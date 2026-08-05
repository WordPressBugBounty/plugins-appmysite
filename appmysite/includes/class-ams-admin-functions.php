<?php
 /**
 * Plugin info.
 * @package    AppMySite
 * @author     AppMySite <support@appmysite.com>
 * @copyright  Copyright (c) 2023 - 2024, AppMySite
 * @link       https://appmysite.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
if ( !class_exists( 'AMS_Admin_Functions' ) ) {
		
	final class AMS_Admin_Functions{
		
		/**
		 * AMS_Admin_Scripts Constructor.
		 **/
		
		function __construct() {
			
			$this->ams_plugin_deactivation_survey();

			add_action( 'admin_menu', array( &$this, 'ams_admin_menu' ) );
			
			//add_action( 'wp_ajax_ams_license_key_form_submit', array( &$this, 'ams_license_key_form_submit' ) ); 
			//commented this function to hook with wp_ajax because connection to V2 was resulting as forbidden in simple ajax call, so implemented it as jquery ajax call in save_ams_license_key
			
			add_action( 'wp_ajax_ams_safe_mode_form_submit', array( &$this, 'ams_safe_mode_form_submit' ) );

			add_action('wp_ajax_save_ams_license_key', array( &$this, 'save_ams_license_key' ) );
			
		}
		
		function ams_plugin_deactivation_survey(){
			
			require_once untrailingslashit( dirname( AMS_PLUGIN_DIR )) . '/includes/ams-plugin-deactivation-survey.php';
				
			add_action( 'wp_ajax_ams_deactivation_form_submit', 'ams_deactivation_form_submit' );
			
		}

		// adds ams menu item to wordpress admin dashboard
		function ams_admin_menu() {
			
			add_menu_page( __( 'AppMySite Dashboard' ),
			__( 'AppMySite' ),
			'manage_options',
			'ams-home',
			array( &$this, 'ams_admin_menu_page' ),
			plugins_url( 'assets/images/ams-side-menu-icon.svg', AMS_PLUGIN_DIR )
			);
			
		}

		function ams_admin_menu_page() {
			// Load home page
			require_once untrailingslashit( dirname( AMS_PLUGIN_DIR )) . '/includes/views/ams-home.php'; 
		}

		function save_ams_license_key() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'is_valid' => 'no',
						'msg'      => 'Unauthorized.',
					)
				);
			}

			if ( ! check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error(
					array(
						'is_valid' => 'no',
						'msg'      => 'Invalid security token.',
					)
				);
			}

			// Get the license key from the POST request
			if (isset($_POST['ams_license_key'])) {
				$ams_license_key = sanitize_text_field($_POST['ams_license_key']);
				
				// Validate the license key before saving (you can add custom validation here)
				if (strlen($ams_license_key) > 28 && strlen($ams_license_key) <= 34) {
					try {
						$config_transformer = new WPConfigTransformer($this->get_config_path());
						$config_param = $this->get_config_args(array('normalize' => true));
		
						// Check if constant exists, then update or add the constant in wp-config.php
						if ($config_transformer->exists('constant', 'AMS_LICENSE_KEY')) {
							$config_transformer->update('constant', 'AMS_LICENSE_KEY', $ams_license_key, $config_param);
							$config_transformer->update('constant', 'AMS_LICENSE_STATUS', 'Verified', $config_param);
						} else {
							$config_transformer->update('constant', 'AMS_LICENSE_KEY', $ams_license_key, $config_param);
							$config_transformer->update('constant', 'AMS_LICENSE_STATUS', 'Verified', $config_param);
						}
		
						wp_send_json_success(array(
							'is_valid' => "yes",
							'msg' => 'License key saved successfully.'
						));
					} catch (\Exception $e) {
						$message = 'Unable to update AMS_LICENSE_KEY in wp-config. ' . $e->getMessage();
						wp_send_json_error(array(
							'is_valid' => "no",
							'msg' => $message
						));
					}
				} else {
					wp_send_json_error(array(
						'is_valid' => "no",
						'msg' => 'Invalid license key format.'
					));
				}
			} else {
				wp_send_json_error(array(
					'is_valid' => "no",
					'msg' => 'License key not provided.'
				));
			}
		
			wp_die(); // Always call this to terminate the request properly
		}
		
		
		/*function ams_license_key_form_submit() { 

			if ( ! check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error();
				wp_die();
			}			
			$form_data   =  wp_parse_args( $_POST['form-data'] ) ; // clean
			
			$ams_license_key = sanitize_text_field($form_data['ams_license_key']);
			$site_url = esc_url( get_bloginfo( 'url' ) );
			$is_valid_ams_license_key = false;*/
						
			/**
			 * Make a POST request to verify the token.
			 */
			/*$response    = wp_remote_post(
				'https://wordpress.api.appmysite.com/api/verify-license',
				array(
					'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
					'body' => json_encode(array(
						'ams_license_key'  => $ams_license_key
					))
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$response_body = wp_remote_retrieve_body( $response );
				$response_code = wp_remote_retrieve_response_code($response);
				if($response_code == 200){
					$json_response = json_decode($response_body, true);
					if(isset($json_response['is_valid'])&& $json_response['is_valid']=="yes"){		//Valid license				
						/*  Logic to modify wp-config file  */
						/*try {
							$config_transformer = new WPConfigTransformer( $this->get_config_path() );
							
							$config_param = $this->get_config_args(array( 'normalize' => true ));
							
							if ( $config_transformer->exists( 'constant', 'AMS_LICENSE_KEY' ) ) {
								// update constant
								$config_transformer->update( 'constant', 'AMS_LICENSE_KEY', $ams_license_key, $config_param ); //'raw' => true
								$config_transformer->update( 'constant', 'AMS_LICENSE_STATUS', 'Verified', $config_param );
							}
							else{
								// add constant
								$config_transformer->update( 'constant', 'AMS_LICENSE_KEY', $ams_license_key, $config_param ); //'raw' => true
								$config_transformer->update( 'constant', 'AMS_LICENSE_STATUS', 'Verified', $config_param );
							}
							
							wp_send_json_success(
								array(
									'is_valid' => "yes",
									'msg' =>'License key saved successfully.'
								)
							);
							wp_die();
							
						} catch ( \Exception $e ) {
							$messsage = 'Unable to update AMS_LICENSE_KEY in wp-config. ' . $e->getMessage();
							
							wp_send_json_success(
								array(
									'is_valid' => "no",
									'msg'	=> $messsage
								)
							);
							wp_die();
						}
						
					}else{	//Invalid license
						try {
							$config_transformer = new WPConfigTransformer( $this->get_config_path() );
							
							$config_param = $this->get_config_args(array( 'normalize' => true ));
							
							// update constant
							$config_transformer->update( 'constant', 'AMS_LICENSE_KEY', $ams_license_key, $config_param ); //'raw' => true
							$config_transformer->update( 'constant', 'AMS_LICENSE_STATUS', 'Unverified', $config_param );
							
							wp_send_json_success(
									array(
										'is_valid' => "no",
										'msg' =>'Invalid license key.'
									)
								);
							wp_die();
							
						} catch ( \Exception $e ) {
							$messsage = 'Unable to update AMS_LICENSE_KEY in wp-config. ' . $e->getMessage();
							
							wp_send_json_success(
								array(
									'is_valid' => "no",
									'msg'	=> $messsage
								)
							);
							wp_die();
						}
						
					}
					
				}else{
					wp_send_json_success(
						array(
							'is_valid' => "no",
							'msg' =>'Could not verify license with AppMySite.'.$response_body.''
						)
					);
					wp_die();
				}
			} else {
				wp_send_json_success(
					array(
						'is_valid' => "no",
						'msg' =>$response->get_error_message()
					)
				);
				wp_die();
			}
			
		}*/

		function ams_safe_mode_form_submit() {

			if ( ! check_ajax_referer( 'ajax-nonce', 'nonce', false ) ) {
				wp_send_json_error();
				wp_die();
			}

			$form_data = array();
			if ( isset( $_POST['form-data'] ) && is_array( $_POST['form-data'] ) ) {
				$form_data = wp_parse_args( wp_unslash( $_POST['form-data'] ) );
			}

			if ( empty( $form_data['ams_safe_mode'] ) ) {
				wp_send_json_error(
					array(
						'ams_safe_mode' => ams_get_safe_mode_value(),
						'msg'           => 'Invalid safe mode request.',
					)
				);
			}

			$ams_safe_mode = sanitize_text_field( $form_data['ams_safe_mode'] );

			if ( ! in_array( $ams_safe_mode, array( 'on', 'off' ), true ) ) {
				wp_send_json_error(
					array(
						'ams_safe_mode' => ams_get_safe_mode_value(),
						'msg'           => 'Invalid safe mode value.',
					)
				);
			}

			if ( ! function_exists( 'ams_has_default_theme' ) || ! ams_has_default_theme() ) {
				wp_send_json_error(
					array(
						'ams_safe_mode' => ams_get_safe_mode_value(),
						'msg'           => 'No default WordPress theme found on your website. Please install at least one default theme to enable safe mode.',
					)
				);
				wp_die();
			}

			/*  Logic to modify wp-config file  */
			try {
				$config_transformer = new WPConfigTransformer( $this->get_config_path() );

				$config_param = $this->get_config_args( array( 'normalize' => true ) );

				$config_transformer->update( 'constant', 'AMS_SAFE_MODE', $ams_safe_mode, $config_param );

				if ( 'on' === $ams_safe_mode ) {
					wp_send_json_success(
						array(
							'ams_safe_mode' => $ams_safe_mode,
							'msg'           => 'Safe mode has been activated successfully. We recommend not leaving it on for an extended period.',
						)
					);
				} else {
					wp_send_json_success(
						array(
							'ams_safe_mode' => $ams_safe_mode,
							'msg'           => 'Safe mode has been deactivated successfully.',
						)
					);
				}
			} catch ( \Throwable $e ) {
				$messsage = 'Unable to update AMS_SAFE_MODE in wp-config.  ' . $e->getMessage();

				wp_send_json_error(
					array(
						'ams_safe_mode' => ams_get_safe_mode_value(),
						'msg'           => $messsage,
					)
				);
			}

							
		}

		
		private function get_config_path() {
			$config_path = ABSPATH . 'wp-config.php';

			if ( ! file_exists( $config_path ) ) {
				if ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
					$config_path = dirname( ABSPATH ) . '/wp-config.php';
				}
			}

			return apply_filters( 'wp_debugging_config_path', $config_path );
		}

		private function get_config_args($config_args){
			$config_contents = @file_get_contents( $this->get_config_path() );
			if ( false === $config_contents ) {
				return $config_args;
			}

			if ( false === strpos( $config_contents, "/* That's all, stop editing!" ) ) {
				if ( 1 === preg_match( '@\$table_prefix(.*;)@', $config_contents, $matches ) ) {
					$config_args = array_merge(
						$config_args,
						[
							'anchor'    => "$matches[0]",
							'placement' => 'after',
						]
					);
				}
			}
			return $config_args;
		}
		
	}

}

