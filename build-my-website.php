<?php
/**
 * Plugin Name: Build my Website
 * Description: Interviews the user and generates a website for them.
 * Version: 1.0
 * Author: Alex Kirk
 */

defined('ABSPATH') or exit;

class ChatGPT_Agency_Plugin {
	public function __construct() {
		add_action('template_include', array( $this, 'load_fullscreen_template' ) );
		add_action('wp_ajax_upload_files', array( $this, 'upload_files' ) );
		add_action('wp_ajax_get_gutenberg_patterns', array( $this, 'get_gutenberg_patterns' ) );
		add_action('wp_ajax_wp_insert_post', array( $this, 'wp_insert_post' ) );
		add_action('admin_bar_menu', array( $this, 'add_to_admin_bar' ), 1000);
	}

	public function load_fullscreen_template($template) {
		global $wp_query;
		if ( 'build' === $wp_query->get('pagename') ) {
			add_filter( 'pre_handle_404', '__return_true' );
			status_header(200);
			$wp_query->is_404 = false;
			$wp_query->is_page = true;
			$wp_query->is_singular = true;
			$wp_query->set('pagename', 'build');

			return dirname(__FILE__) . '/chat.php';
		}
		return $template;
	}

	public function add_to_admin_bar($wp_admin_bar) {
		$wp_admin_bar->add_node(array(
			'id' => 'build-my-website',
			'title' => 'Build my Website',
			'href' => site_url('/build/'),
		));
		if ( '/build/' === $_SERVER['REQUEST_URI'] ) {
			$wp_admin_bar->remove_node('comments');
			$wp_admin_bar->remove_node('new-content');
		}
	}

	public function upload_files() {
		if (!empty($_FILES['files'])) {
			foreach ($_FILES['files']['name'] as $key => $value) {
				if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
					$file = [
						'name' => $_FILES['files']['name'][$key],
						'type' => $_FILES['files']['type'][$key],
						'tmp_name' => $_FILES['files']['tmp_name'][$key],
						'error' => $_FILES['files']['error'][$key],
						'size' => $_FILES['files']['size'][$key]
					];

					$upload_id = wp_handle_upload($file, ['test_form' => false]);
					if (isset($upload_id['file'])) {
						// The file is successfully uploaded to the upload directory
						$filename = $upload_id['file'];
						$wp_filetype = wp_check_filetype(basename($filename), null);
						$attachment = [
							'guid' => $upload_id['url'],
							'post_mime_type' => $wp_filetype['type'],
							'post_title' => sanitize_file_name($value),
							'post_content' => '',
							'post_status' => 'inherit',
						];

						// Insert the attachment in the WordPress media library
						$attach_id = wp_insert_attachment($attachment, $filename);
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
						wp_update_attachment_metadata($attach_id, $attach_data);
					}
				}
			}
			wp_send_json_success();
		}
		wp_send_json_error();
	}
	public function get_gutenberg_patterns() {
		$theme = $_GET['theme'];

		$theme = wp_get_theme( $theme );
		$patterns = array();
		foreach ( $theme->get_block_patterns() as $slug => $pattern ) {
			ob_start();
			require $theme->get_stylesheet_directory() . '/patterns/' . $slug;
			$patterns[ $slug ] = ob_get_clean();
		}
		return $patterns;
	}

	public function wp_insert_post() {
		$post = array(
			'post_title' => $_POST['post_title'],
			'post_content' => $_POST['post_content'],
			'post_status' => 'publish',
			'post_type' => $_POST['post_type'],
		);
		$post_id = wp_insert_post($post);
		if ($post_id) {
			wp_send_json_success($post_id);
		}
		wp_send_json_error();
	}

}

new ChatGPT_Agency_Plugin();
