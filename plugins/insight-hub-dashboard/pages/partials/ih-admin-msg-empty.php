<?php
/**
 * Admin Messages — center pane empty state (Figma 230:2244 / Msg-EmptyState)
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ih-admin-msg-empty__card" role="status">
	<div class="ih-admin-msg-empty__icon-wrap" aria-hidden="true">
		<svg class="ih-admin-msg-empty__icon" width="39" height="95" viewBox="0 0 39 95" fill="none" xmlns="http://www.w3.org/2000/svg">
			<rect x="0.75" y="0.75" width="37.5" height="93.5" rx="19.5" fill="#fff" stroke="#E6E9F2" stroke-width="1.5"/>
			<path d="M11.5 38.5c0-5.8 4.7-10.5 10.5-10.5h5c5.8 0 10.5 4.7 10.5 10.5v8.2c0 5.8-4.7 10.5-10.5 10.5h-2.4l-4.6 4.6v-4.6h-3.5c-5.8 0-10.5-4.7-10.5-10.5v-8.2z" stroke="#5347CE" stroke-width="2.83" stroke-linejoin="round"/>
		</svg>
	</div>
	<h3 class="ih-admin-msg-empty__title"><?php esc_html_e( 'No conversation selected', 'insight-hub-dashboard' ); ?></h3>
	<p class="ih-admin-msg-empty__sub"><?php esc_html_e( 'Pick a conversation on the left to start chatting, share files, or approve a contact request.', 'insight-hub-dashboard' ); ?></p>
	<div class="ih-admin-msg-empty__banner">
		<svg class="ih-admin-msg-empty__banner-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<circle cx="12" cy="12" r="9" stroke="#4896FE" stroke-width="2"/>
			<path d="M12 10v6M12 7h.01" stroke="#4896FE" stroke-width="2" stroke-linecap="round"/>
		</svg>
		<p class="ih-admin-msg-empty__banner-text"><?php esc_html_e( 'Users request contact details from a listing — they\'ll appear here for approval.', 'insight-hub-dashboard' ); ?></p>
	</div>
</div>
