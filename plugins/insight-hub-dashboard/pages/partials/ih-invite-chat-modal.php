<?php
/**
 * Invite to chat modal — Figma Msg-InviteModal (227:2244)
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ih-invite-scrim" id="ihInviteModalScrim" hidden aria-hidden="true"></div>
<div
	class="ih-invite-modal"
	id="ihInviteModal"
	role="dialog"
	aria-modal="true"
	aria-labelledby="ihInviteModalTitle"
	aria-describedby="ihInviteModalDesc"
	hidden
>
	<header class="ih-invite-modal__head">
		<div class="ih-invite-modal__titles">
			<h2 id="ihInviteModalTitle"><?php esc_html_e( 'Invite to chat', 'insight-hub-dashboard' ); ?></h2>
			<p id="ihInviteModalDesc" class="ih-invite-modal__sub">
				<?php esc_html_e( 'Add another user to this conversation — admin only.', 'insight-hub-dashboard' ); ?>
			</p>
		</div>
		<button type="button" class="ih-invite-modal__close" id="ihInviteModalClose" aria-label="<?php esc_attr_e( 'Close', 'insight-hub-dashboard' ); ?>">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
		</button>
	</header>

	<div class="ih-invite-modal__search-wrap">
		<label class="ih-invite-modal__search" for="ihInviteSearch">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9aa0b4" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
			<input
				type="search"
				id="ihInviteSearch"
				placeholder="<?php esc_attr_e( 'Search users by name or USR-ID…', 'insight-hub-dashboard' ); ?>"
				autocomplete="off"
				aria-controls="ihInviteUserList"
			>
		</label>
	</div>

	<div class="ih-invite-modal__list" id="ihInviteUserList" role="listbox" aria-multiselectable="true" aria-label="<?php esc_attr_e( 'Users to invite', 'insight-hub-dashboard' ); ?>">
		<div class="ih-invite-modal__loading" id="ihInviteLoading" hidden><?php esc_html_e( 'Loading users…', 'insight-hub-dashboard' ); ?></div>
		<div class="ih-invite-modal__empty" id="ihInviteEmpty" hidden><?php esc_html_e( 'No users match your search.', 'insight-hub-dashboard' ); ?></div>
	</div>

	<footer class="ih-invite-modal__foot">
		<span class="ih-invite-modal__summary" id="ihInviteSelectionSummary"><?php esc_html_e( '0 selected', 'insight-hub-dashboard' ); ?></span>
		<button type="button" class="ih-invite-modal__submit" id="ihInviteAddBtn" disabled>
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
			<?php esc_html_e( 'Add to chat', 'insight-hub-dashboard' ); ?>
		</button>
	</footer>
	<p class="ih-invite-modal__note">
		<?php esc_html_e( 'Invited users join as guests — admin stays the chat owner.', 'insight-hub-dashboard' ); ?>
	</p>
</div>
