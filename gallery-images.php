<?php
/*
 Plugin Name: Gallery Images
 Plugin URI: https://buywptemplates.com
 Description: Use to create and display gallery images.
 Author: BuyWpTemplates
 Version: 1.0
 Author URI: https://buywptemplates.com
*/

define( 'GALLERY_IMAGES_VERSION', '1.0' );
add_action( 'init', 'gallery_images_init' );
function gallery_images_init() {
	register_post_type( 'gallery', array(
		'labels' => array(
			'name'               => __( 'Gallery','gallery-images' ),
			'singular_name'      => __( 'Gallery','gallery-images' ),
			'add_new'            => __( 'Add New Gallery','gallery-images' ),
			'add_new_item'       => __( 'Add New Gallery','gallery-images' ),
			'edit_item'          => __( 'Edit Gallery', 'gallery-images' ),
			'new_item'           => __( 'New Gallery', 'gallery-images' ),
			'view_item'          => __( 'View Gallery', 'gallery-images' ),
			'search_items'       => __( 'Search Gallery', 'gallery-images' ),
			'not_found'          => __( 'No Gallery found.', 'gallery-images' ),
			'not_found_in_trash' => __( 'No Gallery found in trash.', 'gallery-images' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'Gallery', 'gallery-images' ),
			),
			'public'              => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_position'       => '',
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => array( 'title' ),
		) );
}
function gallery_images_metabox_enqueue($hook) {
	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		wp_enqueue_script('gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/js/gallery.js', array('jquery', 'jquery-ui-sortable'));
		wp_enqueue_style('gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/css/gallery.css');

		global $post;
		if ( $post ) {
			wp_enqueue_media( array(
					'post' => $post->ID,
				)
			);
		}
	}
}
add_action('admin_enqueue_scripts', 'gallery_images_metabox_enqueue');
function gallery_images_add_gallery_metabox($post_type) {
	$types = array('post', 'page', 'gallery');

	if (in_array($post_type, $types)) {
		add_meta_box(
			'gallery-image-metabox',
			__( 'Gallery Images', 'gallery-images' ),
			'gallery_images_meta_callback',
			$post_type,
			'normal',
			'high'
			);
	}
}
add_action('add_meta_boxes', 'gallery_images_add_gallery_metabox');

function gallery_images_meta_callback( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'gallery_images_meta_nonce' );
	$ids = get_post_meta( $post->ID, 'gallery_images_gal_id', true );
	?>
	<table class="form-table">
		<tr><td>
		<a class="gallery-add button" href="#" data-uploader-title="<?php esc_attr_e( 'Add image(s) to gallery', 'gallery-images' ); ?>" data-uploader-button-text="<?php esc_attr_e( 'Add image(s)', 'gallery-images' ); ?>"><?php esc_html_e( 'Add image(s)', 'gallery-images' ); ?></a>
		<ul id="gallery-images-item-list">
			<?php if ( $ids ) : foreach ( $ids as $key => $value ) : $image = wp_get_attachment_image_src( $value ); ?>
				<li>
					<input type="hidden" name="gallery_images_gal_id[<?php echo $key; ?>]" value="<?php echo $value; ?>">
					<img class="image-preview" src="<?php echo esc_url( $image[0] ); ?>">
					<a class="change-image button button-small" href="#" data-uploader-title="<?php esc_attr_e( 'Change image', 'gallery-images' ) ; ?>" data-uploader-button-text="<?php esc_attr_e( 'Change image', 'gallery-images' ) ; ?>"><?php esc_html_e( 'Change image', 'gallery-images' ) ; ?></a><br>
					<small><a class="remove-image" href="#"><?php esc_html_e( 'Remove image', 'gallery-images' ) ; ?></a></small>
				</li>
			<?php endforeach;
			endif; ?>
		</ul>
		</td></tr>
	</table>
	<?php
}
function gallery_images_meta_save($post_id) {
	if (!isset($_POST['gallery_images_meta_nonce']) || !wp_verify_nonce($_POST['gallery_images_meta_nonce'], basename(__FILE__))) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if(isset($_POST['gallery_images_gal_id'])) {
		$sanitized_values = array_map('intval', $_POST['gallery_images_gal_id']);
		update_post_meta($post_id, 'gallery_images_gal_id', $sanitized_values );
	} else {
		delete_post_meta($post_id, 'gallery_images_gal_id');
	}
}
add_action('save_post', 'gallery_images_meta_save');

function gallery_images_get_custom_post_type_template( $single_template ) {
	global $post;
	if ($post->post_type == 'gallery') {
		if ( file_exists( get_template_directory() . '/page-template/gallery.php' ) ) {
			$single_template = get_template_directory() . '/page-template/gallery.php';
		}
	}
	return $single_template;
}
add_filter( 'single_template', 'gallery_images_get_custom_post_type_template' );
/*Shortcode for Gallery*/
function gallery_images_gallery_show($gallery_id,$numberofitem) {
	add_thickbox();
	$get_post_id = isset( $gallery_id['gallery'] ) ? absint( $gallery_id['gallery'] ) : 0;
	$numberofitem = isset( $gallery_id['numberofitem'] ) ? absint( $gallery_id['numberofitem'] ) : 8;

	if ( ! $get_post_id ) {
		return;
	}
	$images = get_post_meta($get_post_id, 'gallery_images_gal_id', true);
	$res = '';
	if(empty($images)){
		$res = '<p>' . esc_html__( 'No Image Found', 'gallery-images' ) . '</p>';
	}
	else{
		$gal_i=1;
		$res .= '<div class="row">';
		foreach ($images as $image) {
			global $post;
			$thumbnail = wp_get_attachment_link($image, 'medium');
			$full = wp_get_attachment_link($image, 'large');
			//$demolink=wp_get_attachment_link($image);
			$res .= '<div class="col-md-4 col-lg-4 col-sm-4 gallery-image">
			<div class="gallery view second-effect">
				<div id="gallery-'.$gal_i.'" style="display:none;">
					'.$full.'
				</div>
				<div class="gl_img">
					'.$thumbnail.'
					
				</div>
				<div class="mask">
					<a href="#TB_inline?width=600&height=550&inlineId=gallery-'.$gal_i.'" class="info thickbox" title="'. esc_attr( get_the_title() ).'">'.esc_html( get_the_title() ).'</a>
				</div>
			</div>
			</div>';
			if($gal_i == $numberofitem) {
				break;
			}
			$gal_i++;
		}
		$res .= '</div>';
	}
	return $res;
}
add_shortcode( 'galleryshow', 'gallery_images_gallery_show' );