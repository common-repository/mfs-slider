<?php
/*
Plugin Name: Mufasa 🦁 Slider
Description: Criado para melhor gerenciamento de banners no Slider Principal podendo programar data e hora de entrada e saída de banner junto com a possibilidade de incluir link e imagens Desktop e Mobile
Version: 1994.9
Author: mufasa
Author URI: https://mufasa.com.br
License: GPLv2 or later
Text Domain: mfs_slider
*/

define( 'MFS_SLIDER_PLUGIN_VERSION', '1994.9' );

include_once( plugin_dir_path( __FILE__ ) . 'includes/acf/acf.php' );

function get_current_post_type() {
	global $post, $typenow, $current_screen;

	//we have a post so we can just get the post type from that
	if ( $post && $post->post_type ) {
		return $post->post_type;
	}

	//check the global $typenow - set in admin.php
	elseif ( $typenow ) {
		return $typenow;
	}

	//check the global $current_screen object - set in sceen.php
	elseif ( $current_screen && $current_screen->post_type ) {
		return $current_screen->post_type;
	}

	//check the post_type querystring
	elseif ( isset( $_REQUEST['post_type'] ) ) {
		return sanitize_key( $_REQUEST['post_type'] );
	}

	//lastly check if post ID is in query string
	elseif ( isset( $_REQUEST['post'] ) ) {
		return get_post_type( $_REQUEST['post'] );
	}

	//we do not know the post type!
	return null;
}

function mfs_slider_admin_init_dashboard() {
	wp_enqueue_style( 'fontawesome', 'https://use.fontawesome.com/releases/v5.13.0/css/all.css' );

	if (is_admin() && 'mfs_slider' == get_current_post_type()) :
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-sortable');

		wp_register_script('mfs_order', plugin_dir_url( __FILE__ ) . 'assets/order.js', array('jquery'));
		global $userdata;
		$mfs_order_variables = array(
			'mfs_order_sort_nonce' => wp_create_nonce( 'mfs_order_sort_nonce' . $userdata->ID)
		);
		wp_localize_script('mfs_order', 'mfs_order', $mfs_order_variables);
		wp_enqueue_script('mfs_order');
	endif;
}
add_action('admin_init', 'mfs_slider_admin_init_dashboard');

function mfs_slider_admin_head_dashboard() {
	echo "<style type='text/css' media='screen'>#adminmenu #menu-posts-mfs_slider div.wp-menu-image:before { font-family: 'Font Awesome\ 5 Free'; content: '\\f1b0'; font-weight: 900;}</style>";

	if (get_post_type() === 'mfs_slider') {
		echo "<style>.misc-pub-section.curtime {display:none !important;} </style>";
	}
}
add_action('admin_head', 'mfs_slider_admin_head_dashboard');

function mfs_save_order() {
	set_time_limit(600);

	global $wpdb, $userdata;

	$post_type = filter_var ( $_POST['post_type'], FILTER_SANITIZE_STRING);
	$paged     = filter_var ( $_POST['paged'], FILTER_SANITIZE_NUMBER_INT);
	$nonce     = $_POST['mfs_order_sort_nonce'];

	if (! wp_verify_nonce( $nonce, 'mfs_order_sort_nonce' . $userdata->ID ) )
		die();

	parse_str($_POST['order'], $data);

	if (!is_array($data) || count($data) < 1)
		die();

	$mysql_query = $wpdb->prepare("SELECT ID FROM ". $wpdb->posts ."
		WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future', 'inherit')
		ORDER BY menu_order, post_date DESC", $post_type);
	$results = $wpdb->get_results($mysql_query);

	if (!is_array($results) || count($results) < 1)
		die();

	$objects_ids = array();
	foreach ($results as $result) {
		$objects_ids[] = (int)$result->ID;
	}

	global $userdata;
	$objects_per_page = get_user_meta($userdata->ID ,'edit_' .  $post_type  .'_per_page', TRUE);
	if (empty($objects_per_page))
		$objects_per_page = 20;

	$edit_start_at = $paged * $objects_per_page - $objects_per_page;
	$index = 0;
	for ($i = $edit_start_at; $i < ($edit_start_at + $objects_per_page); $i++) {
		if (!isset($objects_ids[$i]))
			break;

		$objects_ids[$i] = (int)$data['post'][$index];
		$index++;
	}

	foreach ($objects_ids as $menu_order => $id) {
		$data = array(
			'menu_order' => $menu_order
		);

		$wpdb->update( $wpdb->posts, $data, array('ID' => $id) );
	}
}
add_action('wp_ajax_mfs_save_order', 'mfs_save_order');
add_action('wp_ajax_nopriv_mfs_save_order', 'mfs_save_order');

function mfs_sort_order($query){
	if (is_admin() && $query->get('post_type') == 'mfs_slider') :
		$query->set( 'order', 'ASC' );
		$query->set( 'orderby', 'menu_order' );
	endif;
};
add_action( 'pre_get_posts', 'mfs_sort_order');

function mfs_slider_add_column($cols) {
	unset( $cols['date'] );
	$cols['data_de_inicio'] = __('Data de Início', 'mfs');
	$cols['data_de_termino'] = __('Data de Término', 'mfs');
	$cols['link'] = __('Link?', 'mfs');
	return $cols;
}
add_filter('manage_mfs_slider_posts_columns', 'mfs_slider_add_column');

function mfs_columns($column_name, $id) {
	$return = '—';
	if ('data_de_inicio' == $column_name) {
		$data = get_post_meta($id, 'mfs_slider_data_de_inicio', true);
		if ($data) {
			$return = date('d/m/Y H:i:s', strtotime($data));
		}
	}
	if ('data_de_termino' == $column_name) {
		$data = get_post_meta($id, 'mfs_slider_data_de_termino', true);
		if ($data) {
			$return = date('d/m/Y H:i:s', strtotime($data));
		}
	}
	if ('link' == $column_name) {
		$link = get_post_meta($id, 'mfs_slider_link', true);
		if ($link) {
			$return = '<i class="fas fa-check"></i>';
		}
	}
	echo $return;
}
add_filter('manage_mfs_slider_posts_custom_column', 'mfs_columns', 10, 3);

function mfs_slider_add_slider() {
	register_post_type('mfs_slider', array(
		'labels' => array(
			'menu_name' => __( 'Mufasa Slider', 'mfs_slider' ),
			'name' => __( '🦁 Mufasa Slider', 'mfs_slider' ),
			'add_new' => __( 'Adicionar Slide', 'mfs_slider' ),
			'add_new_item' => __( 'Adicionar Slide', 'mfs_slider' ),
			'new_item' => __( 'Adicionar Slide', 'mfs_slider' ),
			'edit_item' => __( 'Editar Slide', 'mfs_slider' ),
		),
		'public' => false,
		'has_archive' => false,
		'publicly_queryable' => true,
		'exclude_from_search' => true,
		'show_ui' => true,
		'rewrite' => false,
		'supports' => array('title'),
		'menu_position' => 2,
	) );
	register_taxonomy( 'mfs_slider_category',
		array( 'mfs_slider' ),
		array(
			'hierarchical' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
		)
	);
}
add_action('init', 'mfs_slider_add_slider');

// Begin ACF Fields
if( function_exists('acf_add_local_field_group') ):
	acf_add_local_field_group(array(
		'key' => 'group_5e14adca32c22',
		'title' => 'mfs - Slider',
		'fields' => array(
			array(
				'key' => 'field_5e14ae1b3e4f1',
				'label' => 'Data de Início',
				'name' => 'mfs_slider_data_de_inicio',
				'type' => 'date_time_picker',
				'instructions' => 'Caso não queira programar a exibição do Slide, deixar em branco',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'd/m/Y g:i a',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 1,
			),
			array(
				'key' => 'field_5e14ae8e3e4f2',
				'label' => 'Data de Término',
				'name' => 'mfs_slider_data_de_termino',
				'type' => 'date_time_picker',
				'instructions' => 'Caso não queira programar a exibição do Slide, deixar em branco',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'display_format' => 'd/m/Y g:i a',
				'return_format' => 'Y-m-d H:i:s',
				'first_day' => 1,
			),
			array(
				'key' => 'field_5e14aea93e4f3',
				'label' => 'Link',
				'name' => 'mfs_slider_link',
				'type' => 'url',
				'instructions' => 'Caso não haja link, deixar em branco',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'default_value' => '',
				'placeholder' => '',
			),
			array(
				'key' => 'field_5e14add03e4ef',
				'label' => 'Imagem Desktop',
				'name' => 'mfs_slider_imagem_desktop',
				'type' => 'image',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'return_format' => 'url',
				'preview_size' => 'full',
				'library' => 'all',
				'min_width' => '',
				'min_height' => '',
				'min_size' => '',
				'max_width' => '',
				'max_height' => '',
				'max_size' => '',
				'mime_types' => '',
			),
			array(
				'key' => 'field_5e14ae0f3e4f0',
				'label' => 'Imagem Mobile',
				'name' => 'mfs_slider_imagem_mobile',
				'type' => 'image',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'return_format' => 'url',
				'preview_size' => 'full',
				'library' => 'all',
				'min_width' => '',
				'min_height' => '',
				'min_size' => '',
				'max_width' => '',
				'max_height' => '',
				'max_size' => '',
				'mime_types' => '',
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'mfs_slider',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
	));
endif;
// End ACF Fields

function mfs_slider_tinySliderEnqueueScripts() {
	// Tiny Slider # https://github.com/ganlanyuan/tiny-slider
	wp_enqueue_script('tiny-slider_JS', plugin_dir_url( __FILE__ ) . 'assets/tiny-slider.js', array(), MFS_SLIDER_PLUGIN_VERSION, true);
	wp_enqueue_style('tiny-slider_CSS', plugin_dir_url( __FILE__ ) . 'assets/tiny-slider.css', array(), MFS_SLIDER_PLUGIN_VERSION, 'all');
	wp_enqueue_script('tiny-slider_JS_custom', plugin_dir_url( __FILE__ ) . 'assets/js.js', array(), MFS_SLIDER_PLUGIN_VERSION, true);
	wp_enqueue_style('tiny-slider_CSS_custom', plugin_dir_url( __FILE__ ) . 'assets/css.css', array(), MFS_SLIDER_PLUGIN_VERSION, 'all');
}
add_action( 'wp_enqueue_scripts', 'mfs_slider_tinySliderEnqueueScripts' );

// Shortcode
function mfs_slider_shortcode_slider( $atts ) {
	global $wp_version;
	$wp_minor_version = floatval( $wp_version );

	if( $wp_minor_version >= 5.3 ) {
		$date_now = wp_date('Y-m-d H:i:s');
	} else {
		$datetime = date_create( '@' . time() );
	    $datetime->setTimezone( get_option('timezone_string') );
		$date = $datetime->format( 'Y-m-d H:i:s' );
		$date_now = wp_maybe_decline_date( $date );
	}

	$args = array(
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'post_type' => 'mfs_slider',
		'order' => 'ASC',
		'orderby' => 'menu_order',
		'meta_query' => array(
			array(
				'relation' => 'OR',
				array(
					'key'     => 'mfs_slider_data_de_inicio',
					'value'   => $date_now,
					'compare' => '<=',
				),
				array(
		            'key'     => 'mfs_slider_data_de_inicio',
		            'compare' => '=',
					'value'   => '',
		        ),
			),
			array(
		        'relation' => 'OR',
		        array(
		            'key'   => 'mfs_slider_data_de_termino',
		            'value' => $date_now,
					'compare' => '>=',
		        ),
				array(
		            'key'     => 'mfs_slider_data_de_termino',
		            'compare' => '=',
					'value'   => '',
		        ),
		    ),
		),
	);

	if (is_array($atts)) {
		$args = array(
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'post_type' => 'mfs_slider',
			'tax_query' => array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'mfs_slider_category',
					'field' => 'slug',
					'terms' => $atts,
				),
				array(
					'taxonomy' => 'mfs_slider_category',
					'field' => 'name',
					'terms' => $atts,
				)
			)
		);
	}

	$query = new WP_Query( $args );
	if ($query->have_posts()) {
		$return_html = '<div class="mfs_slider">';
		while ($query->have_posts()) { $query->the_post();
			$imgDesk = get_post_meta(get_the_ID(), 'mfs_slider_imagem_desktop', true);
			$imgDesk = wp_get_attachment_image_src($imgDesk, 'full')[0];
			$imgMobile = get_post_meta(get_the_ID(), 'mfs_slider_imagem_mobile', true);
			$imgMobile = wp_get_attachment_image_src($imgMobile, 'full')[0];

			$return_html .= '<div class="item">';
			if (get_post_meta(get_the_ID(), 'mfs_slider_link', true) != '') :
				$return_html .= '<a href="'.get_post_meta(get_the_ID(), 'mfs_slider_link', true).'" target="_blank">';
			endif;
			$return_html .= '<picture>';
			$return_html .= '	<source media="(max-width: 799px)" srcset="'.$imgMobile.'">';
			$return_html .= '	<source media="(min-width: 800px)" srcset="'.$imgDesk.'">';
			$return_html .= '	<img src="'.$imgMobile.'" alt="" class="tns-lazy" style="height: auto; max-width: 100%;">';
			$return_html .= '</picture>';
			if (get_post_meta(get_the_ID(), 'mfs_slider_link', true) != '') :
				$return_html .= '</a>';
			endif;
			$return_html .= '</div>';
		}
		$return_html .= '</div>';

		wp_reset_postdata();
		return $return_html;
	}
}
add_shortcode('mfs_slider', 'mfs_slider_shortcode_slider');