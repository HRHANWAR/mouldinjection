<?php defined( 'ABSPATH' ) || exit;
/** Drawer record body — expects vars from ih_render_user_drawer(). */
if ( ! function_exists( 'ihur_icon' ) ) {
    function ihur_icon( $name ) {
        $set = array(
            'ban'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>',
            'send'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>',
            'edit'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
            'user'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>',
            'mail'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 6-10 7L2 6"/></svg>',
            'phone'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
            'chat'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H8l-4 3.5V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"/></svg>',
            'link'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
            'building' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/></svg>',
            'role'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'briefcase'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>',
            'home'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            'pin'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
            'hash'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>',
            'clock'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        );
        return $set[ $name ] ?? '';
    }
}
$ih_u_empty = function ( $val ) {
    $val = is_scalar( $val ) ? trim( (string) $val ) : '';
    return $val === '' ? '<span class="val empty">— not provided</span>' : '<span class="val">' . esc_html( $val ) . '</span>';
};
$comp_tone = $completion >= 85 ? 'is-green' : ( $completion >= 65 ? 'is-amber' : 'is-rose' );
$uid_label = isset( $uid_label ) ? $uid_label : ( function_exists( 'ih_user_uid_label' ) ? ih_user_uid_label( $unique_id, $drawer_uid ) : strtoupper( $unique_id ) . ' · USR-' . (int) $drawer_uid );
$is_verified = ! empty( $is_verified );
$file_count  = isset( $file_count ) ? (int) $file_count : count( (array) ( $files ?? array() ) );
?>
<div class="ih-u-drawer-inner" data-drawer-uid="<?php echo esc_attr( $drawer_uid ); ?>">
  <header class="ih-u-drawer-head">
    <div class="ih-u-ident">
      <?php echo function_exists( 'ih_user_avatar_html' ) ? ih_user_avatar_html( $drawer_uid, $user->display_name, 'lg' ) : ''; ?>
      <div class="ih-u-ident-text">
        <h2 class="ih-u-name"><?php echo esc_html( $user->display_name ); ?></h2>
        <div class="ih-u-meta-row">
          <p class="ih-u-uid"><?php echo esc_html( $uid_label ); ?></p>
          <?php echo ih_badge( $blocked ? 'Blocked' : 'Active' ); ?>
        </div>
      </div>
    </div>
  </header>

  <div class="ih-u-complete">
    <div class="ih-u-complete-top">
      <span>Profile completeness</span>
      <div class="ih-u-complete-right">
        <?php if ( $is_verified ) : ?><span class="ih-u-verified-badge">Verified</span><?php endif; ?>
        <strong class="ih-u-complete-pct <?php echo esc_attr( $comp_tone ); ?>"><?php echo (int) $completion; ?>%</strong>
      </div>
    </div>
    <div class="ih-progress-bar ih-u-progress"><span class="ih-progress-fill <?php echo esc_attr( $comp_tone ); ?>" style="width:<?php echo (int) $completion; ?>%"></span></div>
    <?php if ( ! empty( $missing ) ) : ?>
      <p class="ih-u-missing">Missing: <?php echo esc_html( implode( ', ', $missing ) ); ?></p>
    <?php endif; ?>
  </div>

  <div class="ih-u-mini-stats">
    <div class="is-listings"><span class="num"><?php echo (int) $listings; ?></span><span class="lab">Listings</span></div>
    <div class="is-requests"><span class="num"><?php echo (int) $requests; ?></span><span class="lab">Requests</span></div>
    <div class="is-pending"><span class="num"><?php echo (int) $pending; ?></span><span class="lab">Pending</span></div>
  </div>

  <div class="ih-u-sec">
    <h4>Contact</h4>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'mail' ); ?></span><div><span class="lab">Email<?php echo $confirmed ? ' (confirmed)' : ''; ?></span><div class="ih-u-val-row"><?php echo $ih_u_empty( $email ); ?><?php if ( $confirmed ) : ?><span class="ih-u-check" title="Confirmed">✓</span><?php endif; ?></div></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'phone' ); ?></span><div><span class="lab">Office number</span><?php echo $ih_u_empty( $phone ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'chat' ); ?></span><div><span class="lab">WhatsApp</span><?php echo $ih_u_empty( $whatsapp ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'link' ); ?></span><div><span class="lab">Website</span><?php if ( $website ) : ?><a class="val" href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', $website ) ); ?></a><?php else : ?><?php echo $ih_u_empty( '' ); ?><?php endif; ?></div></div>
  </div>

  <div class="ih-u-sec">
    <h4>Business</h4>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'building' ); ?></span><div><span class="lab">Company name</span><?php echo $ih_u_empty( $company ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'role' ); ?></span><div><span class="lab">Business role</span><?php echo $role ? '<span class="val">' . esc_html( $role ) . '</span>' : $ih_u_empty( '' ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'briefcase' ); ?></span><div><span class="lab">Job title</span><?php echo $ih_u_empty( $job ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'user' ); ?></span><div><span class="lab">Contact name</span><?php echo $ih_u_empty( $user->display_name ); ?></div></div>
  </div>

  <div class="ih-u-sec">
    <h4>Address</h4>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'home' ); ?></span><div><span class="lab">Street</span><?php echo $ih_u_empty( $address ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'pin' ); ?></span><div><span class="lab">Town / city</span><?php echo $ih_u_empty( $city ); ?></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'hash' ); ?></span><div><span class="lab">Postcode</span><?php echo $ih_u_empty( $postcode ); ?></div></div>
  </div>

  <div class="ih-u-sec">
    <h4 class="ih-u-sec-head">Uploaded files &amp; links<?php if ( $file_count ) : ?><span class="ih-u-file-count"><?php echo (int) $file_count; ?></span><?php endif; ?></h4>
    <?php if ( empty( $files ) ) : ?>
      <p class="ih-u-empty">No files uploaded.</p>
    <?php else : ?>
      <?php foreach ( $files as $f ) : ?>
        <div class="ih-u-file">
          <span class="ic" aria-hidden="true"><?php echo $f['mime'] === 'image' ? '🖼' : ( $f['mime'] === 'link' ? '↗' : '📄' ); ?></span>
          <div class="meta-wrap">
            <div class="name"><?php echo esc_html( $f['label'] ); ?></div>
            <div class="meta"><?php echo esc_html( $f['mime'] ); ?><?php echo ! empty( $f['size'] ) ? ' · ' . esc_html( $f['size'] ) : ''; ?></div>
          </div>
          <a class="view" href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener"><?php echo $f['mime'] === 'link' ? 'Open' : 'View'; ?></a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="ih-u-sec">
    <h4>Account</h4>
    <div class="ih-u-row"><span class="ic" aria-hidden="true">ID</span><div><span class="lab">Unique ID</span><span class="val"><?php echo esc_html( strtoupper( $unique_id ) ); ?></span></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'clock' ); ?></span><div><span class="lab">Registered</span><span class="val"><?php echo esc_html( $registered ); ?></span></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true"><?php echo ihur_icon( 'clock' ); ?></span><div><span class="lab">Last active</span><span class="val"><?php echo esc_html( $last_active ); ?></span></div></div>
    <div class="ih-u-row"><span class="ic" aria-hidden="true">✓</span><div><span class="lab">Email confirmed</span><span class="val ih-u-val-row"><?php echo $confirmed ? 'Yes' : 'No'; ?><?php if ( $confirmed ) : ?><span class="ih-u-check" title="Confirmed">✓</span><?php endif; ?></span></div></div>
  </div>

  <div class="ih-u-actions">
    <a class="ih-btn ih-btn-primary" href="<?php echo esc_url( $message_url ); ?>"><?php echo ihur_icon( 'send' ); ?> Message</a>
    <a class="ih-btn ih-btn-outline" href="<?php echo esc_url( $edit_url ); ?>"><?php echo ihur_icon( 'edit' ); ?> Edit</a>
    <button type="button" class="ih-btn ih-btn-outline ih-block-user-btn <?php echo $blocked ? 'is-unblock' : 'is-block'; ?>"
            data-uid="<?php echo esc_attr( $drawer_uid ); ?>"
            data-blocked="<?php echo $blocked ? '1' : '0'; ?>">
      <?php echo ihur_icon( 'ban' ); ?> <?php echo $blocked ? 'Unblock' : 'Block'; ?>
    </button>
  </div>
</div>
