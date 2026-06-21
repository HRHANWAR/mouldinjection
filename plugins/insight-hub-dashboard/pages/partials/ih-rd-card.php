<?php
/**
 * partials/ih-rd-card.php — v2.5 Figma listing card (dashboards only).
 *
 * The shared tool-card.php / machine-card.php remain for the marketplace
 * list + detail pages; this card is used exclusively by the redesigned
 * admin + corporation dashboards (styled by ih-redesign.css under .ih-rd).
 *
 * Inputs (set before include):
 *   $m            array  listing row
 *   $kind         string 'machine' | 'tool'   (default: detect)
 *   $anon         bool   hide owner name, show USR-#### (default true)
 *   $rd_detail_url string optional detail link override
 *   $rd_msg_url    string optional message link override
 */
defined( 'ABSPATH' ) || exit;

$kind = isset( $kind ) ? $kind : ( ih_val( $m, 'mould_type' ) || ih_val( $m, 'part_description' ) ? 'tool' : 'machine' );
$kind = ( $kind === 'tool' ) ? 'tool' : 'machine';
$anon = isset( $anon ) ? (bool) $anon : true;

$id     = (int) ih_val( $m, array( 'id', 'ID' ), 0 );
$ref    = ih_listing_ref( $m, $kind );
$img    = ih_val( $m, 'image_1' );
$status = function_exists( 'ih_listing_status_meta' )
	? ih_listing_status_meta( $m )
	: array(
		'label'     => ! empty( $m['available'] ) ? 'Available Now' : 'Pending Review',
		'class'     => ! empty( $m['available'] ) ? 'is-available' : 'is-pending',
		'key'       => ! empty( $m['available'] ) ? 'available' : 'pending',
		'available' => ! empty( $m['available'] ) ? 1 : 0,
	);
$is_pending = ( ( $status['key'] ?? '' ) !== 'available' && ( $status['key'] ?? '' ) !== 'completed' ) ? '1' : '0';

if ( ! empty( $rd_detail_url ) ) {
	$detail = $rd_detail_url;
} else {
	$detail = $kind === 'tool'
		? admin_url( 'admin.php?page=ih-tool-detail&tool_id=' . $id )
		: admin_url( 'admin.php?page=ih-machine-detail&machine_id=' . $id );
}
$msg = ! empty( $rd_msg_url ) ? $rd_msg_url : admin_url( 'admin.php?page=ih-messages' );

$listing_date = ih_val( $m, 'listing_date' );
$expiry_date  = ih_val( $m, 'expiry_date' );

if ( $kind === 'machine' ) {
	$company = $anon
		? 'Listed by · ' . ih_user_ref( ih_val( $m, array( 'owner_id', 'user_id' ), 0 ) )
		: ih_val( $m, 'brand', '—' );
	$mtype     = ih_val( $m, 'machine_type' );
	$materials = function_exists( 'ih_machine_materials' ) ? ih_machine_materials( $m ) : array();
	$util      = ih_val( $m, 'utilization' );
	$util_pct  = (int) preg_replace( '/\D/', '', (string) $util );
	$location  = ih_val( $m, 'location' );
	$op_hours  = ih_val( $m, 'operating_hours' );
	$title     = ih_val( $m, 'title' ) ?: ( 'Machine · ' . $ref );
} else {
	$company = $anon
		? 'Listed by · ' . ih_user_ref( ih_val( $m, array( 'owner_id', 'user_id' ), 0 ) )
		: 'Owner: ' . ih_val( $m, 'owner_name', '—' );
	$desc   = ih_val( $m, 'part_description' );
	$specs  = array_filter( array(
		ih_val( $m, 'mould_type' ),
		ih_val( $m, 'num_cavities_spec' ) ? (int) ih_val( $m, 'num_cavities_spec' ) . ' Cavities' : '',
		ih_val( $m, 'material' ),
	) );
	$grades = array_filter( array(
		ih_val( $m, 'medical_grade' ) === 'Yes' ? 'Medical Grade' : '',
		ih_val( $m, 'food_grade' ) === 'Yes' ? 'Food Grade' : '',
		ih_val( $m, 'recycled_materials' ) === 'Yes' ? 'Recycled' : '',
	) );
	$location = ih_val( $m, 'location' );
	$title    = 'Mould · ' . $ref;
}
?>
<div class="ih-listing-card" data-kind="<?php echo esc_attr( $kind ); ?>" data-status="<?php echo esc_attr( $status['key'] ?? 'pending' ); ?>" data-pending="<?php echo esc_attr( $is_pending ); ?>" data-title="<?php echo esc_attr( strtolower( (string) ih_val( $m, 'title' ) . ' ' . $ref ) ); ?>">
	<div class="ih-listing-img-wrap">
		<?php if ( $img ) : ?>
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $ref ); ?>" loading="lazy" class="ih-listing-img">
		<?php else : ?>
			<span class="ih-glyph"><?php echo ih_icon( $kind === 'tool' ? 'mould' : 'machine', 46, '#3a5c4c' ); ?></span>
		<?php endif; ?>
		<span class="ih-available-badge ih-dash-status <?php echo esc_attr( $status['class'] ); ?>"><?php echo esc_html( $status['label'] ); ?></span>
		<span class="ih-ref"><?php echo esc_html( ( $kind === 'tool' ? 'TOOL ID · ' : 'MACHINE ID · ' ) . $ref ); ?></span>
	</div>

	<div class="ih-listing-body">
		<div class="ih-listing-head">
			<h4 class="ih-listing-title"><?php echo esc_html( $title ); ?></h4>
			<?php if ( $kind === 'machine' && ! empty( $mtype ) ) : ?><span class="ih-type-chip"><?php echo esc_html( $mtype ); ?></span><?php endif; ?>
		</div>
		<div class="ih-listing-company"><?php echo esc_html( $company ); ?></div>

		<?php if ( $kind === 'tool' && ! empty( $desc ) ) : ?>
			<p class="ih-listing-desc"><?php echo esc_html( wp_trim_words( $desc, 18 ) ); ?></p>
		<?php endif; ?>

		<div class="ih-listing-dates">
			<div><div class="ih-date-label">Listing Date</div><div class="ih-date-val"><?php echo $listing_date ? esc_html( date( 'd M Y', strtotime( $listing_date ) ) ) : '—'; ?></div></div>
			<div><div class="ih-date-label">Expiry Date</div><div class="ih-date-val"><?php echo $expiry_date ? esc_html( date( 'd M Y', strtotime( $expiry_date ) ) ) : '—'; ?></div></div>
		</div>

		<?php if ( $kind === 'machine' ) : ?>
			<?php if ( ih_val( $m, 'clamping_force' ) || ih_val( $m, 'shot_size' ) ) : ?>
			<div class="ih-spec-cols">
				<div><div class="ih-date-label">Clamping Force</div><div class="ih-date-val"><?php echo esc_html( ih_val( $m, 'clamping_force', '—' ) ); ?></div></div>
				<div><div class="ih-date-label">Shot Size</div><div class="ih-date-val"><?php echo esc_html( ih_val( $m, 'shot_size', '—' ) ); ?></div></div>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $materials ) ) : ?>
			<div class="ih-pill-row"><?php foreach ( $materials as $mat ) echo '<span class="ih-spec-pill">' . esc_html( $mat ) . '</span>'; ?></div>
			<?php endif; ?>
			<div class="ih-meta-rows">
				<?php if ( ! empty( $location ) ) : ?><div class="ih-meta-row"><?php echo ih_icon( 'pin', 13, '#6b8aa3' ); ?><?php echo esc_html( $location ); ?></div><?php endif; ?>
				<?php if ( ! empty( $op_hours ) ) : ?><div class="ih-meta-row"><?php echo ih_icon( 'clock', 13, '#6b8aa3' ); ?>Operating: <?php echo esc_html( $op_hours ); ?></div><?php endif; ?>
			</div>
			<?php if ( ! empty( $util_pct ) ) : ?>
			<div>
				<div class="ih-util-row"><span>Utilization</span><b><?php echo esc_html( $util ); ?></b></div>
				<div class="ih-progress-bar"><div class="ih-progress-fill" style="width:<?php echo (int) min( 100, $util_pct ); ?>%"></div></div>
			</div>
			<?php endif; ?>
		<?php else : ?>
			<?php if ( ! empty( $specs ) ) : ?>
			<div class="ih-pill-row"><?php foreach ( $specs as $p ) echo '<span class="ih-spec-pill">' . esc_html( $p ) . '</span>'; ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $grades ) ) : ?>
			<div class="ih-grade-row"><?php foreach ( $grades as $g ) echo '<span class="ih-grade-pill">' . esc_html( $g ) . '</span>'; ?></div>
			<?php endif; ?>
			<div class="ih-meta-rows">
				<?php if ( ! empty( $location ) ) : ?><div class="ih-meta-row"><?php echo ih_icon( 'pin', 13, '#6b8aa3' ); ?><?php echo esc_html( $location ); ?></div><?php endif; ?>
				<div class="ih-meta-row"><?php echo ih_icon( 'user', 13, '#6b8aa3' ); ?><?php echo esc_html( $company ); ?></div>
			</div>
		<?php endif; ?>

		<div class="ih-listing-actions">
			<a href="<?php echo esc_url( $detail ); ?>" class="ih-btn ih-btn-primary"><?php echo ih_icon( 'eye', 14, '#fff' ); ?>Details</a>
			<a href="<?php echo esc_url( $msg ); ?>" class="ih-btn ih-btn-outline"><?php echo ih_icon( 'messages', 14 ); ?>Message</a>
		</div>
	</div>
</div>
<?php
// Reset per-include overrides so the next card uses defaults.
unset( $rd_detail_url, $rd_msg_url );
