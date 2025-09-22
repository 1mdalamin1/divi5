<?php
/**
 * Google Drive API endpoints using Google Client Library.
 *
 * @link          https://wpmudev.com/
 * @since         1.0.0
 *
 * @author        WPMUDEV (https://wpmudev.com)
 * @package       WPMUDEV\PluginTest
 *
 * @copyright (c) 2025, Incsub (http://incsub.com)
 */

namespace WPMUDEV\PluginTest\Endpoints\V1;

// Abort if called directly.
defined( 'WPINC' ) || die;

use WPMUDEV\PluginTest\Base;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class Drive_API extends Base {

	/**
	 * Google Client instance.
	 *
	 * @var Google_Client
	 */
	private $client;

	/**
	 * Google Drive service.
	 *
	 * @var Google_Service_Drive
	 */
	private $drive_service;

	/**
	 * OAuth redirect URI.
	 *
	 * @var string
	 */
	private $redirect_uri;

	/**
	 * Google Drive API scopes.
	 *
	 * @var array
	 */
	private $scopes = array(
		Google_Service_Drive::DRIVE_FILE,
		Google_Service_Drive::DRIVE_READONLY,
	);

	/**
	 * Initialize the class.
	 */
	public function init() {
		$this->redirect_uri = home_url( '/wp-json/wpmudev/v1/drive/callback' );
		$this->setup_google_client();

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Setup Google Client.
	 */
	private function setup_google_client() {
		$auth_creds = get_option( 'wpmudev_plugin_tests_auth', array() );
		
		if ( empty( $auth_creds['client_id'] ) || empty( $auth_creds['client_secret'] ) ) {
			return;
		}

		$this->client = new Google_Client();
		$this->client->setClientId( $auth_creds['client_id'] );
		$this->client->setClientSecret( $auth_creds['client_secret'] );
		$this->client->setRedirectUri( $this->redirect_uri );
		$this->client->setScopes( $this->scopes );
		$this->client->setAccessType( 'offline' );
		$this->client->setPrompt( 'consent' );

		// Set access token if available
		$access_token = get_option( 'wpmudev_drive_access_token', '' );
		if ( ! empty( $access_token ) ) {
			$this->client->setAccessToken( $access_token );
		}

		$this->drive_service = new Google_Service_Drive( $this->client );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		// Save credentials endpoint
		register_rest_route( 'wpmudev/v1/drive', '/save-credentials', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_credentials' ),
		) );

		// Authentication endpoint
		register_rest_route( 'wpmudev/v1/drive', '/auth', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'start_auth' ),
		) );

		// OAuth callback
		register_rest_route( 'wpmudev/v1/drive', '/callback', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_callback' ),
		) );

		// List files
		register_rest_route( 'wpmudev/v1/drive', '/files', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'list_files' ),
		) );

		// Upload file
		register_rest_route( 'wpmudev/v1/drive', '/upload', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'upload_file' ),
		) );

		// Download file
		register_rest_route( 'wpmudev/v1/drive', '/download', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'download_file' ),
		) );

		// Create folder
		register_rest_route( 'wpmudev/v1/drive', '/create-folder', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_folder' ),
		) );
	}

	/**
	 * Save Google OAuth credentials.
	 */
	public function save_credentials( WP_REST_Request $request ) {
		// Permission check: Only allow users with 'manage_options' capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to perform this action.', array( 'status' => 403 ) );
		}

		// Validate and sanitize input
		$client_id     = $request->get_param( 'client_id' );
		$client_secret = $request->get_param( 'client_secret' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return new WP_Error( 'missing_params', 'Both client_id and client_secret are required.', array( 'status' => 400 ) );
		}

		$client_id     = sanitize_text_field( $client_id );
		$client_secret = sanitize_text_field( $client_secret );

		// Save credentials securely
		$credentials = array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		update_option( 'wpmudev_plugin_tests_auth', $credentials );

		// Remove any old tokens when credentials change
		delete_option( 'wpmudev_drive_access_token' );
		delete_option( 'wpmudev_drive_refresh_token' );
		delete_option( 'wpmudev_drive_token_expires' );

		// Reinitialize Google Client with new credentials
		$this->setup_google_client();

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Credentials saved successfully.',
		), 200 );
	}

	/**
	 * Start Google OAuth flow.
	 */
	public function start_auth( WP_REST_Request $request ) {
		if ( ! $this->client ) {
			return new WP_Error( 'missing_credentials', 'Google OAuth credentials not configured', array( 'status' => 400 ) );
		}

		// Generate the Google OAuth URL
		$auth_url = $this->client->createAuthUrl();

		return new WP_REST_Response( array(
			'success'  => true,
			'auth_url' => $auth_url,
		), 200 );
	}

	/**
	 * Handle OAuth callback.
	 */
	public function handle_callback( WP_REST_Request $request ) {
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
		$state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

		if ( empty( $code ) ) {
			wp_die( 'Authorization code not received' );
		}

		try {
			// Exchange code for access token
			$access_token = $this->client->fetchAccessTokenWithAuthCode( $code );

			if ( isset( $access_token['error'] ) ) {
				wp_die( 'Failed to get access token: ' . esc_html( $access_token['error_description'] ?? $access_token['error'] ) );
			}

			// Store tokens
			update_option( 'wpmudev_drive_access_token', $access_token );
			if ( isset( $access_token['refresh_token'] ) ) {
				update_option( 'wpmudev_drive_refresh_token', $access_token['refresh_token'] );
			}
			update_option( 'wpmudev_drive_token_expires', time() + (int) $access_token['expires_in'] );

			// Redirect back to admin page
			wp_redirect( admin_url( 'admin.php?page=wpmudev_plugintest_drive&auth=success' ) );
			exit;

		} catch ( \Exception $e ) {
			wp_die( 'Failed to get access token: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Ensure we have a valid access token.
	 */
	private function ensure_valid_token() {
		if ( ! $this->client ) {
			return false;
		}

		$access_token = get_option( 'wpmudev_drive_access_token', '' );
		if ( ! empty( $access_token ) ) {
			$this->client->setAccessToken( $access_token );
		}

		// Check if token is expired and refresh if needed
		if ( $this->client->isAccessTokenExpired() ) {
			$refresh_token = get_option( 'wpmudev_drive_refresh_token' );
			if ( empty( $refresh_token ) ) {
				return false;
			}

			try {
				$new_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
				if ( array_key_exists( 'error', $new_token ) ) {
					return false;
				}
				update_option( 'wpmudev_drive_access_token', $new_token );
				update_option( 'wpmudev_drive_token_expires', time() + (int) $new_token['expires_in'] );
				$this->client->setAccessToken( $new_token );
				return true;
			} catch ( \Exception $e ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * List files in Google Drive with pagination.
	 */
	public function list_files( WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new WP_Error( 'no_access_token', 'Not authenticated with Google Drive', array( 'status' => 401 ) );
		}

		$page_size = intval( $request->get_param( 'page_size' ) ) ?: 20;
		$page_token = $request->get_param( 'page_token' );
		$query = $request->get_param( 'q' ) ?: 'trashed=false';

		try {
			$options = array(
				'pageSize' => $page_size,
				'q'        => $query,
				'fields'   => 'nextPageToken,files(id,name,mimeType,size,modifiedTime,webViewLink)',
			);
			if ( $page_token ) {
				$options['pageToken'] = $page_token;
			}

			$results = $this->drive_service->files->listFiles( $options );
			$files   = $results->getFiles();

			$file_list = array();
			foreach ( $files as $file ) {
				$file_list[] = array(
					'id'           => $file->getId(),
					'name'         => $file->getName(),
					'mimeType'     => $file->getMimeType(),
					'size'         => $file->getSize(),
					'modifiedTime' => $file->getModifiedTime(),
					'webViewLink'  => $file->getWebViewLink(),
				);
			}

			return new WP_REST_Response( array(
				'success'        => true,
				'files'          => $file_list,
				'nextPageToken'  => $results->getNextPageToken(),
			), 200 );

		} catch ( \Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Upload file to Google Drive.
	 */
	public function upload_file( WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new WP_Error( 'no_access_token', 'Not authenticated with Google Drive', array( 'status' => 401 ) );
		}

		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'no_file', 'No file provided', array( 'status' => 400 ) );
		}

		$file = $files['file'];
		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'upload_error', 'File upload error', array( 'status' => 400 ) );
		}

		// Validate file type and size (example: max 10MB, allow common types)
		$allowed_types = array(
			'application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'application/zip',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
			'application/msword', // doc
		);
		$max_size = 10 * 1024 * 1024; // 10MB

		if ( ! in_array( $file['type'], $allowed_types ) ) {
			return new WP_Error( 'invalid_type', 'File type not allowed', array( 'status' => 400 ) );
		}
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', 'File size exceeds limit', array( 'status' => 400 ) );
		}

		try {
			$drive_file = new Google_Service_Drive_DriveFile();
			$drive_file->setName( $file['name'] );

			$result = $this->drive_service->files->create(
				$drive_file,
				array(
					'data'       => file_get_contents( $file['tmp_name'] ),
					'mimeType'   => $file['type'],
					'uploadType' => 'multipart',
					'fields'     => 'id,name,mimeType,size,webViewLink',
				)
			);

			return new WP_REST_Response( array(
				'success' => true,
				'file'    => array(
					'id'          => $result->getId(),
					'name'        => $result->getName(),
					'mimeType'    => $result->getMimeType(),
					'size'        => $result->getSize(),
					'webViewLink' => $result->getWebViewLink(),
				),
			), 200 );

		} catch ( \Exception $e ) {
			return new WP_Error( 'upload_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Download file from Google Drive.
	 */
	public function download_file( WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new WP_Error( 'no_access_token', 'Not authenticated with Google Drive', array( 'status' => 401 ) );
		}

		$file_id = $request->get_param( 'file_id' );
		
		if ( empty( $file_id ) ) {
			return new WP_Error( 'missing_file_id', 'File ID is required', array( 'status' => 400 ) );
		}

		try {
			// Get file metadata
			$file = $this->drive_service->files->get( $file_id, array(
				'fields' => 'id,name,mimeType,size',
			) );

			// Download file content
			$response = $this->drive_service->files->get( $file_id, array(
				'alt' => 'media',
			) );

			$content = $response->getBody()->getContents();

			// Return file content as base64 for JSON response
			return new WP_REST_Response( array(
				'success'  => true,
				'content'  => base64_encode( $content ),
				'filename' => $file->getName(),
				'mimeType' => $file->getMimeType(),
			) );

		} catch ( Exception $e ) {
			return new WP_Error( 'download_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Create folder in Google Drive.
	 */
	public function create_folder( WP_REST_Request $request ) {
		if ( ! $this->ensure_valid_token() ) {
			return new WP_Error( 'no_access_token', 'Not authenticated with Google Drive', array( 'status' => 401 ) );
		}

		$name = $request->get_param( 'name' );
		
		if ( empty( $name ) ) {
			return new WP_Error( 'missing_name', 'Folder name is required', array( 'status' => 400 ) );
		}

		try {
			$folder = new Google_Service_Drive_DriveFile();
			$folder->setName( sanitize_text_field( $name ) );
			$folder->setMimeType( 'application/vnd.google-apps.folder' );

			$result = $this->drive_service->files->create( $folder, array(
				'fields' => 'id,name,mimeType,webViewLink',
			) );

			return new WP_REST_Response( array(
				'success' => true,
				'folder'  => array(
					'id'          => $result->getId(),
					'name'        => $result->getName(),
					'mimeType'    => $result->getMimeType(),
					'webViewLink' => $result->getWebViewLink(),
				),
			) );

		} catch ( Exception $e ) {
			return new WP_Error( 'create_failed', $e->getMessage(), array( 'status' => 500 ) );
		}
	}
}

// At the end of the file, outside the class:
// add_action( 'plugins_loaded', function() {
// 	if ( class_exists( '\WPMUDEV\PluginTest\Endpoints\V1\Drive_API' ) ) {
// 		$GLOBALS['wpmudev_drive_api'] = new \WPMUDEV\PluginTest\Endpoints\V1\Drive_API();
// 		$GLOBALS['wpmudev_drive_api']->init();
// 	}
// } );