<?php
/**
 * FEEDUS - 카카오 로그인 인가코드 중복 소비 방지
 *
 * 카카오 OAuth 인가코드는 1회용입니다.
 * 브라우저 프리페치, 페이지 리로드, 뒤로가기 등으로
 * 같은 code 파라미터가 두 번 이상 wp-login.php 에 도달하면
 * 두 번째 요청부터 카카오가 KOE320 (authorization code not found)
 * 에러를 반환하면서 사용자에게 wp-login.php 에러 화면이 노출됩니다.
 *
 * 특히 Safari 의 Preload Top Hit / Smart Search / Link Preview,
 * Chrome 의 Prerender / Prefetch 등이 이 문제를 일으킵니다.
 *
 * 이 파일은 아래 2중 방어로 모든 중복 요청 케이스를 차단합니다:
 *
 *   1차 방어: 프리페치 전용 요청 차단
 *     - 브라우저가 붙이는 Purpose / Sec-Purpose / X-Moz 헤더를 감지
 *     - 프리페치 요청은 503 으로 즉시 거부하여 카카오 토큰 교환까지 가지 못하게 함
 *     - 실제 사용자 네비게이션 요청에서만 플러그인이 코드를 소비
 *
 *   2차 방어: 코드 해시 transient 잠금
 *     - 처음 도달한 code 의 해시를 transient 에 기록 (10분 유효)
 *     - 같은 code 가 다시 들어오면 플러그인 훅보다 먼저 가로채서
 *       에러 화면 없이 /my-account/ 로 조용히 리다이렉트
 *     - 카카오 토큰 교환을 아예 시도하지 않으므로 KOE320 이 발생하지 않음
 *
 * WPCode 또는 테마 functions.php 에서 require_once 로 불러오세요.
 * kakao-tam 플러그인보다 먼저 로드되도록 우선순위 0 훅을 사용합니다.
 *
 * 원본 이슈: wp-login.php 에 KOE320 invalid_grant 노출 (Safari 에서 재현)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* ==========================================================================
   상수 정의
   ========================================================================== */

if ( ! defined( 'FEEDUS_KAKAO_CODE_TTL' ) ) {
    // 인가코드 잠금 유효 시간 (초). 카카오 코드 자체가 약 10분 유효.
    define( 'FEEDUS_KAKAO_CODE_TTL', 10 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'FEEDUS_KAKAO_LANDING_PATH' ) ) {
    // 중복 요청 시 리다이렉트할 경로 (플러그인의 Landing path 와 맞춤)
    define( 'FEEDUS_KAKAO_LANDING_PATH', '/my-account/' );
}


/* ==========================================================================
   유틸리티
   ========================================================================== */

/**
 * 현재 요청이 카카오 OAuth 콜백인지 판별
 * - wp-login.php 경로에 code & state 파라미터가 함께 붙어 있으면 콜백으로 간주
 */
if ( ! function_exists( 'feedus_is_kakao_oauth_callback' ) ) {
    function feedus_is_kakao_oauth_callback() {
        if ( empty( $_GET['code'] ) || empty( $_GET['state'] ) ) {
            return false;
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        if ( strpos( $request_uri, 'wp-login.php' ) === false ) {
            return false;
        }

        return true;
    }
}

/**
 * 현재 요청이 브라우저 프리페치/프리로드 요청인지 판별
 * - Chrome/Edge: Purpose: prefetch 또는 Sec-Purpose: prefetch;prerender
 * - Firefox: X-Moz: prefetch
 * - Safari 는 공식 헤더를 항상 붙이지는 않지만 일부 버전에서 X-Purpose 사용
 */
if ( ! function_exists( 'feedus_is_prefetch_request' ) ) {
    function feedus_is_prefetch_request() {
        $headers = array(
            isset( $_SERVER['HTTP_PURPOSE'] ) ? $_SERVER['HTTP_PURPOSE'] : '',
            isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ? $_SERVER['HTTP_SEC_PURPOSE'] : '',
            isset( $_SERVER['HTTP_X_PURPOSE'] ) ? $_SERVER['HTTP_X_PURPOSE'] : '',
            isset( $_SERVER['HTTP_X_MOZ'] ) ? $_SERVER['HTTP_X_MOZ'] : '',
        );

        foreach ( $headers as $value ) {
            if ( $value === '' ) {
                continue;
            }
            if ( stripos( $value, 'prefetch' ) !== false ) {
                return true;
            }
            if ( stripos( $value, 'prerender' ) !== false ) {
                return true;
            }
            if ( stripos( $value, 'preview' ) !== false ) {
                return true;
            }
        }

        return false;
    }
}

/**
 * 인가코드 해시 기반 transient 키 생성
 * - code 전체를 키에 쓰면 너무 길어지므로 해시 사용
 */
if ( ! function_exists( 'feedus_kakao_code_lock_key' ) ) {
    function feedus_kakao_code_lock_key( $code ) {
        return 'feedus_kakao_code_' . md5( (string) $code );
    }
}


/* ==========================================================================
   1차 방어: 프리페치 요청 차단 + 캐시 금지 헤더
   ========================================================================== */

/**
 * wp-login.php 에 도달한 카카오 콜백 요청 중 프리페치성 요청을 503 으로 거부
 * - login_init 은 wp-login.php 초기화 시 실행되는 공식 훅
 * - 우선순위 0 으로 kakao-tam 플러그인보다 먼저 실행
 */
add_action( 'login_init', function () {
    if ( ! feedus_is_kakao_oauth_callback() ) {
        return;
    }

    // 프리페치/프리렌더/프리뷰 요청은 즉시 거부
    if ( feedus_is_prefetch_request() ) {
        status_header( 503 );
        nocache_headers();
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'X-Feedus-Kakao-Guard: prefetch-blocked' );
        exit;
    }

    // 정상 사용자 요청이더라도 브라우저/프록시가 응답을 캐시/재요청하지 못하도록
    // 강력한 캐시 금지 + 검색엔진 수집 차단 헤더를 부여
    nocache_headers();
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
    header( 'Pragma: no-cache' );
    header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
}, 0 );


/* ==========================================================================
   2차 방어: 인가코드 중복 소비 방지 (transient 잠금)
   ========================================================================== */

/**
 * 같은 인가코드가 두 번째 이상 도달하면 플러그인 처리 전에 가로채서
 * 에러 화면 없이 조용히 Landing path 로 리다이렉트
 *
 * - 첫 요청: transient 에 코드 해시 기록 후 kakao-tam 플러그인 처리 흐름으로 진행
 * - 두 번째 이후 요청: transient 에 이미 있으므로 즉시 리다이렉트
 */
add_action( 'login_init', function () {
    if ( ! feedus_is_kakao_oauth_callback() ) {
        return;
    }

    $code     = sanitize_text_field( wp_unslash( $_GET['code'] ) );
    $lock_key = feedus_kakao_code_lock_key( $code );

    // 이미 처리된 코드인 경우
    if ( get_transient( $lock_key ) ) {
        $redirect_to = home_url( FEEDUS_KAKAO_LANDING_PATH );
        nocache_headers();
        header( 'X-Feedus-Kakao-Guard: duplicate-code-bypassed' );
        wp_safe_redirect( $redirect_to );
        exit;
    }

    // 첫 요청으로 기록 (이후 kakao-tam 플러그인이 실제 토큰 교환 수행)
    set_transient( $lock_key, 1, FEEDUS_KAKAO_CODE_TTL );
}, 1 );
