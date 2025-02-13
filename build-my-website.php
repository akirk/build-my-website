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
	}

	public function load_fullscreen_template($template) {
		if ( '/build/' === $_SERVER['REQUEST_URI'] ) {
			status_header(200);
			return dirname(__FILE__) . '/chat.php';
		}
		return $template;
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
		return $theme->get_block_patterns();


	}
}

new ChatGPT_Agency_Plugin();
