<?php
/**
 * Server render for cmscoe/directory: GET filter form plus a bounded, paginated query.
 *
 * @package CmsCoe\Platform
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$cmscoe_type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : '';
if ( ! $cmscoe_type && is_post_type_archive() ) {
	$cmscoe_type = (string) get_query_var( 'post_type' );
}
$cmscoe_type_obj = $cmscoe_type ? get_post_type_object( $cmscoe_type ) : null;
if ( ! $cmscoe_type_obj || ! $cmscoe_type_obj->public ) {
	return;
}

$cmscoe_taxonomies = array_values(
	array_filter(
		array_map( 'sanitize_key', (array) ( $attributes['taxonomies'] ?? array() ) ),
		static fn( $tax ) => taxonomy_exists( $tax ) && is_object_in_taxonomy( $cmscoe_type, $tax ) && get_taxonomy( $tax )->public
	)
);

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only GET filters.
$cmscoe_filters = array();
foreach ( $cmscoe_taxonomies as $cmscoe_tax ) {
	$cmscoe_filters[ $cmscoe_tax ] = isset( $_GET[ 'f_' . $cmscoe_tax ] ) ? sanitize_title( wp_unslash( $_GET[ 'f_' . $cmscoe_tax ] ) ) : '';
}
$cmscoe_search = ! empty( $attributes['showSearch'] ) && isset( $_GET['q'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['q'] ) ), 0, 100 ) : '';
$cmscoe_page   = isset( $_GET['dpage'] ) ? min( 500, max( 1, absint( $_GET['dpage'] ) ) ) : 1;
// phpcs:enable
$cmscoe_per_page = min( 48, max( 1, (int) ( $attributes['perPage'] ?? 12 ) ) );

$cmscoe_tax_query = array();
foreach ( $cmscoe_filters as $cmscoe_tax => $cmscoe_slug ) {
	if ( $cmscoe_slug && term_exists( $cmscoe_slug, $cmscoe_tax ) ) {
		$cmscoe_tax_query[] = array(
			'taxonomy' => $cmscoe_tax,
			'field'    => 'slug',
			'terms'    => $cmscoe_slug,
		);
	}
}
$cmscoe_by_title = 'date' !== ( $attributes['orderBy'] ?? 'title' );
$cmscoe_query    = new WP_Query(
	array(
		'post_type'      => $cmscoe_type,
		'post_status'    => 'publish',
		'posts_per_page' => $cmscoe_per_page,
		'paged'          => $cmscoe_page,
		's'              => $cmscoe_search,
		'orderby'        => $cmscoe_by_title ? 'title' : 'date',
		'order'          => $cmscoe_by_title ? 'ASC' : 'DESC',
		'tax_query'      => $cmscoe_tax_query ? array_merge( array( 'relation' => 'AND' ), $cmscoe_tax_query ) : array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- taxonomy filters are the indexed approach.
	)
);

$cmscoe_action = is_post_type_archive( $cmscoe_type ) ? get_post_type_archive_link( $cmscoe_type ) : get_permalink( get_queried_object_id() );
$cmscoe_action = $cmscoe_action ? $cmscoe_action : home_url( '/' );
$cmscoe_label  = $cmscoe_type_obj->labels->name;
$cmscoe_active = $cmscoe_search || array_filter( $cmscoe_filters );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-directory' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php if ( $cmscoe_taxonomies || ! empty( $attributes['showSearch'] ) ) : ?>
		<?php /* translators: %s: post type plural label. */ ?>
		<form class="cmscoe-directory__filters" method="get" action="<?php echo esc_url( $cmscoe_action ); ?>" role="search" aria-label="<?php echo esc_attr( sprintf( __( 'Filter %s', 'cmscoe-platform' ), $cmscoe_label ) ); ?>">
			<?php if ( ! empty( $attributes['showSearch'] ) ) : ?>
				<div class="cmscoe-directory__field">
					<label for="cmscoe-directory-q"><?php esc_html_e( 'Keyword', 'cmscoe-platform' ); ?></label>
					<input type="search" id="cmscoe-directory-q" name="q" value="<?php echo esc_attr( $cmscoe_search ); ?>" maxlength="100">
				</div>
			<?php endif; ?>
			<?php
			foreach ( $cmscoe_taxonomies as $cmscoe_tax ) :
				$cmscoe_terms = get_terms(
					array(
						'taxonomy'   => $cmscoe_tax,
						'hide_empty' => true,
						'number'     => 200,
					)
				);
				$cmscoe_id    = 'cmscoe-directory-' . $cmscoe_tax;
				?>
				<div class="cmscoe-directory__field">
					<label for="<?php echo esc_attr( $cmscoe_id ); ?>"><?php echo esc_html( get_taxonomy( $cmscoe_tax )->labels->singular_name ); ?></label>
					<select id="<?php echo esc_attr( $cmscoe_id ); ?>" name="<?php echo esc_attr( 'f_' . $cmscoe_tax ); ?>">
						<option value=""><?php esc_html_e( 'All', 'cmscoe-platform' ); ?></option>
						<?php if ( is_array( $cmscoe_terms ) ) : ?>
							<?php foreach ( $cmscoe_terms as $cmscoe_term ) : ?>
								<option value="<?php echo esc_attr( $cmscoe_term->slug ); ?>"<?php selected( $cmscoe_filters[ $cmscoe_tax ], $cmscoe_term->slug ); ?>><?php echo esc_html( $cmscoe_term->name ); ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
			<?php endforeach; ?>
			<?php // Forge exception: native submit button styled with Forge tokens. Wrapping <button> in <forge-button> is a nested-interactive failure and would not submit before Forge loads. ?>
			<button type="submit" class="cmscoe-button"><?php esc_html_e( 'Apply filters', 'cmscoe-platform' ); ?></button>
			<?php if ( $cmscoe_active ) : ?>
				<a href="<?php echo esc_url( $cmscoe_action ); ?>"><?php esc_html_e( 'Clear filters', 'cmscoe-platform' ); ?></a>
			<?php endif; ?>
		</form>
	<?php endif; ?>
	<h2 class="cmscoe-directory__count">
		<?php
		/* translators: 1: number of results, 2: post type plural label. */
		echo esc_html( sprintf( _n( '%1$d result in %2$s', '%1$d results in %2$s', (int) $cmscoe_query->found_posts, 'cmscoe-platform' ), (int) $cmscoe_query->found_posts, $cmscoe_label ) );
		?>
	</h2>
	<?php if ( $cmscoe_query->have_posts() ) : ?>
		<ul class="cmscoe-directory__list">
			<?php
			while ( $cmscoe_query->have_posts() ) :
				$cmscoe_query->the_post();
				?>
				<li>
					<forge-card>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<?php
						foreach ( $cmscoe_taxonomies as $cmscoe_tax ) :
							$cmscoe_names = wp_get_post_terms( get_the_ID(), $cmscoe_tax, array( 'fields' => 'names' ) );
							if ( $cmscoe_names && ! is_wp_error( $cmscoe_names ) ) :
								?>
								<p class="cmscoe-directory__meta"><?php echo esc_html( get_taxonomy( $cmscoe_tax )->labels->singular_name . ': ' . implode( ', ', $cmscoe_names ) ); ?></p>
								<?php
							endif;
						endforeach;
						?>
						<?php if ( has_excerpt() ) : ?>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
					</forge-card>
				</li>
			<?php endwhile; ?>
		</ul>
		<?php if ( $cmscoe_query->max_num_pages > 1 ) : ?>
			<?php /* translators: %s: post type plural label. */ ?>
			<nav class="cmscoe-directory__pagination" aria-label="<?php echo esc_attr( sprintf( __( '%s result pages', 'cmscoe-platform' ), $cmscoe_label ) ); ?>">
				<?php
				echo wp_kses_post(
					(string) paginate_links(
						array(
							'base'    => esc_url_raw( add_query_arg( 'dpage', '%#%' ) ),
							'format'  => '',
							'current' => $cmscoe_page,
							'total'   => (int) $cmscoe_query->max_num_pages,
						)
					)
				);
				?>
			</nav>
		<?php endif; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing matches these filters. Try different filters, or clear them.', 'cmscoe-platform' ); ?></p>
	<?php endif; ?>
	<?php wp_reset_postdata(); ?>
</div>
