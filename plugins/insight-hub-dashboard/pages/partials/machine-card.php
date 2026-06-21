<?php defined( 'ABSPATH' ) || exit;
// $m = machine row from DB (included from pages/machines.php)

$id     = (int) ( $m['id'] ?? 0 );
$ref    = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $m, 'machine' ) : 'MCH-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
$img    = ! empty( $m['image_1'] ) ? $m['image_1'] : '';
$materials = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
$listing_status_meta = function_exists( 'ih_listing_status_meta' )
	? ih_listing_status_meta( $m )
	: array(
		'label' => ! empty( $m['available'] ) ? 'Available Now' : 'Pending Review',
		'class' => ! empty( $m['available'] ) ? 'is-available' : 'is-pending',
	);

$detail_url  = isset( $ih_machine_detail_url )
	? $ih_machine_detail_url
	: admin_url( 'admin.php?page=ih-machine-detail&machine_id=' . $id );
$message_url = isset( $ih_machine_message_url )
	? $ih_machine_message_url
	: admin_url( 'admin.php?page=ih-messages' );
$owner_id    = (int) ( $m['owner_id'] ?? $m['user_id'] ?? 0 );
$listed_by  = function_exists( 'ih_user_ref' ) ? 'Listed by · ' . ih_user_ref( $owner_id ) : ( $m['brand'] ?: '—' );
$mtype      = $m['machine_type'] ?? '';
$util       = $m['utilization'] ?? '';
$util_pct   = (int) preg_replace( '/\D/', '', (string) $util );
?>
<div class="ih-listing-card ih-machine-card ih-machine-card--figma" data-title="<?php echo esc_attr( strtolower( (string) ( $m['title'] ?? '' ) ) ); ?>">
	<div class="ih-listing-img-wrap">
		<?php if ( $img ) : ?>
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $ref ); ?>" loading="lazy" class="ih-listing-img">
		<?php else : ?>
			<span class="ih-glyph"><?php echo function_exists( 'ih_icon' ) ? ih_icon( 'machine', 50, '#3a5c4c' ) : ''; ?></span>
		<?php endif; ?>
		<span class="ih-available-badge ih-dash-status <?php echo esc_attr( $listing_status_meta['class'] ); ?>">
			<span class="ih-status-dot" aria-hidden="true"></span>
			<?php echo esc_html( $listing_status_meta['label'] ); ?>
		</span>
		<span class="ih-ref"><?php echo esc_html( 'MACHINE ID · ' . $ref ); ?></span>
		<?php if ( ! empty( $ih_machine_show_wishlist ) ) : ?>
		<button type="button"
			class="ih-machine-fav-btn<?php echo ! empty( $ih_machine_is_saved ) ? ' is-favourite' : ''; ?>"
			aria-label="<?php esc_attr_e( 'Save to wishlist', 'insight-hub-dashboard' ); ?>"
			onclick="ihToggleWishlist(<?php echo (int) $id; ?>,'machine',<?php echo wp_json_encode( (string) ( $m['title'] ?? 'Machine' ) ); ?>,<?php echo wp_json_encode( (string) $img ); ?>,this)">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
		</button>
		<?php endif; ?>
	</div>

	<div class="ih-listing-body">
		<div class="ih-listing-head">
			<h3 class="ih-listing-title"><?php echo esc_html( ih_val( $m, 'title' ) ?: ( 'Machine · ' . $ref ) ); ?></h3>
			<?php if ( $mtype ) : ?>
				<span class="ih-type-chip"><?php echo esc_html( $mtype ); ?></span>
			<?php endif; ?>
		</div>
		<div class="ih-listing-company"><?php echo esc_html( $listed_by ); ?></div>

		<div class="ih-listing-dates">
			<div>
				<div class="ih-date-label">Listing Date</div>
				<div class="ih-date-val"><?php echo ! empty( $m['listing_date'] ) ? esc_html( date( 'd M Y', strtotime( $m['listing_date'] ) ) ) : '—'; ?></div>
			</div>
			<div>
				<div class="ih-date-label">Expiry Date</div>
				<div class="ih-date-val"><?php echo ! empty( $m['expiry_date'] ) ? esc_html( date( 'd M Y', strtotime( $m['expiry_date'] ) ) ) : '—'; ?></div>
			</div>
		</div>

		<div class="ih-spec-cols">
			<div>
				<div class="ih-date-label">Clamping Force</div>
				<div class="ih-date-val"><?php echo esc_html( $m['clamping_force'] ?? '—' ); ?></div>
			</div>
			<div>
				<div class="ih-date-label">Shot Size</div>
				<div class="ih-date-val"><?php echo esc_html( $m['shot_size'] ?? '—' ); ?></div>
			</div>
		</div>

		<?php if ( $materials ) : ?>
		<div class="ih-pill-row">
			<?php foreach ( $materials as $mat ) : ?>
				<span class="ih-spec-pill"><?php echo esc_html( $mat ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="ih-meta-rows">
			<?php if ( ! empty( $m['location'] ) ) : ?>
			<div class="ih-meta-row">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'pin', 14, '#6b7185' ) : ''; ?>
				<?php echo esc_html( $m['location'] ); ?>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $m['operating_hours'] ) ) : ?>
			<div class="ih-meta-row">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'clock', 14, '#6b7185' ) : ''; ?>
				Operating: <?php echo esc_html( $m['operating_hours'] ); ?>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $util_pct > 0 || $util !== '' ) : ?>
		<div class="ih-machine-util">
			<div class="ih-util-row">
				<span>Utilization</span>
				<b><?php echo esc_html( $util ?: $util_pct . '%' ); ?></b>
			</div>
			<?php if ( $util_pct > 0 ) : ?>
			<div class="ih-progress-bar"><div class="ih-progress-fill" style="width:<?php echo (int) min( 100, $util_pct ); ?>%"></div></div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="ih-listing-actions">
			<a href="<?php echo esc_url( $detail_url ); ?>" class="ih-btn ih-btn-primary">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'eye', 14, '#fff' ) : ''; ?>
				Details
			</a>
			<a href="<?php echo esc_url( $message_url ); ?>" class="ih-btn ih-btn-outline">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'messages', 14, '#1a1c2b' ) : ''; ?>
				Message
			</a>
		</div>
	</div>
</div>
