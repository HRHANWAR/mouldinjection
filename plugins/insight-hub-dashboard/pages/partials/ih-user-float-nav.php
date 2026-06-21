<?php defined( 'ABSPATH' ) || exit;

$current_page = ih_site_nav_current_page();
$items        = ih_site_nav_user_float_nav_items();
$user         = wp_get_current_user();
$gravatar_url = function_exists( 'ih_get_user_avatar_url' )
    ? ih_get_user_avatar_url( get_current_user_id(), 72 )
    : get_avatar_url( get_current_user_id(), array( 'size' => 72 ) );
$fallback_url = 'https://ui-avatars.com/api/?name=' . rawurlencode( $user->display_name ?: 'U' ) . '&background=5347ce&color=ffffff&size=72&bold=true&rounded=true&length=2';
?>
<nav
    class="ih-float-nav"
    id="ihFloatNav"
    aria-label="<?php esc_attr_e( 'Workspace navigation', 'insight-hub-dashboard' ); ?>"
>
    <button
        type="button"
        class="ih-float-nav__toggle"
        id="ihFloatNavToggle"
        aria-expanded="false"
        aria-controls="ihFloatNavPanel"
        aria-label="<?php esc_attr_e( 'Open workspace menu', 'insight-hub-dashboard' ); ?>"
    >
        <span class="ih-float-nav__toggle-glow" aria-hidden="true"></span>
        <span class="ih-float-nav__toggle-stack" aria-hidden="true">
            <?php foreach ( array_slice( $items, 0, 4 ) as $stack_item ) : ?>
                <span
                    class="ih-float-nav__stack-dot"
                    style="--ih-float-accent: <?php echo esc_attr( $stack_item['accent'] ); ?>"
                ></span>
            <?php endforeach; ?>
            <span class="ih-float-nav__toggle-icon ih-float-nav__toggle-icon--close" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="20" height="20">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </span>
        </span>
        <span class="ih-float-nav__toggle-label"><?php esc_html_e( 'Menu', 'insight-hub-dashboard' ); ?></span>
    </button>

    <div
        class="ih-float-nav__panel"
        id="ihFloatNavPanel"
        role="region"
        aria-label="<?php esc_attr_e( 'Navigation links', 'insight-hub-dashboard' ); ?>"
        hidden
    >
        <ul class="ih-float-nav__list" role="list">
            <?php foreach ( $items as $item ) :
                $is_active = ih_site_nav_item_active( $item, $current_page );
                $badge     = isset( $item['badge'] ) ? (int) $item['badge'] : 0;
                $onclick   = ! empty( $item['hash'] )
                    ? ' onclick="var el=document.querySelector(\'' . esc_js( $item['hash'] ) . '\');if(el){el.scrollIntoView({behavior:\'smooth\'});}return false;"'
                    : '';
                ?>
            <li class="ih-float-nav__item-wrap">
                <a
                    class="ih-float-nav__item<?php echo $is_active ? ' is-active' : ''; ?>"
                    href="<?php echo esc_url( $item['url'] ); ?>"
                    style="--ih-float-accent: <?php echo esc_attr( $item['accent'] ); ?>"
                    <?php echo $onclick; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                >
                    <span class="ih-float-nav__icon" aria-hidden="true">
                        <?php echo $item['float_icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </span>
                    <span class="ih-float-nav__label"><?php echo esc_html( $item['label'] ); ?></span>
                    <?php if ( $badge > 0 ) : ?>
                        <span class="ih-float-nav__badge"><?php echo $badge > 99 ? '99+' : (int) $badge; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="ih-float-nav__account">
            <img
                class="ih-float-nav__avatar"
                src="<?php echo esc_url( $gravatar_url ); ?>"
                alt=""
                width="32"
                height="32"
                onerror="this.onerror=null;this.src='<?php echo esc_js( $fallback_url ); ?>'"
            >
            <div class="ih-float-nav__account-meta">
                <span class="ih-float-nav__account-name"><?php echo esc_html( $user->display_name ); ?></span>
                <span class="ih-float-nav__account-email"><?php echo esc_html( $user->user_email ); ?></span>
            </div>
            <a class="ih-float-nav__logout" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
                <?php esc_html_e( 'Logout', 'insight-hub-dashboard' ); ?>
            </a>
        </div>
    </div>
</nav>
