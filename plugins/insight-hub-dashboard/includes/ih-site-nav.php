<?php
defined( 'ABSPATH' ) || exit;

/**
 * Figma site navigation — shared config for admin + user dashboards.
 */

if ( ! function_exists( 'ih_site_nav_mode' ) ) {
    function ih_site_nav_mode() {
        if ( current_user_can( 'manage_options' ) ) {
            return 'admin';
        }
        return 'user';
    }
}

if ( ! function_exists( 'ih_site_nav_current_page' ) ) {
    function ih_site_nav_current_page() {
        return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    }
}

if ( ! function_exists( 'ih_site_nav_badges' ) ) {
    function ih_site_nav_badges() {
        global $wpdb;
        static $cache = null;
        if ( null !== $cache ) {
            return $cache;
        }
        $user_id = get_current_user_id();
        $pending = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests WHERE LOWER(TRIM(status))='pending'" );
        $unread  = 0;
        if ( $user_id ) {
            $unread = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COALESCE(SUM(unread),0) FROM {$wpdb->prefix}ih_threads WHERE user_id=%d",
                    $user_id
                )
            );
        }
        $enq_pending = 0;
        if ( $user_id && ih_site_nav_mode() === 'user' ) {
            $enq_pending = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ih_requests WHERE user_id=%d AND LOWER(TRIM(status))='pending'",
                    $user_id
                )
            );
        }
        $cache = array(
            'pending'     => $pending,
            'unread'      => $unread,
            'enq_pending' => $enq_pending,
        );
        return $cache;
    }
}

if ( ! function_exists( 'ih_site_nav_item_active' ) ) {
    function ih_site_nav_item_active( $item, $current = '' ) {
        $current = $current ?: ih_site_nav_current_page();
        $pages   = (array) ( $item['pages'] ?? array( $item['page'] ?? '' ) );
        $pages   = array_filter( array_map( 'sanitize_key', $pages ) );
        if ( in_array( $current, $pages, true ) ) {
            return true;
        }
        if ( ! empty( $item['match'] ) && is_callable( $item['match'] ) ) {
            return (bool) call_user_func( $item['match'], $current );
        }
        return false;
    }
}

if ( ! function_exists( 'ih_site_nav_bottom_items' ) ) {
    function ih_site_nav_bottom_items( $mode = '' ) {
        $mode   = $mode ?: ih_site_nav_mode();
        $badges = ih_site_nav_badges();
        $dash   = admin_url( 'admin.php' );

        if ( $mode === 'admin' ) {
            return array(
                array(
                    'key'   => 'home',
                    'label' => 'Home',
                    'url'   => $dash . '?page=ih-dashboard',
                    'page'  => 'ih-dashboard',
                    'pages' => array( 'ih-dashboard' ),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
                ),
                array(
                    'key'   => 'queue',
                    'label' => 'Queue',
                    'url'   => $dash . '?page=ih-requests',
                    'page'  => 'ih-requests',
                    'pages' => array( 'ih-requests' ),
                    'badge' => $badges['pending'],
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
                ),
                array(
                    'key'   => 'listings',
                    'label' => 'Listings',
                    'url'   => $dash . '?page=ih-machines',
                    'page'  => 'ih-machines',
                    'pages' => array( 'ih-machines', 'ih-tools', 'ih-add-machine', 'ih-add-tool' ),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
                ),
                array(
                    'key'   => 'users',
                    'label' => 'Users',
                    'url'   => $dash . '?page=ih-users',
                    'page'  => 'ih-users',
                    'pages' => array( 'ih-users' ),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M2 20c0-3.3 3.1-6 7-6"/><circle cx="17" cy="9" r="2.5"/><path d="M14 20c0-2.2 2.7-4 6-4"/></svg>',
                ),
                array(
                    'key'   => 'messages',
                    'label' => 'Messages',
                    'url'   => $dash . '?page=ih-messages',
                    'page'  => 'ih-messages',
                    'pages' => array( 'ih-messages' ),
                    'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
                ),
            );
        }

        return array(
            array(
                'key'   => 'home',
                'label' => 'Home',
                'url'   => $dash . '?page=ih-user-dashboard',
                'page'  => 'ih-user-dashboard',
                'pages' => array( 'ih-user-dashboard' ),
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>',
            ),
            array(
                'key'   => 'machine',
                'label' => 'Machine',
                'url'   => $dash . '?page=ih-user-add-machine',
                'page'  => 'ih-user-add-machine',
                'pages' => array( 'ih-user-add-machine', 'ih-user-view-machine', 'ih-user-edit-machine' ),
                'match' => function ( $page ) {
                    return in_array( $page, array( 'ih-user-add-machine', 'ih-user-view-machine', 'ih-user-edit-machine' ), true );
                },
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
            ),
            array(
                'key'   => 'tool',
                'label' => 'Tool',
                'url'   => $dash . '?page=ih-user-add-tool',
                'page'  => 'ih-user-add-tool',
                'pages' => array( 'ih-user-add-tool', 'ih-user-view-tool', 'ih-user-edit-tool' ),
                'match' => function ( $page ) {
                    return in_array( $page, array( 'ih-user-add-tool', 'ih-user-view-tool', 'ih-user-edit-tool' ), true );
                },
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0-5.66 5.66l-5.3 5.3a2 2 0 0 0 2.83 2.83l5.3-5.3a4 4 0 0 0 5.66-5.66l-2.12 2.12-2.83-2.83 2.12-2.12Z"/></svg>',
            ),
            array(
                'key'   => 'messages',
                'label' => 'Messages',
                'url'   => $dash . '?page=ih-user-messages',
                'page'  => 'ih-user-messages',
                'pages' => array( 'ih-user-messages' ),
                'badge' => $badges['unread'],
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
            ),
            array(
                'key'   => 'account',
                'label' => 'Account',
                'url'   => $dash . '?page=ih-user-edit-profile',
                'page'  => 'ih-user-edit-profile',
                'pages' => array( 'ih-user-edit-profile' ),
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
            ),
        );
    }
}

if ( ! function_exists( 'ih_site_nav_should_render' ) ) {
    function ih_site_nav_should_render() {
        if ( ! is_admin() || ! is_user_logged_in() ) {
            return false;
        }
        $page = ih_site_nav_current_page();
        if ( ! $page || strpos( $page, 'ih-' ) !== 0 ) {
            return false;
        }
        return true;
    }
}

if ( ! function_exists( 'ih_render_site_bottom_nav' ) ) {
    function ih_render_site_bottom_nav( $args = array() ) {
        if ( ! empty( $GLOBALS['ih_site_nav_rendered'] ) ) {
            return;
        }
        $GLOBALS['ih_site_nav_rendered'] = true;
        $args = wp_parse_args(
            $args,
            array(
                'mode' => ih_site_nav_mode(),
            )
        );
        include IH_DIR . 'pages/partials/ih-site-bottom-nav.php';
    }
}

if ( ! function_exists( 'ih_render_site_nav_header' ) ) {
    function ih_render_site_nav_header( $args = array() ) {
        $args = wp_parse_args(
            $args,
            array(
                'mode' => ih_site_nav_mode(),
            )
        );
        include IH_DIR . 'pages/partials/ih-site-nav-header.php';
    }
}

/**
 * Desktop sidebar primary items — mirrors bottom nav labels/order where applicable.
 */
if ( ! function_exists( 'ih_site_nav_primary_pages' ) ) {
    function ih_site_nav_primary_pages( $mode = '' ) {
        $items = ih_site_nav_bottom_items( $mode ?: ih_site_nav_mode() );
        $pages = array();
        foreach ( $items as $item ) {
            if ( ! empty( $item['fab'] ) ) {
                continue;
            }
            if ( ! empty( $item['pages'] ) ) {
                $pages = array_merge( $pages, (array) $item['pages'] );
            }
            if ( ! empty( $item['page'] ) ) {
                $pages[] = $item['page'];
            }
        }
        return array_unique( array_filter( $pages ) );
    }
}

if ( ! function_exists( 'ih_site_nav_svg_icon' ) ) {
    function ih_site_nav_svg_icon( $name ) {
        $icons = array(
            'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'machine'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
            'tool'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.66 5.66l-5.3 5.3a2 2 0 0 0 2.83 2.83l5.3-5.3a4 4 0 0 0 5.66-5.66l-2.12 2.12-2.83-2.83 2.12-2.12Z"/></svg>',
            'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'messages'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
        );
        return $icons[ $name ] ?? '';
    }
}

if ( ! function_exists( 'ih_site_nav_admin_sidebar_items' ) ) {
    function ih_site_nav_admin_sidebar_items() {
        $dash  = admin_url( 'admin.php' );
        $badges = ih_site_nav_badges();
        return array(
            array(
                'key'   => 'dashboard',
                'label' => 'Dashboard',
                'url'   => $dash . '?page=ih-dashboard',
                'page'  => 'ih-dashboard',
                'icon'  => ih_site_nav_svg_icon( 'dashboard' ),
            ),
            array(
                'key'   => 'queue',
                'label' => 'Review Queue',
                'url'   => $dash . '?page=ih-requests',
                'page'  => 'ih-requests',
                'badge' => $badges['pending'],
                'badge_class' => 'is-warn',
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
            ),
            array(
                'key'   => 'listings',
                'label' => 'All Listings',
                'url'   => $dash . '?page=ih-machines',
                'page'  => 'ih-machines',
                'pages' => array( 'ih-machines', 'ih-tools', 'ih-add-machine', 'ih-add-tool' ),
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            ),
            array(
                'key'   => 'machines',
                'label' => 'Machines',
                'url'   => $dash . '?page=ih-machines',
                'page'  => 'ih-machines',
                'icon'  => ih_site_nav_svg_icon( 'machine' ),
            ),
            array(
                'key'   => 'tools',
                'label' => 'Tools',
                'url'   => $dash . '?page=ih-tools',
                'page'  => 'ih-tools',
                'icon'  => ih_site_nav_svg_icon( 'tool' ),
            ),
            array(
                'key'   => 'users',
                'label' => 'Users',
                'url'   => $dash . '?page=ih-users',
                'page'  => 'ih-users',
                'icon'  => ih_site_nav_svg_icon( 'users' ),
            ),
            array(
                'key'        => 'messages',
                'label'      => 'Messages',
                'url'        => $dash . '?page=ih-messages',
                'page'       => 'ih-messages',
                'badge_ajax' => 'ihSidebarBadge',
                'badge_class' => 'is-lime',
                'icon'       => ih_site_nav_svg_icon( 'messages' ),
            ),
            array(
                'key'   => 'activity',
                'label' => 'Activity',
                'url'   => $dash . '?page=ih-activity',
                'page'  => 'ih-activity',
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            ),
            array(
                'key'   => 'settings',
                'label' => 'Settings',
                'url'   => $dash . '?page=ih-settings',
                'page'  => 'ih-settings',
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            ),
        );
    }
}

if ( ! function_exists( 'ih_site_nav_user_float_nav_accents' ) ) {
    function ih_site_nav_user_float_nav_accents() {
        return array(
            'dashboard'   => '#5347ce',
            'add-machine' => '#4896fe',
            'add-tool'    => '#16a34a',
            'messages'    => '#f59e0b',
            'enquiries'   => '#ef4444',
        );
    }
}

if ( ! function_exists( 'ih_site_nav_user_float_nav_icons' ) ) {
    function ih_site_nav_user_float_nav_icons() {
        return array(
            'dashboard'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
            'add-machine' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><path d="M12 12v4M10 14h4"/></svg>',
            'add-tool'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.66 5.66l-5.3 5.3a2 2 0 0 0 2.83 2.83l5.3-5.3a4 4 0 0 0 5.66-5.66l-2.12 2.12-2.83-2.83 2.12-2.12Z"/></svg>',
            'messages'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
            'enquiries'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg>',
        );
    }
}

if ( ! function_exists( 'ih_site_nav_user_float_nav_items' ) ) {
    function ih_site_nav_user_float_nav_items() {
        $accents = ih_site_nav_user_float_nav_accents();
        $icons   = ih_site_nav_user_float_nav_icons();
        $items   = array();
        foreach ( ih_site_nav_user_sidebar_items() as $item ) {
            $key = $item['key'] ?? '';
            $item['accent']     = $accents[ $key ] ?? '#887cfd';
            $item['float_icon'] = $icons[ $key ] ?? ( $item['icon'] ?? '' );
            $items[]            = $item;
        }
        return $items;
    }
}

if ( ! function_exists( 'ih_render_user_float_nav' ) ) {
    function ih_render_user_float_nav() {
        include IH_DIR . 'pages/partials/ih-user-float-nav.php';
    }
}

if ( ! function_exists( 'ih_site_nav_user_sidebar_items' ) ) {
    function ih_site_nav_user_sidebar_items() {
        $theme  = get_template_directory_uri();
        $dash   = admin_url( 'admin.php' );
        $badges = ih_site_nav_badges();
        return array(
            array(
                'key'   => 'dashboard',
                'label' => 'Dashboard',
                'url'   => $dash . '?page=ih-user-dashboard',
                'page'  => 'ih-user-dashboard',
                'icon'  => '<img src="' . esc_url( $theme . '/assets/images/dashboard-user.png' ) . '" alt="" width="18" height="18">',
                'icon_active' => '<img src="' . esc_url( $theme . '/assets/images/dashboard-user-white.png' ) . '" alt="" width="18" height="18">',
            ),
            array(
                'key'   => 'add-machine',
                'label' => 'Add Machine',
                'url'   => $dash . '?page=ih-user-add-machine',
                'page'  => 'ih-user-add-machine',
                'pages' => array( 'ih-user-add-machine', 'ih-user-edit-machine', 'ih-user-view-machine' ),
                'icon'  => '<img src="' . esc_url( $theme . '/assets/images/Machine-user.png' ) . '" alt="" width="18" height="18">',
                'icon_active' => '<img src="' . esc_url( $theme . '/assets/images/Machine-user-white.png' ) . '" alt="" width="18" height="18">',
            ),
            array(
                'key'   => 'add-tool',
                'label' => 'Add Tool',
                'url'   => $dash . '?page=ih-user-add-tool',
                'page'  => 'ih-user-add-tool',
                'pages' => array( 'ih-user-add-tool', 'ih-user-edit-tool', 'ih-user-view-tool' ),
                'icon'  => '<img src="' . esc_url( $theme . '/assets/images/user-tools.png' ) . '" alt="" width="18" height="18">',
            ),
            array(
                'key'   => 'messages',
                'label' => 'Messages',
                'url'   => $dash . '?page=ih-user-messages',
                'page'  => 'ih-user-messages',
                'badge' => $badges['unread'],
                'badge_class' => 'is-warn',
                'icon'  => '<img src="' . esc_url( $theme . '/assets/images/meassage-user-black.png' ) . '" alt="" width="18" height="18">',
            ),
            array(
                'key'   => 'enquiries',
                'label' => 'My Enquiries',
                'url'   => $dash . '?page=ih-user-dashboard#ihEnquiriesSection',
                'page'  => 'ih-user-dashboard',
                'hash'  => '#ihEnquiriesSection',
                'badge' => $badges['enq_pending'],
                'badge_class' => 'is-warn',
                'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            ),
        );
    }
}
