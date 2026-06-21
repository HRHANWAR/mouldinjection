<?php defined( 'ABSPATH' ) || exit;
// $m = tool row from DB (included from pages/tools.php)

$id     = (int) ( $m['id'] ?? 0 );
$ref    = function_exists( 'ih_listing_ref' ) ? ih_listing_ref( $m, 'tool' ) : 'TL-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
$img    = ! empty( $m['image_1'] ) ? $m['image_1'] : '';
$listing_status_meta = function_exists( 'ih_listing_status_meta' )
	? ih_listing_status_meta( $m )
	: array(
		'label' => ! empty( $m['available'] ) ? 'Available Now' : 'Pending Review',
		'class' => ! empty( $m['available'] ) ? 'is-available' : 'is-pending',
	);

$spec_pills = array_filter( array(
	! empty( $m['mould_type'] ) ? $m['mould_type'] : null,
	! empty( $m['num_cavities_spec'] ) ? (int) $m['num_cavities_spec'] . ' Cavities' : null,
	! empty( $m['material'] ) ? $m['material'] : null,
	! empty( $m['tolerance_pp'] ) ? 'PP' : null,
	! empty( $m['tolerance_pe'] ) ? 'PE' : null,
) );

$grade_pills = array_filter( array(
	( ( $m['medical_grade'] ?? '' ) === 'Yes' ) ? 'Medical Grade' : null,
	( ( $m['food_grade'] ?? '' ) === 'Yes' ) ? 'Food Grade' : null,
	( stripos( (string) ( $m['material_grade'] ?? '' ), 'recycled' ) !== false ) ? 'Recycled' : null,
) );

$detail_url  = admin_url( 'admin.php?page=ih-tool-detail&tool_id=' . $id );
$message_url = admin_url( 'admin.php?page=ih-messages' );
$owner_id    = (int) ( $m['owner_id'] ?? $m['user_id'] ?? 0 );
$owner_label = ! empty( $m['owner_name'] )
	? $m['owner_name']
	: ( function_exists( 'ih_user_ref' ) ? ih_user_ref( $owner_id ) : '—' );
?>
<div class="ih-listing-card ih-tool-card ih-tool-card--figma" data-title="<?php echo esc_attr( strtolower( (string) ( $m['title'] ?? '' ) ) ); ?>">
	<div class="ih-listing-img-wrap">
		<?php if ( $img ) : ?>
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $ref ); ?>" loading="lazy" class="ih-listing-img">
		<?php else : ?>
			<span class="ih-glyph"><?php echo function_exists( 'ih_icon' ) ? ih_icon( 'tool', 50, '#3a5c4c' ) : ''; ?></span>
		<?php endif; ?>
		<span class="ih-available-badge ih-dash-status <?php echo esc_attr( $listing_status_meta['class'] ); ?>">
			<span class="ih-status-dot" aria-hidden="true"></span>
			<?php echo esc_html( $listing_status_meta['label'] ); ?>
		</span>
		<span class="ih-ref"><?php echo esc_html( 'TOOL ID · ' . $ref ); ?></span>
	</div>

	<div class="ih-listing-body">
		<div class="ih-listing-head">
			<h3 class="ih-listing-title"><?php echo esc_html( 'Tool · ' . $ref ); ?></h3>
		</div>

		<?php if ( ! empty( $m['part_description'] ) ) : ?>
			<p class="ih-listing-desc"><?php echo esc_html( $m['part_description'] ); ?></p>
		<?php elseif ( ! empty( $m['title'] ) ) : ?>
			<p class="ih-listing-desc"><?php echo esc_html( $m['title'] ); ?></p>
		<?php endif; ?>

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

		<?php if ( $spec_pills ) : ?>
		<div class="ih-pill-row">
			<?php foreach ( $spec_pills as $pill ) : ?>
				<span class="ih-spec-pill"><?php echo esc_html( $pill ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $grade_pills ) : ?>
		<div class="ih-grade-row">
			<?php foreach ( $grade_pills as $grade ) : ?>
				<span class="ih-grade-pill"><?php echo esc_html( $grade ); ?></span>
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
			<?php if ( $owner_label && $owner_label !== '—' ) : ?>
			<div class="ih-meta-row">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'user', 14, '#6b7185' ) : ''; ?>
				Owner: <strong><?php echo esc_html( $owner_label ); ?></strong>
			</div>
			<?php endif; ?>
		</div>

		<div class="ih-listing-actions">
			<a href="<?php echo esc_url( $detail_url ); ?>" class="ih-btn ih-btn-primary">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'eye', 14, '#fff' ) : ''; ?>
				View Details
			</a>
			<a href="<?php echo esc_url( $message_url ); ?>" class="ih-btn ih-btn-outline">
				<?php echo function_exists( 'ih_icon' ) ? ih_icon( 'messages', 14, '#1a1c2b' ) : ''; ?>
				Message
			</a>
		</div>
	</div>
</div>
