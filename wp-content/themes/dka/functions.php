<?php
/**
 * @package WordPress
 * @subpackage DKA
 */

require('wp-bootstrap-navwalker/wp_bootstrap_navwalker.php');

function dka_setup() {

	add_editor_style();

	add_theme_support( 'automatic-feed-links' );

	//add_theme_support( 'post-formats', array( 'aside', 'image', 'link', 'quote', 'status' ) );

	register_nav_menu( 'primary','Primary');
	register_nav_menu( 'secondary','Secondary');

	remove_action( 'wp_head','rsd_link',10);
	remove_action( 'wp_head','wlwmanifest_link',10);

	//add_theme_support( 'post-thumbnails' );
	//set_post_thumbnail_size( 624, 9999 ); // Unlimited height, soft crop
	add_filter('wp_mail_from', function($old) { return get_bloginfo('admin_email'); });
	add_filter('wp_mail_from_name', function($old) { return get_bloginfo('name'); });
}
add_action( 'after_setup_theme', 'dka_setup' );

function dka_scripts_styles() {

	wp_enqueue_style( 'dka-style', get_template_directory_uri() . '/css/styles.css' );

	wp_enqueue_style( 'mediaelementplayer', get_template_directory_uri() . '/css/mediaelementplayer.css' );

	wp_dequeue_script('jquery');
	wp_enqueue_script( 'jquery', get_template_directory_uri() . '/js/jquery-1.10.1.min.js', array(), '1.10.1', true );

	wp_enqueue_script( 'mediaelementplayer', get_template_directory_uri() . '/js/mediaelement-and-player.min.js', array(), '4.1', true );

	wp_enqueue_script( 'custom-functions', get_template_directory_uri() . '/js/custom-functions.js', array('jquery'), '1', true );

	$bootstrap_scripts = array(
		'transition',
		'alert',
		'button',
		'carousel',
		'collapse',
		'dropdown',
		'modal',
		'scrollspy',
		'tab',
		'tooltip',
		'popover',
		'affix'
	);
	foreach($bootstrap_scripts as $bootscript) {
		wp_enqueue_script($bootscript, get_template_directory_uri() . '/js/bootstrap/'.$bootscript.'.js', array(), '3.0.0', true );
	}


}
add_action( 'wp_enqueue_scripts', 'dka_scripts_styles' );

function dka_footer_scripts() {
	echo '<script type="text/javascript">
    jQuery("video, audio").mediaelementplayer();
</script>';
}
add_action('wp_footer','dka_footer_scripts',99);

function dka_widgets_init() {

	register_sidebar( array(
		'id' => 'sidebar-1',
		'name' => 'Sidebar',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'id' => 'sidebar-2',
		'name' => 'Top',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	//Remove some widgets not needed that clutter the screen
	unregister_widget('WP_Widget_Pages');
	unregister_widget('WP_Widget_Calendar');
	unregister_widget('WP_Widget_Archives');
	unregister_widget('WP_Widget_Links');
	unregister_widget('WP_Widget_Meta');
	//unregister_widget('WP_Widget_Search');
	//unregister_widget('WP_Widget_Text');
	unregister_widget('WP_Widget_Categories');
	//unregister_widget('WP_Widget_Recent_Posts');
	unregister_widget('WP_Widget_Recent_Comments');
	unregister_widget('WP_Widget_RSS');
	unregister_widget('WP_Widget_Tag_Cloud');
	//unregister_widget('WP_Nav_Menu_Widget');
}

add_action( 'widgets_init', 'dka_widgets_init' );

function dka_content_nav( $html_id ) {
	global $wp_query;

	$html_id = esc_attr( $html_id );

	if ( $wp_query->max_num_pages > 1 ) : ?>
		<nav id="<?php echo $html_id; ?>" class="navigation" role="navigation">
			<h3 class="assistive-text"><?php _e( 'Post navigation', 'dka' ); ?></h3>
			<div class="nav-previous alignleft"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'dka' ) ); ?></div>
			<div class="nav-next alignright"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'dka' ) ); ?></div>
		</nav><!-- #<?php echo $html_id; ?> .navigation -->
	<?php endif;
}

if ( ! function_exists( 'dka_entry_meta' ) ) :
/**
 * Prints HTML with meta information for current post: categories, tags, permalink, author, and date.
 *
 * Create your own dka_entry_meta() to override in a child theme.
 *
 * @since Twenty Twelve 1.0
 */
function dka_entry_meta() {
	// Translators: used between list items, there is a space after the comma.
	$categories_list = get_the_category_list( __( ', ', 'dka' ) );

	// Translators: used between list items, there is a space after the comma.
	$tag_list = get_the_tag_list( '', __( ', ', 'dka' ) );

	$date = sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a>',
		esc_url( get_permalink() ),
		esc_attr( get_the_time() ),
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);

	$author = sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a></span>',
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_attr( sprintf( __( 'View all posts by %s', 'dka' ), get_the_author() ) ),
		get_the_author()
	);

	// Translators: 1 is category, 2 is tag, 3 is the date and 4 is the author's name.
	if ( $tag_list ) {
		$utility_text = __( 'This entry was posted in %1$s and tagged %2$s on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	} elseif ( $categories_list ) {
		$utility_text = __( 'This entry was posted in %1$s on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	} else {
		$utility_text = __( 'This entry was posted on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	}

	printf(
		$utility_text,
		$categories_list,
		$tag_list,
		$date,
		$author
	);
}
endif;

function dka_wp_title( $title, $sep ) {
	global $paged, $page;

	if ( is_feed() )
		return $title;

	// Add the site name.
	$title .= get_bloginfo( 'name' );

	// Add the site description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title = "$title $sep $site_description";

	//Add title from CHAOS object
	if(WPChaosClient::get_object()) {
		$title = WPChaosClient::get_object()->title. " $sep $title";
	}

	// Add a page number if necessary.
	if ( $paged >= 2 || $page >= 2 )
		$title = "$title $sep " . sprintf( __( 'Page %s', 'dka' ), max( $paged, $page ) );

	return $title;
}
add_filter( 'wp_title', 'dka_wp_title', 10, 2 );


function dka_wp_head() {	

	$metadatas = array();

	if(is_singular()) {
		global $post;
		setup_postdata($post);
		
		$excerpt = dka_custom_excerpt(20);
		$metadatas['description'] = array(
			'name' => 'description',
			'content' => $excerpt
		);
		$metadatas['og:description'] = array(
			'name' => 'og:description',
			'property' => $excerpt
		);

		wp_reset_postdata();
	}

	if(WPChaosClient::get_object()) {
		$metadatas['description'] = array(
			'name' => 'description',
			'content' => WPChaosClient::get_object()->description
		);
		$metadatas['og:description'] = array(
			'property' => 'og:description',
			'content' => WPChaosClient::get_object()->description
		);
		$metadatas['og:title'] = array(
			'property' => 'og:title',
			'content' => WPChaosClient::get_object()->title
		);
		$metadatas['og:type'] = array(
			'property' => 'og:type',
			'content' => WPChaosClient::get_object()->type
		);
		$metadatas['og:url'] = array(
			'property' => 'og:url',
			'content' => WPChaosClient::get_object()->url
		);
		$metadatas['og:image'] = array(
			'property' => 'og:image',
			'content' => WPChaosClient::get_object()->thumbnail
		);
	}

	$metadatas['og:site_name'] = array(
			'property' => 'og:site_name',
			'content' => get_bloginfo('title')
	);

	$metadatas = apply_filters('wpchaos-head-meta',$metadatas);

	//Loop over metadata
	foreach($metadatas as $metadata) {
		$fields = array();
		//Loop over each metadata attribute
		foreach($metadata as $key => $value) {
			$fields[] = $key.'="'.esc_attr($value).'"';
		}
		//Insert attributes in meta node and print
		echo "<meta ".implode(" ", $fields).">\n";
	}
	
}

add_action('wp_head','dka_wp_head',99);

function dka_custom_excerpt($new_length = 20) {
  add_filter('excerpt_length', create_function('$new_length',"return $new_length;"), 999);
  $output = get_the_excerpt();
  return $output;
}

/*function dka_sanitize_title($title, $raw_title, $context) {

	$replacements = array(
		'æ' => 'cab9d5d0-f843-11e2-b778-0800200c9a66',
		'Æ' => 'cab9d5d1-f843-11e2-b778-0800200c9a66',
		'Ø' => 'cab9d5d2-f843-11e2-b778-0800200c9a66',
		'ø' => 'cab9d5d3-f843-11e2-b778-0800200c9a66',
		'Å' => 'cab9d5d4-f843-11e2-b778-0800200c9a66',
		'å' => 'cab9d5d5-f843-11e2-b778-0800200c9a66'
	);

    if ( 'save' == $context ) {
    	$title = $raw_title;
    	$title = str_replace(array_keys($replacements), $replacements, $title);
    	$title = remove_accents($title);
    	$title = str_replace($replacements, array_keys($replacements), $title);
    }
    return $title;
}
add_filter('sanitize_title', 'dka_sanitize_title', 10, 3);

function dka_redirect($location, $status) {
	$replacements = array(
		'æ' => '%C3%86',
		'Æ' => '%C3%A6',
		'Ø' => '%C3%98',
		'ø' => '%C3%B8',
		'Å' => '%C3%85',
		'å' => '%C3%A5'
	);
	$location = str_replace(array_keys($replacements), $replacements, $location);
	return $location;
}

add_filter('wp_redirect','dka_redirect',10,2);*/

//eol