<?php
 /**
 * Plugin info.
 * @package    AppMySite
 * @author     AppMySite <support@appmysite.com>
 * @copyright  Copyright (c) 2023 - 2024, AppMySite
 * @link       https://appmysite.com
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
	$appmysite_safemode_loader = new WPAMSSafeModeLoader();
	
	class WPAMSSafeModeLoader
	{
		function __construct()
		{
			// Only do this if safe mode is activated by querystring.
			if(isset($_GET['safe_mode']) && $_GET['safe_mode'] == '1')
			{
				add_filter('template', array($this, 'ams_disable_theme'), 10, 1);
				add_filter('stylesheet', array($this, 'ams_disable_theme'), 10, 1);
				add_filter('option_active_plugins', array($this, 'ams_disable_plugins'), 10, 1);
				add_filter('plugin_action_links', array($this, 'ams_plugin_links'), 10, 4);
			}
		}
		
		function ams_disable_plugins($plugins)
		{
			// Returning an empty array will instruct the loader not to load any plugins at all.
			
			// HOWEVER, if you need to keep certain plugins activated, you can do this very easily.
			// Just modify the array below to contain the file names of the plugins you need to stay active.
			// Example: plugin file is located in "some-plugin/some-plugin.php"
			// return array('some-plugin/some-plugin.php');
			
			return array();
		}
		
		function ams_disable_theme( $theme ) {
			if ( defined( 'WP_DEFAULT_THEME' ) ) {
				$default_theme = wp_get_theme( WP_DEFAULT_THEME );
				if ( $default_theme->exists() ) {
					return WP_DEFAULT_THEME;
				}
			}

			$fallback_slugs = array(
				'twentytwentyfive',
				'twentytwentyfour',
				'twentytwentythree',
				'twentytwentytwo',
				'twentytwentyone',
				'twentytwenty',
				'twentynineteen',
				'twentyeighteen',
				'twentyseventeen',
				'twentysixteen',
				'twentyfifteen',
				'twentyfourteen',
				'twentythirteen',
				'twentytwelve',
				'twentyeleven',
				'twentyten',
			);

			$themes = wp_get_themes();
			foreach ( $fallback_slugs as $slug ) {
				if ( array_key_exists( $slug, $themes ) ) {
					return $slug;
				}
			}

			return $theme;
		}
		
		function ams_plugin_links($actions, $plugin_file, $plugin_data, $context)
		{
			// Make sure all plugins can be deactivated in safe mode.
			
			if($plugin_file != 'appmysite/appmysite.php')
			{			
				$page = 1;
				$actions['deactivate'] = '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page 
				, 'deactivate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Deactivate this plugin') . ' (safe mode)' . '">' . __('Deactivate') . ' (safe mode)' . '</a>';
			}
			
			return $actions;
		}
	}
?>