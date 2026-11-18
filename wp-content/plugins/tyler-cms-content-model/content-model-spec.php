<?php
/**
 * Default CMS COE agency content model: the starting point every new site gets.
 *
 * The bin/bootstrap-content-model.php script turns this spec into Secure Custom Fields definitions, which SCF
 * saves as JSON in ./content-model/. After that, the JSON files are the source of truth and
 * Administrators change the model in the builder UI (DDEV or Dev in SFTP mode). Edit this spec only
 * before the first bootstrap, to shape the model for a new site.
 *
 * Field names are unprefixed and shared across CMS COE sites (platform blocks such as Active alerts
 * read alert_severity / alert_expires). Post type and taxonomy keys carry the site prefix.
 * News uses core Posts.
 *
 * @package Cms\ContentModel
 */

defined( 'ABSPATH' ) || exit;

$p = 'cms';

return array(
	'taxonomies'   => array(
		array(
			'key'          => "{$p}_topic",
			'singular'     => 'Topic',
			'plural'       => 'Topics',
			'slug'         => 'topic',
			'hierarchical' => true,
			'types'        => array( "{$p}_service", "{$p}_event", "{$p}_location", "{$p}_document", "{$p}_faq", 'post' ),
		),
		array(
			'key'          => "{$p}_audience",
			'singular'     => 'Audience',
			'plural'       => 'Audiences',
			'slug'         => 'audience',
			'hierarchical' => true,
			'types'        => array( "{$p}_service", "{$p}_event", "{$p}_document" ),
		),
		array(
			'key'          => "{$p}_division",
			'singular'     => 'Division',
			'plural'       => 'Divisions',
			'slug'         => 'division',
			'hierarchical' => true,
			'types'        => array( "{$p}_service", "{$p}_location", "{$p}_person", "{$p}_document" ),
		),
		array(
			'key'          => "{$p}_doc_type",
			'singular'     => 'Document type',
			'plural'       => 'Document types',
			'slug'         => 'document-type',
			'hierarchical' => true,
			'types'        => array( "{$p}_document" ),
		),
	),

	'post_types'   => array(
		array(
			'key'         => "{$p}_service",
			'singular'    => 'Service',
			'plural'      => 'Services',
			'slug'        => 'services',
			'icon'        => 'dashicons-clipboard',
			'description' => 'Something the agency does for the public: a program, license, permit, benefit, or request.',
			'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		),
		array(
			'key'         => "{$p}_location",
			'singular'    => 'Location',
			'plural'      => 'Locations',
			'slug'        => 'locations',
			'icon'        => 'dashicons-location',
			'description' => 'An office, facility, or service center the public can visit.',
			'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		),
		array(
			'key'         => "{$p}_event",
			'singular'    => 'Event',
			'plural'      => 'Events',
			'slug'        => 'events',
			'icon'        => 'dashicons-calendar-alt',
			'description' => 'A public meeting, hearing, class, or event with a date and time.',
			'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		),
		array(
			'key'         => "{$p}_alert",
			'singular'    => 'Alert',
			'plural'      => 'Alerts',
			'slug'        => 'alerts',
			'icon'        => 'dashicons-warning',
			'description' => 'A time-limited notice (closure, outage, emergency). Shown site-wide until it expires.',
			'supports'    => array( 'title', 'editor', 'excerpt', 'revisions' ),
		),
		array(
			'key'         => "{$p}_document",
			'singular'    => 'Document',
			'plural'      => 'Documents',
			'slug'        => 'documents',
			'icon'        => 'dashicons-media-document',
			'description' => 'A form, report, or publication with a file to download and a summary page.',
			'supports'    => array( 'title', 'editor', 'excerpt', 'revisions' ),
		),
		array(
			'key'         => "{$p}_faq",
			'singular'    => 'FAQ',
			'plural'      => 'FAQs',
			'slug'        => 'faqs',
			'icon'        => 'dashicons-editor-help',
			'description' => 'A frequently asked question. The title is the question; the content is the answer.',
			'supports'    => array( 'title', 'editor', 'revisions' ),
		),
		array(
			'key'         => "{$p}_person",
			'singular'    => 'Person',
			'plural'      => 'People',
			'slug'        => 'people',
			'icon'        => 'dashicons-id',
			'description' => 'A staff directory or leadership entry. Publish only work contact details.',
			'supports'    => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		),
	),

	'field_groups' => array(
		array(
			'key'       => 'service',
			'title'     => 'Service details',
			'post_type' => "{$p}_service",
			'fields'    => array(
				array( 'service_eligibility', 'Who can apply', 'textarea', array( 'rows' => 3 ) ),
				array( 'service_how_to_apply', 'How to apply', 'textarea', array( 'rows' => 4 ) ),
				array( 'service_apply_url', 'Apply online', 'url', array( 'instructions' => 'Link to the online application, if there is one.' ) ),
				array( 'service_fee', 'Fee', 'text', array( 'maxlength' => 120 ) ),
				array( 'service_processing_time', 'Processing time', 'text', array( 'maxlength' => 120 ) ),
				array( 'service_phone', 'Phone', 'text', array( 'maxlength' => 40 ) ),
				array(
					'service_locations',
					'Where to get this service',
					'relationship',
					array(
						'post_type'     => array( "{$p}_location" ),
						'return_format' => 'id',
						'filters'       => array( 'search' ),
					),
				),
			),
		),
		array(
			'key'       => 'location',
			'title'     => 'Location details',
			'post_type' => "{$p}_location",
			'fields'    => array(
				array(
					'location_address',
					'Address',
					'textarea',
					array(
						'rows'     => 3,
						'required' => 1,
					),
				),
				array( 'location_hours', 'Hours', 'textarea', array( 'rows' => 3 ) ),
				array( 'location_phone', 'Phone', 'text', array( 'maxlength' => 40 ) ),
				array( 'location_email', 'Email', 'email' ),
				array(
					'location_accessibility',
					'Accessibility information',
					'textarea',
					array(
						'rows'         => 3,
						'instructions' => 'Parking, entrances, elevators, interpreters, and other access details.',
					),
				),
				array(
					'location_latitude',
					'Latitude',
					'number',
					array(
						'min'  => -90,
						'max'  => 90,
						'step' => 'any',
					),
				),
				array(
					'location_longitude',
					'Longitude',
					'number',
					array(
						'min'  => -180,
						'max'  => 180,
						'step' => 'any',
					),
				),
			),
		),
		array(
			'key'       => 'event',
			'title'     => 'Event details',
			'post_type' => "{$p}_event",
			'fields'    => array(
				array(
					'event_start',
					'Starts',
					'date_time_picker',
					array(
						'required'       => 1,
						'display_format' => 'F j, Y g:i a',
						'return_format'  => 'Y-m-d H:i:s',
					),
				),
				array(
					'event_end',
					'Ends',
					'date_time_picker',
					array(
						'display_format' => 'F j, Y g:i a',
						'return_format'  => 'Y-m-d H:i:s',
					),
				),
				array(
					'event_location',
					'Location',
					'post_object',
					array(
						'post_type'     => array( "{$p}_location" ),
						'return_format' => 'id',
						'allow_null'    => 1,
					),
				),
				array( 'event_venue', 'Other venue or online link text', 'text', array( 'instructions' => 'Use when the event is not at one of our locations.' ) ),
				array( 'event_registration_url', 'Register', 'url' ),
				array( 'event_cost', 'Cost', 'text', array( 'maxlength' => 120 ) ),
				array( 'event_contact_email', 'Contact email', 'email' ),
			),
		),
		array(
			'key'       => 'alert',
			'title'     => 'Alert details',
			'post_type' => "{$p}_alert",
			'fields'    => array(
				array(
					'alert_severity',
					'Severity',
					'select',
					array(
						'required'      => 1,
						'choices'       => array(
							'info'     => 'Notice',
							'warning'  => 'Warning',
							'critical' => 'Urgent',
						),
						'default_value' => 'info',
						'return_format' => 'value',
					),
				),
				array(
					'alert_expires',
					'Expires',
					'date_time_picker',
					array(
						'display_format' => 'F j, Y g:i a',
						'return_format'  => 'Y-m-d H:i:s',
						'instructions'   => 'The alert stops showing after this time. Leave empty to show it until you unpublish it.',
					),
				),
				array(
					'alert_locations',
					'Affected locations',
					'relationship',
					array(
						'post_type'     => array( "{$p}_location" ),
						'return_format' => 'id',
						'filters'       => array( 'search' ),
					),
				),
			),
		),
		array(
			'key'       => 'document',
			'title'     => 'Document details',
			'post_type' => "{$p}_document",
			'fields'    => array(
				array(
					'document_file',
					'File',
					'file',
					array(
						'return_format' => 'id',
						'instructions'  => 'Upload an accessible (tagged) PDF or an office document. Prefer a web page when possible.',
					),
				),
				array( 'document_number', 'Form or document number', 'text', array( 'maxlength' => 60 ) ),
				array(
					'document_effective_date',
					'Effective date',
					'date_picker',
					array(
						'display_format' => 'F j, Y',
						'return_format'  => 'Ymd',
					),
				),
			),
		),
		array(
			'key'       => 'person',
			'title'     => 'Contact details',
			'post_type' => "{$p}_person",
			'fields'    => array(
				array( 'person_job_title', 'Job title', 'text', array( 'maxlength' => 120 ) ),
				array( 'person_email', 'Work email', 'email' ),
				array( 'person_phone', 'Work phone', 'text', array( 'maxlength' => 40 ) ),
				array(
					'person_location',
					'Office',
					'post_object',
					array(
						'post_type'     => array( "{$p}_location" ),
						'return_format' => 'id',
						'allow_null'    => 1,
					),
				),
			),
		),
	),
);
