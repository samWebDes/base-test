<?php
/**
 * Seed SANITIZED, fictional local content for the default agency content model (ddev-workflow.md
 * step 6). Idempotent: existing items are skipped. Local passwords are for DDEV only.
 *
 * Usage: ddev wp eval-file bin/seed-local.php
 *
 * @package Cms
 */

if ( 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Seeding is only allowed in local (DDEV).' );
}

$cmscoe_p = 'cms';

$cmscoe_find = static function ( string $title, string $type ): int {
	$q = new WP_Query(
		array(
			'post_type'      => $type,
			'title'          => $title,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $q->posts ? (int) $q->posts[0] : 0;
};

$cmscoe_para = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";

$cmscoe_pattern = static function ( string $slug ): string {
	$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'tyler-cms-theme/' . $slug );
	return $pattern ? $pattern['content'] : '';
};

/**
 * Create an entry if missing. Returns its ID.
 *
 * @param string $type   Post type.
 * @param string $title  Title.
 * @param array  $args   content, excerpt, fields, terms, status.
 */
$cmscoe_entry = static function ( string $type, string $title, array $args = array() ) use ( $cmscoe_find ): int {
	$id = $cmscoe_find( $title, $type );
	if ( $id ) {
		return $id;
	}
	$id = (int) wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => $args['status'] ?? 'publish',
			'post_title'   => $title,
			'post_name'    => $args['slug'] ?? '',
			'post_excerpt' => $args['excerpt'] ?? '',
			'post_content' => $args['content'] ?? '',
		)
	);
	foreach ( $args['fields'] ?? array() as $name => $value ) {
		update_field( $name, $value, $id );
	}
	foreach ( $args['terms'] ?? array() as $taxonomy => $names ) {
		wp_set_object_terms( $id, $names, $taxonomy );
	}
	WP_CLI::log( "created {$type}: {$title}" );
	return $id;
};

// Terms.
$cmscoe_terms = array(
	"{$cmscoe_p}_topic"    => array( 'Health', 'Business', 'Licenses and permits', 'Public safety', 'Transportation' ),
	"{$cmscoe_p}_audience" => array( 'Residents', 'Businesses', 'Older adults', 'Veterans' ),
	"{$cmscoe_p}_division" => array( 'Administration', 'Field Services', 'Licensing' ),
	"{$cmscoe_p}_doc_type" => array( 'Forms', 'Reports', 'Rules and regulations' ),
);
foreach ( $cmscoe_terms as $cmscoe_tax => $cmscoe_names ) {
	foreach ( $cmscoe_names as $cmscoe_name ) {
		if ( taxonomy_exists( $cmscoe_tax ) && ! term_exists( $cmscoe_name, $cmscoe_tax ) ) {
			wp_insert_term( $cmscoe_name, $cmscoe_tax );
		}
	}
}

// Locations.
$cmscoe_central = $cmscoe_entry(
	"{$cmscoe_p}_location",
	'Central Office',
	array(
		'excerpt' => 'Main office for walk-in services and records.',
		'content' => $cmscoe_para( 'Fictional sample location for local development.' ),
		'fields'  => array(
			'location_address'       => "100 Capitol Avenue\nCapital City, ST 00001",
			'location_hours'         => "Monday to Friday: 8 a.m. to 5 p.m.\nClosed state holidays",
			'location_phone'         => '555-555-0100',
			'location_email'         => 'central@example.gov',
			'location_accessibility' => 'Step-free entrance on Capitol Avenue. Accessible parking in Lot B. Interpreters available on request.',
			'location_latitude'      => 39.7684,
			'location_longitude'     => -86.1581,
		),
		'terms'   => array( "{$cmscoe_p}_division" => array( 'Administration' ) ),
	)
);
$cmscoe_north   = $cmscoe_entry(
	"{$cmscoe_p}_location",
	'North Service Center',
	array(
		'excerpt' => 'Licensing and permits for the northern counties.',
		'content' => $cmscoe_para( 'Fictional sample location for local development.' ),
		'fields'  => array(
			'location_address' => "45 Lake Road\nNorthfield, ST 00002",
			'location_hours'   => 'Tuesday to Saturday: 9 a.m. to 4 p.m.',
			'location_phone'   => '555-555-0101',
		),
		'terms'   => array( "{$cmscoe_p}_division" => array( 'Licensing' ) ),
	)
);

// Services.
$cmscoe_entry(
	"{$cmscoe_p}_service",
	'Apply for a business license',
	array(
		'excerpt' => 'Register a new business and get the state license you need to operate.',
		'content' => $cmscoe_para( 'Fictional sample service. Replace with plain-language steps.' ),
		'fields'  => array(
			'service_eligibility'     => 'Anyone starting a business in the state.',
			'service_how_to_apply'    => "1. Gather your business name and tax ID.\n2. Complete the online application.\n3. Pay the fee.",
			'service_apply_url'       => 'https://example.gov/apply',
			'service_fee'             => '$50',
			'service_processing_time' => '5 to 10 business days',
			'service_phone'           => '555-555-0110',
			'service_locations'       => array( $cmscoe_central, $cmscoe_north ),
		),
		'terms'   => array(
			"{$cmscoe_p}_topic"    => array( 'Business', 'Licenses and permits' ),
			"{$cmscoe_p}_audience" => array( 'Businesses' ),
			"{$cmscoe_p}_division" => array( 'Licensing' ),
		),
	)
);
$cmscoe_entry(
	"{$cmscoe_p}_service",
	'Request a public record',
	array(
		'excerpt' => 'Ask for copies of agency records under the state public records law.',
		'content' => $cmscoe_para( 'Fictional sample service.' ),
		'fields'  => array(
			'service_eligibility'  => 'Anyone may request public records.',
			'service_how_to_apply' => 'Submit the request form online or in person at the Central Office.',
			'service_fee'          => 'Free for the first 50 pages',
			'service_locations'    => array( $cmscoe_central ),
		),
		'terms'   => array(
			"{$cmscoe_p}_topic"    => array( 'Public safety' ),
			"{$cmscoe_p}_audience" => array( 'Residents', 'Businesses' ),
		),
	)
);
$cmscoe_entry(
	"{$cmscoe_p}_service",
	'Get help paying for health care',
	array(
		'excerpt' => 'Find programs that help residents and older adults pay for care.',
		'content' => $cmscoe_para( 'Fictional sample service.' ),
		'fields'  => array(
			'service_eligibility' => 'Residents who meet income guidelines.',
			'service_apply_url'   => 'https://example.gov/benefits',
		),
		'terms'   => array(
			"{$cmscoe_p}_topic"    => array( 'Health' ),
			"{$cmscoe_p}_audience" => array( 'Residents', 'Older adults' ),
		),
	)
);

// Events (dates relative to today so they stay upcoming).
$cmscoe_date = static fn( string $relative ): string => wp_date( 'Y-m-d H:i:s', strtotime( $relative ) );
$cmscoe_entry(
	"{$cmscoe_p}_event",
	'Public hearing on proposed rules',
	array(
		'excerpt' => 'Comment on proposed changes to licensing rules.',
		'content' => $cmscoe_para( 'Fictional sample event.' ),
		'fields'  => array(
			'event_start'            => $cmscoe_date( '+14 days 18:00' ),
			'event_end'              => $cmscoe_date( '+14 days 20:00' ),
			'event_location'         => $cmscoe_central,
			'event_registration_url' => 'https://example.gov/register',
			'event_cost'             => 'Free',
		),
		'terms'   => array( "{$cmscoe_p}_topic" => array( 'Licenses and permits' ) ),
	)
);
$cmscoe_entry(
	"{$cmscoe_p}_event",
	'Small business workshop',
	array(
		'excerpt' => 'A free online class on starting a business.',
		'content' => $cmscoe_para( 'Fictional sample event.' ),
		'fields'  => array(
			'event_start' => $cmscoe_date( '+30 days 10:00' ),
			'event_end'   => $cmscoe_date( '+30 days 12:00' ),
			'event_venue' => 'Online (link sent after registration)',
		),
		'terms'   => array(
			"{$cmscoe_p}_topic"    => array( 'Business' ),
			"{$cmscoe_p}_audience" => array( 'Businesses' ),
		),
	)
);

// One active alert, shown site-wide by the header's Active alerts block.
$cmscoe_entry(
	"{$cmscoe_p}_alert",
	'North Service Center closed for repairs',
	array(
		'excerpt' => 'Use the Central Office until repairs are complete.',
		'content' => $cmscoe_para( 'Fictional sample alert.' ),
		'fields'  => array(
			'alert_severity'  => 'warning',
			'alert_expires'   => $cmscoe_date( '+21 days' ),
			'alert_locations' => array( $cmscoe_north ),
		),
	)
);

// Document, FAQs, people, news.
$cmscoe_entry(
	"{$cmscoe_p}_document",
	'Business license application form',
	array(
		'excerpt' => 'Paper version of the business license application.',
		'content' => $cmscoe_para( 'Fictional sample document. Attach an accessible PDF in the File field.' ),
		'fields'  => array(
			'document_number'         => 'BL-100',
			'document_effective_date' => wp_date( 'Ymd', strtotime( '-60 days' ) ),
		),
		'terms'   => array(
			"{$cmscoe_p}_doc_type" => array( 'Forms' ),
			"{$cmscoe_p}_topic"    => array( 'Business' ),
		),
	)
);
foreach ( array(
	'How do I renew a license?'                          => 'Renew online up to 60 days before your license expires.',
	'What are your office hours?'                        => 'Most offices are open Monday to Friday, 8 a.m. to 5 p.m. Check the Locations page for each office.',
	'How do I report outdated information on this site?' => 'Use the contact page. We review reports within five business days.',
) as $cmscoe_q => $cmscoe_a ) {
	$cmscoe_entry( "{$cmscoe_p}_faq", $cmscoe_q, array( 'content' => $cmscoe_para( $cmscoe_a ) ) );
}
$cmscoe_entry(
	"{$cmscoe_p}_person",
	'Jordan Sample',
	array(
		'content' => $cmscoe_para( 'Fictional sample person.' ),
		'fields'  => array(
			'person_job_title' => 'Director',
			'person_email'     => 'director@example.gov',
			'person_location'  => $cmscoe_central,
		),
		'terms'   => array( "{$cmscoe_p}_division" => array( 'Administration' ) ),
	)
);
foreach ( array( 'Agency launches new website', 'New online license renewals' ) as $cmscoe_title ) {
	$cmscoe_entry(
		'post',
		$cmscoe_title,
		array(
			'excerpt' => 'Fictional sample news item.',
			'content' => $cmscoe_para( 'Fictional sample news item for local development.' ),
		)
	);
}

// Pages built from the theme's patterns.
$cmscoe_pages = array(
	'home'    => array( 'Home', $cmscoe_pattern( 'hero' ) . $cmscoe_pattern( 'card-grid' ) . $cmscoe_pattern( 'call-to-action' ) ),
	'news'    => array( 'News', '' ),
	'about'   => array( 'About us', $cmscoe_para( 'Explain what the agency does, who leads it, and the laws that govern it.' ) . $cmscoe_pattern( 'faq-accordion' ) ),
	'contact' => array( 'Contact us', $cmscoe_para( 'Tell us how we can help, or report outdated information on this site.' ) . $cmscoe_pattern( 'contact-panel' ) ),
);
$cmscoe_ids   = array();
foreach ( $cmscoe_pages as $cmscoe_slug => list( $cmscoe_title, $cmscoe_content ) ) {
	$cmscoe_ids[ $cmscoe_slug ] = $cmscoe_entry(
		'page',
		$cmscoe_title,
		array(
			'slug'    => $cmscoe_slug,
			'content' => $cmscoe_content,
		)
	);
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $cmscoe_ids['home'] );
update_option( 'page_for_posts', $cmscoe_ids['news'] );
update_option( 'permalink_structure', '/%postname%/' );

// Primary navigation used by the header's Navigation block.
if ( ! get_posts(
	array(
		'post_type'   => 'wp_navigation',
		'post_status' => 'publish',
		'numberposts' => 1,
	)
) ) {
	$cmscoe_links = '';
	foreach ( array(
		'Services'  => '/services/',
		'Locations' => '/locations/',
		'Events'    => '/events/',
		'News'      => '/news/',
		'About'     => '/about/',
		'Contact'   => '/contact/',
	) as $cmscoe_label => $cmscoe_url ) {
		$cmscoe_links .= '<!-- wp:navigation-link {"label":"' . $cmscoe_label . '","url":"' . $cmscoe_url . '","kind":"custom","isTopLevelLink":true} /-->';
	}
	wp_insert_post(
		array(
			'post_type'    => 'wp_navigation',
			'post_status'  => 'publish',
			'post_title'   => 'Main',
			'post_content' => $cmscoe_links,
		)
	);
}

// Local test users, one per role.
foreach ( array(
	'editor1'   => 'editor',
	'reviewer1' => 'cmscoe_reviewer',
	'author1'   => 'author',
) as $cmscoe_login => $cmscoe_role ) {
	if ( ! get_user_by( 'login', $cmscoe_login ) ) {
		wp_insert_user(
			array(
				'user_login' => $cmscoe_login,
				'user_pass'  => $cmscoe_login . '-local-only',
				'user_email' => $cmscoe_login . '@example.test',
				'role'       => $cmscoe_role,
			)
		);
		WP_CLI::log( "created user {$cmscoe_login} ({$cmscoe_role})" );
	}
}

// Admin skin defaults from the brand palette (accessible variants reach 4.5:1 with white).
$cmscoe_palette = array_column( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ), 'color', 'slug' );
if ( ! get_option( 'cmscoe_admin_skin' ) && isset( $cmscoe_palette['primary-text'], $cmscoe_palette['neutral-dark'] ) ) {
	update_option(
		'cmscoe_admin_skin',
		array(
			'primary'       => $cmscoe_palette['neutral-dark'],
			'secondary'     => $cmscoe_palette['primary-text'],
			'logo_id'       => 0,
			'background_id' => 0,
			'tagline'       => get_bloginfo( 'name' ),
		)
	);
}

flush_rewrite_rules( false );
WP_CLI::success( 'Local content seeded.' );
