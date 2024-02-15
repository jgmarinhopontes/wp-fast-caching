<?php
/*
Plugin Name: Fast Caching
Description: The fastest caching plugin for WordPress!
Version: 4.0.2
Author: jgmarinhopontes

*/

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WPRoidsPhil' ) )
{	
	class WPRoidsPhil
	{		
		private static $instance = NULL;
		private $className;
		private $pluginName;
		private $pluginStrapline;
		private $donateLink;
		private $textDomain;
		private $debug;
		private $settings;
		private $settingCacheHtml;
		private $settingMinifyHtml;
		private $settingDeferJs;
		private $settingCompressImages;
		private $settingCacheCdn;
		private $settingIgnoredFolders;
		private $settingFlushSchedule;
		private $settingCreditLink;
		private $cacheDir;
		private $imgCache;
		private $compressionLevelJpeg;
		private $compressionLevelPng;
		private $assetsCache;
		private $assetsCacheFolder;
		private $postsCache;
		private $postsCacheFolder;
		private $fileTypes;
		private $earlyAssets;
		private $lateAssets;
		private $uri;
		private $protocol;
		private $domainName;
		private $siteUrl;
		private $rewriteBase;
		private $rootDir;
		private $styleFile;
		private $coreScriptFile;
		private $scriptFile;
		private $theme;
		private $timezone;
		private $timestamp;
		private $jsDeps;
		private $nonceName;
		private $nonceAction;
		private $cdnProviders;
		private $cachingPlugins;
		private $conflictingPlugins;
		
		/**
		* Our constructor
		*/
		public function __construct()
		{			
			$this->className = get_class();
			$this->pluginName = 'Fast Caching!';
			$this->pluginStrapline = 'The fastest caching plugin for WordPress';
			
			// debug
			$this->debug = FALSE;
			
			// settings
			$this->settingCacheHtml = TRUE;
			$this->settingMinifyHtml = TRUE;
			$this->settingDeferJs = TRUE;
			$this->settingCompressImages = TRUE;
			$this->compressionLevelJpeg = 15;
			$this->compressionLevelPng = 50;
			$this->settingCacheCdn = TRUE;
			$this->settingFlushSchedule = FALSE;
			$this->settingCreditLink = FALSE;
			$this->settingIgnoredFolders = array();
			$this->settings = get_option( $this->textDomain.'_settings', NULL );
			if( $this->settings !== NULL )
			{
				if( isset( $this->settings['cache'] ) && isset( $this->settings['cache']['disabled'] ) && intval( $this->settings['cache']['disabled'] ) === 1 )
				{
					$this->settingCacheHtml = FALSE;
				}
				
				if( isset( $this->settings['html'] ) && isset( $this->settings['html']['disabled'] ) && intval( $this->settings['html']['disabled'] ) === 1 )
				{
					$this->settingMinifyHtml = FALSE;
				}
				
				if( isset( $this->settings['defer'] ) && isset( $this->settings['defer']['disabled'] ) && intval( $this->settings['defer']['disabled'] ) === 1 )
				{
					$this->settingDeferJs = FALSE;
				}
				
				if( isset( $this->settings['imgs'] ) && isset( $this->settings['imgs']['disabled'] ) && intval( $this->settings['imgs']['disabled'] ) === 1 )
				{
					$this->settingCompressImages = FALSE;
					if( $this->alternateIsDir( $this->imgCache ) ) $this->recursiveRemoveDirectory( $this->imgCache );
				}
					
				if( isset( $this->settings['imgs-quality-jpeg']['value'] ) && intval( $this->settings['imgs-quality-jpeg']['value'] ) !== intval( $this->compressionLevelJpeg ) )
				{
					$this->compressionLevelJpeg = intval( $this->settings['imgs-quality-jpeg']['value'] );
				}
				
				if( isset( $this->settings['imgs-quality-png']['value'] ) && intval( $this->settings['imgs-quality-png']['value'] ) !== intval( $this->compressionLevelPng ) )
				{
					$this->compressionLevelPng = intval( $this->settings['imgs-quality-png']['value'] );
				}
				
				if( isset( $this->settings['cdn'] ) && isset( $this->settings['cdn']['disabled'] ) && intval( $this->settings['cdn']['disabled'] ) === 1 )
				{
					$this->settingCacheCdn = FALSE;
				}
				
				if( isset( $this->settings['debug'] ) && isset( $this->settings['debug']['value'] ) && $this->settings['debug']['value'] === 'enabled' )
				{
					$this->debug = TRUE;
				}
					
				if( isset( $this->settings['schedule']['value'] ) && $this->settings['schedule']['value'] === 'disabled' )
				{
					$this->settingFlushSchedule = FALSE;
					// kill the schedule
					$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
					if( $scheduleTimestamp !== FALSE )
					{
						wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
					}
				}
				if( isset( $this->settings['schedule']['value'] ) && $this->settings['schedule']['value'] !== 'disabled' )
				{					
					// set event to flush posts
					$this->settingFlushSchedule = $this->settings['schedule']['value'];
					if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
					{
					    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
					}				
				}
				
				$settingIgnoredFolders = array();
				foreach( $this->settings as $key => $settingArray )
				{
					if( $key === 'theme' && intval( $settingArray['disabled'] ) === 1 )
					{
						$settingIgnoredFolders[] = 'themes';
					}
					if( $key !== 'html' && $key !== 'cache' && $key !== 'cdn' && $key !== 'theme' )
					{
						$parts = explode( '/', $key );
						$settingIgnoredFolders[] = $parts[0];
					}
				}
				$this->settingIgnoredFolders = $settingIgnoredFolders;
				if( $this->settings['credit']['value'] === 'enabled' ) $this->settingCreditLink = TRUE;
				
			} // END if( $this->settings !== NULL )
			
			// vars
			if( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 )
			{		
				$this->protocol = 'https://';
			}
			else
			{
				$this->protocol = 'http://';
			}
			$this->siteUrl = site_url();
			$this->rootDir = $_SERVER['DOCUMENT_ROOT'] . str_replace( $this->protocol . $_SERVER['HTTP_HOST'], '', $this->siteUrl );
			
			// fix for ridiculous 1&1 directory lookup bug
			if( strpos( $this->rootDir, '/kunden' ) !== FALSE )
			{
				$this->rootDir = str_replace( '/kunden', '', $this->rootDir );
			}
			
			$this->fileTypes = array( 'css', 'core-js', 'js' );
			$this->earlyAssets = array( 'css', 'core-js' );
			$this->lateAssets = array( 'js' );
			$this->domainName = $_SERVER['HTTP_HOST'];
			$this->rewriteBase = str_replace( $this->protocol . $this->domainName, '', $this->siteUrl );
			if( strpos( $this->domainName, 'www.' ) === 0 )
			{
				$this->domainName = substr( $this->domainName, 4 );
			}
			$this->uri = str_replace( $this->rewriteBase, '', $_SERVER['REQUEST_URI'] );
			$this->cacheDir = $this->rootDir . '/wp-roids-cache';
			$this->imgCache = $this->cacheDir . '/img';
			$this->assetsCache = $this->cacheDir . '/' . 'assets' . $this->rewriteBase;
			$this->assetsCacheFolder = str_replace( $this->rootDir . '/', '', $this->assetsCache );
			$this->postsCache = $this->cacheDir . '/' . 'posts' . $this->rewriteBase;
			$this->postsCacheFolder = str_replace( $this->rootDir . '/', '', $this->postsCache );
			$this->styleFile = $this->textDomain . '-styles.min';
			$this->coreScriptFile = $this->textDomain . '-core.min';
			$this->scriptFile = $this->textDomain . '-scripts.min';
			$this->theme = wp_get_theme();
			$siteTimezone = get_option( 'timezone_string' );
			if( empty( $siteTimezone ) )
			{
				$siteTimezone = 'UTC';
			}
			$this->timezone = $siteTimezone;
			date_default_timezone_set( $this->timezone );
			$this->timestamp = '-' . substr( time(), 0, 8 );
			$this->jsDeps = array();
			$this->nonceName = $this->textDomain . '_nonce';
			$this->nonceAction = 'do_' . $this->textDomain;
			
			$this->cdnProviders = array(
				'ajax.googleapis.com',
				'cdn',
				'unpkg.com',
			);
			
			$this->conflictingPlugins = array(
				['slug' => 'nextgen-gallery/nggallery.php', 'name' => 'WordPress Gallery Plugin – NextGEN Gallery', 'ref' => 'https://wordpress.org/support/topic/all-marketing-crappy-product-does-not-even-follow-good-coding-practices'],
			);
			$this->cachingPlugins = array(
				['slug' => 'autoptimize/autoptimize.php', 'name' => 'Autoptimize'],
				['slug' => 'breeze/breeze.php', 'name' => 'Breeze'],
				['slug' => 'cache-control/cache-control.php', 'name' => 'Cache-Control'],
				['slug' => 'cache-enabler/cache-enabler.php', 'name' => 'Cache Enabler'],
				['slug' => 'cachify/cachify.php', 'name' => 'Cachify'],
				['slug' => 'comet-cache/comet-cache.php', 'name' => 'Comet Cache'],
				['slug' => 'dessky-cache/dessky-cache.php', 'name' => 'Dessky Cache'],
				['slug' => 'fast-velocity-minify/fvm.php', 'name' => 'Fast Velocity Minify'],
				['slug' => 'hummingbird-performance/wp-hummingbird.php', 'name' => 'Hummingbird'],
				['slug' => 'sg-cachepress/sg-cachepress.php', 'name' => 'SG Optimizer'],
				['slug' => 'hyper-cache/plugin.php', 'name' => 'Hyper Cache'],
				['slug' => 'hyper-cache-extended/plugin.php', 'name' => 'Hyper Cache Extended'],
				['slug' => 'litespeed-cache/litespeed-cache.php', 'name' => 'LiteSpeed Cache'],
				['slug' => 'simple-cache/simple-cache.php', 'name' => 'Simple Cache'],			
				['slug' => 'w3-total-cache/w3-total-cache.php', 'name' => 'W3 Total Cache'],
				['slug' => 'wp-fastest-cache/wpFastestCache.php', 'name' => 'WP Fastest Cache'],
				['slug' => 'wp-speed-of-light/wp-speed-of-light.php', 'name' => 'WP Speed of Light'],
				['slug' => 'wp-super-cache/wp-cache.php', 'name' => 'WP Super Cache'],
			);
			
			// do we have the necessary stuff?
			if( ! $this->checkRequirements() )
			{
				// ensures .htaccess is reset cleanly
				$this->deactivate();
				return FALSE;
			}
			else
			{
				// install
				register_activation_hook( __FILE__, array( $this, 'install' ) );
				add_filter( 'cron_schedules', array( $this, 'addCronIntervals' ) );
				add_action( $this->textDomain . '_flush_schedule', array( $this, 'flushPostCache' ) );				
				// add links below plugin description on Plugins Page table
				// see: https://developer.wordpress.org/reference/hooks/plugin_row_meta/
				add_filter( 'plugin_row_meta', array( $this, 'pluginMetaLinks' ), 10, 2 );
				
				// some styles for the admin page
				add_action( 'admin_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
				
				// add a link to the Admin Menu
				add_action( 'admin_menu', array( $this, 'adminMenu' ) );
				
				// add settings link
				// see: https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'pluginActionLinks' ) );
				
				// add admin bar link
				add_action('admin_bar_menu', array( $this, 'adminBarLinks' ), 1000 );
				
				// individual caching actions
				add_action( 'save_post', array( $this, 'cacheDecider') );
				add_action( 'comment_post', array( $this, 'cacheComment') );
				
				// cache flushing actions
				add_action( 'activated_plugin', array( $this, 'flushWholeCache' ), 10, 2 );
				add_action( 'deactivated_plugin', array( $this, 'flushWholeCache' ), 10, 2 );
				add_action( 'switch_theme', array( $this, 'reinstall' ), 1000 );
				add_action( 'wp_create_nav_menu', array( $this, 'flushPostCache' ) );
				add_action( 'wp_update_nav_menu', array( $this, 'flushPostCache' ) );
				add_action( 'wp_delete_nav_menu', array( $this, 'flushPostCache' ) );
		        add_action( 'create_term', array( $this, 'flushPostCache' ) );
		        add_action( 'edit_terms', array( $this, 'flushPostCache' ) );
		        add_action( 'delete_term', array( $this, 'flushPostCache' ) );
		        add_action( 'add_link', array( $this, 'flushPostCache' ) );
		        add_action( 'edit_link', array( $this, 'flushPostCache' ) );
		        add_action( 'delete_link', array( $this, 'flushPostCache' ) );
		        
		        // deactivate
				register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
				
				$ignorePattern = "~(/wp-admin/|/wp-json/|/wp-roids-cache/|/xmlrpc.php|/wp-(app|cron|login|register|mail).php|wp-.*.php|/feed/|favicon.ico|index.php|wp-comments-popup.php|wp-links-opml.php|wp-locations.php|sitemap(_index)?.xml|[a-z0-9_-]+-sitemap([0-9]+)?.xml)~";
				$ignoreCheck = boolval( intval( preg_match( $ignorePattern, $this->uri ) ) );
				if( $this->debug === TRUE ) $this->writeLog( 'Call to `' . $this->uri . '` $ignoreCheck said: ' . strtoupper( var_export( $ignoreCheck, TRUE ) ) );
				
				// let's roll...
				if( $ignoreCheck !== TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( '#################### ' . $this->pluginName . ' instantiated with a call to `' . $this->protocol . $this->domainName . $this->uri . '` ####################' );
					add_action( 'init', array( $this, 'sentry' ) );
					add_action( 'get_header', array( $this, 'minifyView' ) );
					remove_action( 'wp_head', 'wp_generator' );
					add_filter( 'script_loader_src', array( $this, 'removeScriptVersion' ), 15, 1 );
					add_filter( 'style_loader_src', array( $this, 'removeScriptVersion' ), 15, 1 );
					add_action( 'wp_enqueue_scripts', array( $this, 'doAllAssets' ), PHP_INT_MAX - 2 );
					add_action( 'wp_head', array( $this, 'cacheThisView'), PHP_INT_MAX - 1 );
					add_action( 'wp', array( $this, 'htaccessFallback'), PHP_INT_MAX );
					add_action( 'wp_footer', array( $this, 'creditLink') );
				}
			
				// ONLY USE IF DESPERATE! Prints data to bottom of PUBLIC pages!
				//if( $this->debug === TRUE ) add_action( 'wp_footer', array( $this, 'wpRoidsDebug'), 100 );
			
			}			
			
		} // END __construct()
		
		/**
		* DEV use only!
		* 
		* @return string: Your debug info
		*/
		private function writeLog( $message )
		{
			if( $this->debug === TRUE )
			{
				$microsecArray = explode( ' ', microtime() );
				$microsec = ltrim( $microsecArray[0], '0' );
				$fh = fopen( __DIR__ . '/log.txt', 'ab' );
				fwrite( $fh, date( 'd/m/Y H:i:s' ) . $microsec . ' (' . $this->timezone . '): ' . $message . "\n________________________________\n\n" );
				fclose( $fh );
			}
		}
		
		public function wpRoidsDebug()
		{
			if( $this->debug === TRUE )
			{
				$output = array('wpRoidsDebug initialised!...');
				if( file_exists( __DIR__ . '/log.txt' ) )
				{
					$theLog = htmlentities( file_get_contents( __DIR__ . '/log.txt' ) );			
					// strip excessive newlines
					$theLog = preg_replace( '/\r/', "\n", $theLog );
					$theLog = preg_replace( '/\n+/', "\n", $theLog );
					// wrap errors in a class
					$theLog = preg_replace( '~^(.*ERROR:.*)$~m', '<span class="error">$1</span>', $theLog );
					$output['errorsFound'] = substr_count( $theLog, 'ERROR:' );
					$output['logfile'] = $theLog;
				}
				echo '<pre class="debug"> Debug...'. "\n\n" . print_r( $output, TRUE ) .'</pre>';				
			}
		}
		/**
		* END DEV use only!
		*/
		
		/**
		* Adapted from https://www.php.net/manual/en/function.is-dir.php#42770
		* This due to false positives sometimes from `is_dir`
		* @param string $file: path to directory
		* 
		* @return bool
		*/
		private function alternateIsDir( $file )
		{
			$output = FALSE;
		    if( ( @fileperms( $file ) & 0xF000 ) === 0x4000 )
		    {
		        $output = TRUE;
		    }
		    return $output;
		}
		
		/**
		* Basically a boolean strpos(), but checks an array of strings for occurence
		* @param string $haystack
		* @param array $needles
		* @param int $offset
		* 
		* @return bool
		*/
		private function strposArray( $haystack, $needles, $offset = 0 )
		{
		    foreach( $needles as $lookup )
		    {
		        if( strpos( $haystack, $lookup, $offset ) !== FALSE )
		        {
		        	return TRUE; // stop on first true result
				}
		    }
		    return FALSE;
		    
		} // END strposArray()
		
		/**
		* Add to built in WordPress CRON schedules
		* see: https://developer.wordpress.org/plugins/cron/understanding-wp-cron-scheduling/
		* @param array $schedules
		* 
		* @return array $schedules
		*/
		public function addCronIntervals( $schedules )
		{
			$schedules['every_five_minutes'] = array(
		        'interval' => 300,
		        'display'  => esc_html__( 'Every Five Minutes' ),
		    );
			$schedules['weekly'] = array(
		        'interval' => 604800,
		        'display'  => esc_html__( 'Weekly' ),
		    );
		    return $schedules;
		}
		
		/**
		* Check dependencies
		*/
		public function checkRequirements()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'checkRequirements() running...');
			$requirementsMet = TRUE;
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			require_once( ABSPATH . '/wp-includes/pluggable.php' );
			
			// we need cURL active
			if( ! in_array( 'curl', get_loaded_extensions() ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: cURL NOT available!');
				add_action( 'admin_notices', array( $this, 'messageCurlRequired' ) );
				$requirementsMet = FALSE;
			}
			
			// .htaccess needs to be writable, some security plugins disable this
			// only perform this check when an admin is logged in, or it'll deactivate the plugin :/
			if( current_user_can( 'install_plugins' ) )
			{		
				$htaccess = $this->rootDir . '/.htaccess';
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN  - DO NOT REMOVE THIS LINE';
				$endtext = '### END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE )
				{
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: `.htaccess` NOT writable!');
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						$requirementsMet = FALSE;
					}
				}
			}
			
			// we do not want caching plugins active
			$cachingDetected = FALSE;
			foreach( $this->cachingPlugins as $cachingPlugin )
			{
				if( is_plugin_active( $cachingPlugin ) )
				{
					$cachingDetected = TRUE;
				}
			}
			if( $cachingDetected === TRUE )
			{
				add_action( 'admin_notices', array( $this, 'messageCachingDetected' ) );
				$requirementsMet = FALSE;
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Another caching plugin detected!');
			}
			
			// we do not want conflicting plugins active
			$conflictDetected = FALSE;
			foreach( $this->conflictingPlugins as $conflictingPlugin )
			{
				if( is_plugin_active( $conflictingPlugin['slug'] ) )
				{
					$conflictDetected = TRUE;
				}
			}
			if( $conflictDetected === TRUE )
			{
				add_action( 'admin_notices', array( $this, 'messageConflictDetected' ) );
				$requirementsMet = FALSE;
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Conflicting plugin(s) detected!');
			}
			
			// kill plugin activation
			if( $requirementsMet === FALSE ) deactivate_plugins( plugin_basename( __FILE__ ) );
			
			if( $this->debug === TRUE ) $this->writeLog( 'checkRequirements() SUCCESS!');
			return $requirementsMet;
			
		} // END checkRequirements()
		
		/**
		* Called on plugin activation - sets things up
		* @return void
		*/
		public function install()
		{
			if( $this->debug === TRUE ) $this->writeLog( $this->pluginName . ' install() running');
			
			// create cache directory
			if( ! $this->alternateIsDir( $this->cacheDir ) ) mkdir( $this->cacheDir, 0755 );
			
			// .htaccess
			$htaccess = $this->rootDir . '/.htaccess';
			if( file_exists( $htaccess ) )
			{
				$desiredPerms = fileperms( $htaccess );
				chmod( $htaccess, 0644 );
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN fast caching - DO NOT REMOVE THIS LINE';
				$endtext = '### END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE )
				{
					// take a backup
					$backup = __DIR__ . '/ht-backup.txt';
					$fh = fopen( $backup, 'wb' );
					fwrite( $fh, $current );
					fclose( $fh );
					chmod( $backup, 0600 );
					
					// edit .htaccess
					$assetsCacheFolder = str_replace( $this->rootDir . '/', '', $this->assetsCache );
					$fullPostsCacheFolder = str_replace( $_SERVER['DOCUMENT_ROOT'] . '/', '', $this->postsCache );
					$fullPostsCacheFolder = ltrim( str_replace( $this->rootDir, '', $fullPostsCacheFolder ), '/' );
					$fullImagesCacheFolder = str_replace( $_SERVER['DOCUMENT_ROOT'] . '/', '', $this->imgCache );
					$fullImagesCacheFolder = ltrim( str_replace( $this->rootDir, '', $fullImagesCacheFolder ), '/' );
					$postsCacheFolder = str_replace( $this->rootDir . '/', '', $this->postsCache );
					$imagesCacheFolder = str_replace( $this->rootDir . '/', '', $this->imgCache );
					$additional = str_replace( '[[DOMAIN_NAME]]', $this->domainName, file_get_contents( __DIR__ . '/ht-template.txt' ) );
					$additional = str_replace( '[[WP_ROIDS_REWRITE_BASE]]', $this->rewriteBase, $additional );
					$additional = str_replace( '[[WP_ROIDS_ASSETS_CACHE]]', $assetsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_FULL_POSTS_CACHE]]', $fullPostsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_ALT_FULL_POSTS_CACHE]]', $this->postsCache, $additional );
					$additional = str_replace( '[[WP_ROIDS_FULL_IMAGES_CACHE]]', $fullImagesCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_ALT_FULL_IMAGES_CACHE]]', $this->imgCache, $additional );
					$additional = str_replace( '[[WP_ROIDS_POSTS_CACHE]]', $postsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_IMAGES_CACHE]]', $imagesCacheFolder, $additional );
					$startpoint = strpos( $current, '# BEGIN WordPress' );
					$new = substr_replace( $current, $additional . "\n\n", $startpoint, 0 );
					$fh = fopen( $htaccess, 'wb' );
					fwrite( $fh, $new );
					fclose( $fh );
					chmod( $htaccess, $desiredPerms );
					if( $this->debug === TRUE ) $this->writeLog( '`.htaccess` rewritten with: "' . $new . '"');
				}
    		
			} // END if htaccess
			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
				clearstatcache();
			}
			
			// set event to flush posts
			if( $this->settingFlushSchedule !== FALSE )
			{
				if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
				{
				    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
				}
			}
			
		} // END install()
		
		/**
		* Ongoing check all is healthy
		* 
		* @return void
		*/
		public function sentry()
		{
			if( current_user_can( 'install_plugins' ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'sentry() running...');
				$requirementsMet = TRUE;
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				if( is_plugin_active( plugin_basename( __FILE__ ) )  )
				{
					// check .htaccess is still legit for us
					if( $this->debug === TRUE ) $this->writeLog( 'sentry() running: ' . $this->pluginName . ' is active!' );	
					$htaccess = $this->rootDir . '/.htaccess';
					$current = file_get_contents( $htaccess );
					
					$myRules = TRUE;
					$starttext = '### BEGIN fast caching - DO NOT REMOVE THIS LINE';
					$endtext = '### END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
					if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE ) $myRules = FALSE;
					$newCookieCheck = 'RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$';
					if( strpos( $current, $newCookieCheck ) === FALSE ) $myRules = FALSE;
					
					$myOldRules = FALSE;
					$oldstarttext = '# BEGIN fast caching - DO NOT REMOVE THIS LINE';
					$oldendtext = '# END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
					if( strpos( $current, $oldstarttext ) !== FALSE && strpos( $current, $oldendtext ) !== FALSE ) $myOldRules = TRUE;
					
					if( $myRules === FALSE || ( $myRules === FALSE && $myOldRules === TRUE ) )
					{
						$requirementsMet = FALSE;
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() running: `.htaccess` is missing rules!' );	
					}
				
					// check cache directories
					if( ! $this->alternateIsDir( $this->cacheDir ) )
					{
						$requirementsMet = FALSE;
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() running: cache folder not found!' );
					}
					
					if( $requirementsMet === FALSE )
					{
						$this->deactivate();
						$this->install();
					}
					
				} // END we are active
				
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() found ' . $this->pluginName . ' is NOT active!');
				}
				
			} // END current user is admin
		} // END sentry()
		
		/**
		* Called on any plugin activation - resets things up and returns to request origin
		* @return void
		*/
		public function reinstall()
		{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			activate_plugins( plugin_basename( __FILE__ ), $this->protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
				clearstatcache();
			}
			
			if( $this->debug === TRUE ) $this->writeLog( 'reinstall() executed!');
		} // END reinstall()
		
		/**
		* Fired when a page is browsed
		* @return void
		*/
		public function htaccessFallback()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() running...' );
			if( $this->isViableView() === TRUE && ! $_POST )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() determined isViableView() is TRUE' );
					
				// does a cache file exist?
				$thePermalink = $this->protocol . $this->domainName . $this->uri;
				if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() determined $thePermalink is ' . $thePermalink );
						
				$isHome = FALSE;					
				if( rtrim( $thePermalink, '/') === rtrim( $this->siteUrl, '/' ) )
				{
					$isHome = TRUE;
				}
				if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() determined $isHome is ' . var_export( $isHome, TRUE ) );
				
				if( $isHome === FALSE )
				{
					$cacheFilePath = str_replace( $this->siteUrl, '', $thePermalink );
					$fullCacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFilePath, '/' ), '/' );
					$cacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
					$cacheFile = $fullCacheFilePath . '/index.html';	
				}
				else
				{
					$cacheFile = $this->postsCache . '/index.html';
				}
				
				if( file_exists( $cacheFile ) )
				{
					// cache file exists, yet .htaccess did NOT rewrite :/
					if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() invoked for file: `' . $cacheFile . '`!');
						
					// file is cool, go get it
					$cacheContent = file_get_contents( $cacheFile );
					$cacheContent .= "\n" . '<!-- fast caching cache file served by PHP script as Apache `.htaccess` rewrite did not occur.' . "\n";
					
					if( isset( $_SERVER['SERVER_SOFTWARE'] ) )
					{
						if( stripos( $_SERVER['SERVER_SOFTWARE'], 'apache' ) !== FALSE)
						{
							if( $isHome === TRUE )
							{
								$cacheContent .= 'BUT! This is your home page, SOME hosts struggle with `.htaccess` rewrite on the home page only.' . "\n" . 'Check one of your inner Posts/Pages and see what the comment is there... -->';
							}
						}
						else
						{
							$cacheContent .= 'It appears your web server is NOT running on Apache, it could be using NGINX or Windows IIS, for example -->';
						}
					}
					else
					{
						$cacheContent .= 'Contact your host for explanation -->';
					}						
					
					die( $cacheContent );						
					
				} // END cache file exists
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() says cache file `' . $cacheFile . '` does NOT exist!' );
				}				
				
			} // END isViableView
			
			if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() determined isViableView was FALSE, so did nothing' );
			
		} // END htaccessFallback()
		
		/**
		* Is the request a cacheworthy View?
		* @param bool $assets: Whether this is being called by the asset crunching functionality
		* @param obj $post: Instance of WP_Post object - OPTIONAL!
		* 
		* @return bool
		*/
		private function isViableView( $post = NULL, $assets = FALSE )
		{
			$output = TRUE;
			$requestView = $this->domainName . $this->uri;
			$reasonsNot = array();
				
			if( $post !== NULL && $post instanceof WP_Post )
			{
				if( get_post_status( $post->ID ) !== 'publish' )
				{
					$output = FALSE;
					$reasonsNot[] = 'Post/Page is NOT published';
				}
				
				if( post_password_required( $post->ID ) === TRUE )
				{
					$output = FALSE;
					$reasonsNot[] = 'Post/Page requires password';
				}				
				
			} // END $post instanceof WP_Post
			
			foreach( $_COOKIE as $cookieKey => $cookieValue )
			{
				if( strpos( $cookieKey, 'wordpress_logged_in' ) !== FALSE )
				{
					$output = FALSE;
					$reasonsNot[] = 'You are logged in to this WordPress site';
				}
				
				if( strpos( $cookieKey, 'postpass' ) !== FALSE )
				{
					$output = FALSE;
					$reasonsNot[] = 'You are viewing a password protected Post/Page';
				}
				
				if( strpos( $cookieKey, 'comment_author' ) !== FALSE )
				{
					$output = FALSE;
					$reasonsNot[] = 'You have asked WordPress to remember your details when commenting';
				}
			}
			
			if( is_admin() === TRUE )
			{
				$output = FALSE;
				$reasonsNot[] = 'Request is inside Admin Dashboard';
			}
			
			if( $assets === FALSE && ( $_POST && isset( $_POST['X-WP-Roids'] ) ) )
			{
				$output = FALSE;
				$reasonsNot[] = 'Request is from this plugin taking a snapshot via cURL';
			}
			
			if( post_password_required() === TRUE )
			{
				$output = FALSE;
				$reasonsNot[] = 'Post/Page requires password';
			}
			
			if( have_posts() === FALSE && is_singular() === FALSE )
			{
				$output = FALSE;
				$reasonsNot[] = 'No Posts OR Page found';
			}
			
			if( defined( 'DONOTCACHEPAGE' ) === TRUE && intval( DONOTCACHEPAGE ) === 1 )
			{
				$output = FALSE;
				$reasonsNot[] = 'Request is marked DONOTCACHEPAGE';
			}
			
			if( is_404() === TRUE )
			{
				$output = FALSE;
				$reasonsNot[] = 'Post/Page is 404 not found';
			}
			
			if( is_search() === TRUE )
			{
				$output = FALSE;
				$reasonsNot[] = 'Post/Page is search result';
			}
			
			if( $this->debug === TRUE ) $this->writeLog( 'isViableView() ran on `' . $requestView . '` - outcome was: ' . strtoupper( var_export( $output, TRUE ) ) . ( ! empty( $reasonsNot ) ? "\n" . '$reasonsNot was:' . strtoupper( print_r( $reasonsNot, TRUE ) ) : '' ) );
				
			if( $output === FALSE && ! empty( $reasonsNot ) )
			{
				$output = $reasonsNot;
			}
			
			return $output;
			
		} // END isViableView()
		
		/**
		* Caches a View
		* @param int $id: a Post/Page ID - OPTIONAL!
		* 
		* @return bool: TRUE on success, FALSE on fail
		*/
		public function cacheView( $id = NULL, $internalCall = FALSE )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheView() running... Received $id data (if any): ' . var_export( $id, TRUE ) );
				
			$start = microtime( TRUE );
			if( $this->settingCacheHtml === TRUE )
			{
				$thePermalink = $this->protocol . $this->domainName . $this->uri;
				$isHome = FALSE;
				
				if( ( is_single() || $internalCall === TRUE ) && $id !== NULL && is_numeric( $id ) && intval( $id ) > 0 )
				{
					$thePermalink = get_permalink( $id );
				}
				
				if( $this->debug === TRUE ) $this->writeLog( 'cacheView() determined $thePermalink is ' . var_export( $thePermalink, TRUE ) );
				
				if( $thePermalink !== FALSE )
				{					
					if( rtrim( $thePermalink, '/') === rtrim( $this->siteUrl, '/' ) )
					{
						$isHome = TRUE;
					}
					
					if( $this->debug === TRUE ) $this->writeLog( 'cacheView() determined $isHome is ' . var_export( $isHome, TRUE ) );
					
					if( $isHome === FALSE )
					{
						$cacheFile = str_replace( $this->siteUrl, '', $thePermalink );
						$cacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
						$newfile = $cacheFilePath . '/index.html';	
					}
					else
					{
						$cacheFilePath = $this->postsCache;
						$newfile = $cacheFilePath . '/index.html';
					}
					
					$data = array( 'X-WP-Roids' => TRUE );
			        $curlOptions = array(
			            CURLOPT_URL => $thePermalink,
			            CURLOPT_REFERER => $thePermalink,
						CURLOPT_POST => TRUE,
						CURLOPT_POSTFIELDS => $data,
				        CURLOPT_HEADER => FALSE,
			            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
			            CURLOPT_RETURNTRANSFER => TRUE,
			        );
					$ch = curl_init();
		    		curl_setopt_array( $ch, $curlOptions );
		    		$html = curl_exec( $ch );
		    		curl_close( $ch );
				    $executionTime = number_format( microtime( TRUE ) - $start, 5 );
		    		
		    		if( $html !== FALSE && ! empty( $html ) )
		    		{
					    // add a wee note
					    $htmlComment = "\n" . '<!-- Performance enhanced Static HTML cache file generated at ' . date( "M d Y H:i:s" ) . ' (' . $this->timezone . ') by ' . $this->pluginName . ' in ' . $executionTime . ' sec -->';
						if( ! $this->alternateIsDir( $cacheFilePath ) )
						{
							mkdir( $cacheFilePath, 0755, TRUE );
						}
						// write the static HTML file
						$fh = fopen( $newfile, 'wb' );
						fwrite( $fh, $html . $htmlComment );
						fclose( $fh );
						if( file_exists( $newfile ) )
						{
							if( $this->debug === TRUE ) $this->writeLog( '`' . $newfile . '` written' );					
							if( $this->debug === TRUE ) $this->writeLog( 'cacheView() took ' . $executionTime . ' sec' );
							return TRUE;
						}
						else
						{
							if( $this->debug === TRUE ) $this->writeLog( 'ERROR: `' . $newfile . '`  was NOT written, grrrrr' );
							if( $this->debug === TRUE ) $this->writeLog( 'cacheView() took ' . $executionTime . ' sec' );
							return FALSE;
						}
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: cURL FAILED to retrieve HTML, PageSpeed was over 10 seconds, may be large images or bad queries' );
						if( $this->debug === TRUE ) $this->writeLog( 'cacheView() took ' . $executionTime . ' sec' );
						return FALSE;
					}
					
				} // END $thePermalink !== FALSE	    		
			}
			else
			{
				if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: `' . $newfile . '`  was NOT written as HTML caching disabled in Settings' );
				return FALSE;
			}
			
		} // END cacheView()
		
		/**
		* Fired when a View not in the cache is browsed
		* @return void
		*/
		public function cacheThisView()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheThisView() running...');
			if( $this->settingCacheHtml === TRUE )
			{
				$postId = NULL;
				global $post;
				if( $post instanceof WP_Post )
				{
					$postId = $post->ID;
				}
				else
				{
					$post = NULL;
				}
				if( $this->isViableView( $post ) === TRUE )
				{
					$start = microtime( TRUE );
					$outcome = $this->cacheView( $postId );
					if( $this->debug === TRUE )
					{
						if( $outcome === TRUE )
						{
							$this->writeLog( 'cacheThisView() on `' . $post->post_title . '` took ' . number_format( microtime( TRUE ) - $start, 5 ) . ' sec' );
						}
						else
						{
							$this->writeLog( 'ERROR: cacheThisView() on `' . $post->post_title . '` failed with FALSE response from cacheView()' );
						}
					}
				} // END post IS viable
				
			} // END $this->settingCacheHtml === TRUE
			else
			{
				$this->writeLog( 'cacheThisView() did nothing because caching is disabled in Settings' );
			}
			
		} // END cacheThisView()
		
		/**
		* Fired on "save_post" action
		* @param int $id: the Post ID
		* 
		* @return void
		*/
		public function cacheDecider( $id )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheDecider() running...');
			$postObj = get_post( $id );
			if( $postObj instanceof WP_Post )
			{
				switch( $postObj->post_status )
				{
					case 'publish':
						if( $postObj->post_password === '' )
						{
							$this->flushPostCache();
							$this->cacheView( $id, TRUE );
						}
						else
						{
							$this->flushPostCache();
						}						
						break;
					case 'inherit':
						$this->flushPostCache();
						$this->cacheView( $postObj->post_parent, TRUE );
						break;
					case 'private':
					case 'trash':
						$this->flushPostCache();
						break;
						
				} // END switch WP_Post
				
				if( $this->debug === TRUE ) $this->writeLog( 'cacheDecider() was triggered! Got a WP_Post obj. Status was: ' . $postObj->post_status );
					
			} // END is WP_Post
			
		} // END cacheDecider()
		
		/**
		* Deletes cached version of Post/Page
		* @param int $id: a Post/Page ID
		* 
		* @return void
		*/
		public function deleteCachePost( $id )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() running...' );
			$thePermalink = get_permalink( $id );
			$isHome = FALSE;
			if( rtrim( $thePermalink, '/' ) === rtrim( $this->siteUrl, '/' ) )
			{
				$isHome = TRUE;
			}
			
			if( $isHome === FALSE )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - NOT the home page' );
				$cacheFile = str_replace( $this->siteUrl, '', str_replace( '__trashed', '', $thePermalink ) );
				$cacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
				$assetFilePath = $this->assetsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
				$killfile = $cacheFilePath . '/index.html';	
			}
			else
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - IS the home page' );
				$cacheFilePath = $this->postsCache;
				$assetFilePath = $this->assetsCache . '/' . str_replace( $this->protocol . $_SERVER['HTTP_HOST'], '', $this->siteUrl );
				$killfile = $cacheFilePath . '/index.html';
			}
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $cacheFilePath = "' . $cacheFilePath . '"' );
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $killfile = "' . $killfile . '"' );
			
			if( $this->alternateIsDir( $cacheFilePath ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $cacheFilePath $this->alternateIsDir = TRUE' );
				if( file_exists( $killfile ) )
				{
					unlink( $killfile );
					clearstatcache();
				}					
				$this->recursiveRemoveEmptyDirectory( $cacheFilePath );
			}
			
			if( $this->alternateIsDir( $assetFilePath ) )
			{
				$scriptFile = $this->scriptFile;
				$filenameArray = glob( $assetFilePath . '/' . $scriptFile . "*" );
				if( count( $filenameArray) === 1 && file_exists( $filenameArray[0] ) )
				{
					unlink( $filenameArray[0] );
					clearstatcache();
				}
				$this->recursiveRemoveEmptyDirectory( $assetFilePath );
			}
			
		} // END deleteCachePost()
		
		/**
		* Checks if new comment is approved and caches Post if so
		* @param int $commentId: The Comment ID
		* @param int $commentApproved: 1 if approved OR 0 if not
		* 
		* @return void
		*/
		public function cacheComment( $commentId, $commentApproved = 1 )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheComment() running...' );
			if( $commentApproved === 1 )
			{
				$theComment = get_comment( $commentId );
				if( $theComment instanceof WP_Comment ) $this->cacheView( $theComment->comment_post_ID, TRUE );
			}
			
		} // END cacheComment()
		
		/**
		* Minifies HTML string
		* @param string $html: Some HTML
		* 
		* @return string $html: Minified HTML
		*/
		private function minifyHTML( $html )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'minifyHTML() running...' );
			
			// Defer the main JS file if enabled
			if( $this->settingDeferJs === TRUE )
			{
				$pattern = "~^<script\s.*src='(" . $this->siteUrl . '.*' . $this->scriptFile . "-\d+\.js)'.*</script>$~m";
				$html = preg_replace( $pattern, '<script type="text/javascript"> function downloadJSAtOnload() { var element = document.createElement("script"); element.type = "text/javascript"; element.src = "$1"; document.body.appendChild(element); } if (window.addEventListener) window.addEventListener("load", downloadJSAtOnload, false); else if (window.attachEvent) window.attachEvent("onload", downloadJSAtOnload); else window.onload = downloadJSAtOnload; </script>', $html );
			}
			
			// Compress images if enabled
			if( $this->settingCompressImages === TRUE )
			{
				if( ! $this->alternateIsDir( $this->imgCache ) ) mkdir( $this->imgCache, 0755 );
				if( class_exists( 'DOMDocument' ) )
				{
					$dom = new DOMDocument;
					libxml_use_internal_errors( TRUE );
					$domLoaded = $dom->loadHTML( $html );
					libxml_use_internal_errors( FALSE );
					if( $domLoaded !== FALSE )
					{
						foreach( $dom->getElementsByTagName( 'img' ) as $node )
						{
						    if( $node->hasAttribute( 'src' ) )
						    {
								$src = $node->getAttribute( 'src' );
								if( strpos( $src, $this->siteUrl ) !== FALSE )
								{
									$siteUrl = str_replace( ['https:','http:'], '', $this->siteUrl );
									$path = rtrim( ABSPATH, '/' );
									$filePath = str_replace( [$this->siteUrl, $siteUrl], $path, $src );
									if( file_exists( $filePath ) )
									{
										// see if we have already compressed and cached this image
										$filename = basename( $filePath );
										$cachedImage = $this->imgCache . '/' . $filename;
										if( ! file_exists( $cachedImage ) )
										{
											$image = wp_get_image_editor( $filePath );
											if( ! is_wp_error( $image ) )
											{
												$quality = $image->get_quality();
												if( is_numeric( $quality ) && intval( $quality ) > $this->compressionLevelJpeg )
												{
													if( substr( $filePath, -4, 4 ) === '.jpg' || substr( $filePath, -5, 5 ) === '.jpeg' )
													{
														$compress = $image->set_quality( $this->compressionLevelJpeg );
													}
													if( substr( $filePath, -4, 4 ) === '.png' )
													{
														$compress = $image->set_quality( $this->compressionLevelPng );
													}
													
													if( isset( $compress ) && ! is_wp_error( $compress ) )
													{
														$image->save( $cachedImage );
														if( $this->debug === TRUE ) $this->writeLog( 'I compressed and cached an image! src: "' . $src . '" | xtn: "' . $xtn . '" | filepath: "' . $filePath . '" | original quality: "' . $quality . '" | basename: "' . $filename . '" | cache file: "' . $cachedImage . '"' );
													}
													else
													{
														if( $this->debug === TRUE ) $this->writeLog( 'ERROR: if( isset( $compress ) && ! is_wp_error( $compress ) ) fail! Returned FALSE! ~ NOT isset OR WP_Error thrown!' );
													}
												}
												else
												{
													if( $this->debug === TRUE ) $this->writeLog( 'NOTICE:if( is_numeric( $quality ) && intval( $quality ) > $this->compressionLevel ) fail! Returned FALSE! ~Locally hosted image ALREADY compressed beyond threshold!' );
												}
											}
											else
											{
												if( $this->debug === TRUE ) $this->writeLog( 'ERROR: if( ! is_wp_error( $image ) ) fail! Returned FALSE! ~ WP_Error thrown!' );
											}
										}
										else
										{
											if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: if( ! file_exists( $cachedImage ) ) fail! Returned FALSE! ~ Locally hosted CACHED image found' );
										}
									}
									else
									{
										if( $this->debug === TRUE ) $this->writeLog( 'WARNING: if( file_exists( $filePath ) ) fail! Returned FALSE! ~ Locally hosted image NOT found' );
									}
								}
								else
								{
									if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: if( strpos( "src", $this->siteUrl ) !== FALSE ) fail! Returned FALSE! ~ Remotely hosted image found' );
								}
							}
							else
							{
								if( $this->debug === TRUE ) $this->writeLog( 'WARNING: if( $node->hasAttribute( "src" ) ) fail! Returned FALSE!' );
							}
							
						} // END foreach( $dom->getElementsByTagName( 'img' ) as $node )
					}					
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'WARNING: class_exists( "DOMDocument" ) fail! Returned FALSE!' );
				}
				
			} // END if( $this->settingCompressImages === TRUE )
			
			// Minify HTML if enabled
			if( $this->settingMinifyHtml === TRUE )
			{
				// see: https://stackoverflow.com/questions/5312349/minifying-final-html-output-using-regular-expressions-with-codeigniter		
				$regex = '~(?>[^\S ]\s*|\s{4,})(?=[^<]*+(?:<(?!/?(?:textarea|pre|span|a)\b)[^<]*+)*+(?:<(?>textarea|pre|span|a)\b|\z))~Six';
				// minify
				$html = preg_replace( $regex, NULL, $html );
				
			    // remove html comments, but not conditionals
			    $html = preg_replace( "~<!--(?!<!)[^\[>].*?-->~", NULL, $html );
			    
			    if( $html === NULL || $html === '' )
			    {
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: minifyHTML() fail! PCRE Error! File too big!' );
			    	exit( 'PCRE Error! File too big.');
			    }
			    
				global $post, $wp_query;
				if( ! $post instanceof WP_Post )
				{
					$post = NULL;
				}
				$isViable = $this->isViableView( $post );
				
				if( $isViable === TRUE && ! $_POST )
				{
					$html .= "\n" . '<!--' . "\n" . 'Minified web page generated at ' . date( "M d Y H:i:s" ) . ' (' . $this->timezone . ') by ' . $this->pluginName . "\n" . 'This page is NOT a cached static HTML file YET, but it should be on its next request if caching is enabled in Settings :)' . "\n" . '-->';
				}
				if( $isViable !== TRUE && is_array( $isViable ) && ! $_POST )
				{
					$html .= "\n" . '<!--' . "\n" . 'Minified web page generated at ' . date( "M d Y H:i:s" ) . ' (' . $this->timezone . ') by ' . $this->pluginName . "\n" . 'This page is NOT a cached static HTML file because:';
					foreach( $isViable as $reason )
					{
						$html .= "\n\t" . $reason;
					}
					$html .= "\n" . '-->';
				}
				
			} // END $this->settingMinifyHtml === TRUE
			else
			{
				// minification is switched off
				$html .= "\n" . '<!--' . "\n" . 'Performance enhanced web page generated at ' . date( "M d Y H:i:s" ) . ' (' . $this->timezone . ') by ' . $this->pluginName . "\n" . 'This page could be improved further if HTML minification is enabled in Settings ;)' . "\n" . '-->';
				$this->writeLog('minifyHTML() did nothing because HTML minification is disabled in Settings' );
			}
		    return $html;
		}
		
		public function minifyView()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'minifyView() running...' );
			ob_start( array( $this, 'minifyHTML' ) );
		}
		
		/**
		* Wipes the assets cache
		* @return void
		*/
		public function flushAssetCache()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'flushAssetCache() running...' );
			if( $this->alternateIsDir( $this->assetsCache ) )
			{
				$this->recursiveRemoveDirectory( $this->assetsCache );
				$createAssetsCacheDirectory = mkdir( $this->assetsCache, 0755, TRUE );
				if( $createAssetsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushAssetCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushAssetCache() FAILED, $this->assetsCache NOT created!' );
				}
			}
		} // END flushAssetCache()
		
		/**
		* Wipes the posts cache
		* @return void
		*/
		public function flushPostCache()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'flushPostCache() running...' );
			if( $this->alternateIsDir( $this->postsCache ) )
			{
				$this->recursiveRemoveDirectory( $this->postsCache );
				$createPostsCacheDirectory = mkdir( $this->postsCache, 0755, TRUE );
				if( $createPostsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushPostCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushPostCache() FAILED, $this->postsCache NOT created!' );
				}
			}
		} // END flushPostCache()
		
		/**
		* Wipes the posts cache
		* @return void
		*/
		public function flushWholeCache()
		{			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
				clearstatcache();
			}
			if( $this->alternateIsDir( $this->cacheDir ) )
			{
				$this->recursiveRemoveDirectory( $this->cacheDir );
				$createPostsCacheDirectory = mkdir( $this->cacheDir, 0755, TRUE );
				if( $createPostsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushWholeCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushWholeCache() FAILED, $this->cacheDir NOT created!' );
				}
			}
		} // END flushPostCache()
		
		public function doAllAssets()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'doAllAssets() running...' );
			$this->doAssets( $this->earlyAssets );
			$this->doAssets( $this->lateAssets );
			if( $this->debug === TRUE ) $this->writeLog( 'doAllAssets() run' );
		}
		
		/**
		* Control function for minifying assets
		* 
		* @return void
		*/
		private function doAssets( $fileTypes )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'doAssets() running...' );
			global $post;
			if( ! $post instanceof WP_Post )
			{
				$post = NULL;
			}
			if( $this->isViableView( $post, TRUE ) === TRUE )
			{
				$flushPostsCache = FALSE;
				foreach( $fileTypes as $fileType )
				{
					$files = $this->getAssets( $fileType );
					if( $this->refreshRequired( $files, $fileType ) === TRUE )
					{
						if( $this->debug === TRUE ) $this->writeLog( 'doAssets() determined refreshRequired() TRUE on file type `'. $fileType .'`' );
						if( $fileType === 'js' )
						{
							if( isset( $_POST['X-WP-Roids'] ) && $_POST['X-WP-Roids'] == TRUE )
							{
								$this->deleteCachePost( $post->ID );
								if( $this->debug === TRUE ) $this->writeLog( 'doAssets() Post ID `' . $post->ID . '` flushed' );
							}
						}
						else
						{
							if( isset( $_POST['X-WP-Roids'] ) && $_POST['X-WP-Roids'] == TRUE )
							{
								$this->flushPostCache();
								if( $this->debug === TRUE ) $this->writeLog( 'doAssets() Post cache flushed' );
							}
						}
						
						$this->refresh( $files, $fileType );
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'doAssets() determined refreshRequired() FALSE on file type `'. $fileType .'`' );
					}
					
					$this->requeueAssets( $files, $fileType );
					
				} // END foreach $fileType
				
			} // END if viable post	
			
		} // END doAssets()
		
		/**
		* 
		* @param string $type: Either 'css' or 'js'
		* 
		* @return array $filenames: List of CSS or JS assets. Format: $handle => $src
		*/
		private function getAssets( $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'getAssets() running...' );
			$output = array();
			$siteUrl = str_replace( ['https:','http:'], '', $this->siteUrl );
			$path = rtrim( ABSPATH, '/' );
			switch( $type )
			{
				case 'css':
					global $wp_styles;
					$wpAssets = $wp_styles;
					break;
				case 'core-js':
				case 'js':
					global $wp_scripts;
					$wpAssets = $wp_scripts;
					$deps = array();
					break;
			}
			
			foreach( $wpAssets->registered as $wpAsset )
			{
			// nope: core files (apart from 'jquery-core' & 'jquery-migrate'), plugins ignored via Settings, unqueued files & files w/o src
				if( (
					( ( $type === 'css' ) 
						|| ( $type === 'js' 
						&& $this->strposArray( $wpAsset->src, $this->settingIgnoredFolders ) === FALSE
						)
					)
					&& ( 
						strpos( $wpAsset->src, 'wp-admin' ) === FALSE
						&& strpos( $wpAsset->src, 'wp-includes' ) === FALSE
						&& ( strpos( $wpAsset->src, $this->domainName ) !== FALSE 
							|| strpos( $wpAsset->src, '/wp' ) === 0 
							|| ( $this->settingCacheCdn === TRUE && strpos( $wpAsset->src, 'cdn' ) !== FALSE && strpos( $wpAsset->src, 'font' ) === FALSE )
							)
						&& strpos( $wpAsset->handle, $this->textDomain ) === FALSE
						&& ( in_array( $wpAsset->handle, $wpAssets->queue ) 
							|| ( isset( $wpAssets->in_footer ) && in_array( $wpAsset->handle, $wpAssets->in_footer ) )
							)
						&& ! empty( $wpAsset->src )
						&& ! is_bool( $wpAsset->src )
						)
					)
					||
					( $type === 'core-js' 
						&& ( $wpAsset->handle === 'jquery-core' || $wpAsset->handle === 'jquery-migrate' )
						&& strpos( $wpAsset->handle, $this->textDomain ) === FALSE
						)
				)
				{
					if( $this->strposArray( $wpAsset->src, $this->cdnProviders ) === FALSE )
					{
						// prepend the relational files
						if( ( strpos( $wpAsset->handle, 'jquery' ) === 0 && strpos( $wpAsset->src, $this->domainName ) === FALSE ) || strpos( $wpAsset->src, '/wp' ) === 0 )
						{
							$wpAsset->src = $siteUrl . $wpAsset->src;
						}
						
						// we need the file path for checking file update timestamps later on in refreshRequired()
						$filePath = str_replace( [$this->siteUrl, $siteUrl], $path, $wpAsset->src );
						
						// now rebuild the url from filepath
						$src = str_replace( $path, $this->siteUrl, $filePath );
					}
					else
					{
						if( $this->settingCacheCdn === TRUE )
						{
							// no local filepath as is CDN innit
							$filePath = NULL;
							$src = $wpAsset->src;
						}
					}
					
					// add file to minification array list
					$output[$wpAsset->handle] = array( 'src' => $src, 'filepath' => $filePath, 'deps' => $wpAsset->deps, 'args' => $wpAsset->args, 'extra' => $wpAsset->extra );
					
					// if javascript we need all the dependencies for later in enqueueAssets()
					if( $type === 'js' )
					{
						foreach( $wpAsset->deps as $dep )
						{
							if( ! in_array( $dep, $deps ) ) $deps[] = $dep;
						}						
					}
					
					if( $this->debug === TRUE ) $this->writeLog('type `' . $type . '` getAssets() file: `'.$wpAsset->handle.'` was considered okay to cache/minify');
				} // END if considered ok to minify/cache
				
			} // END foreach registered asset
			
			if( $type === 'js' )
			{
				// set the class property that stores javascript dependencies
				$this->jsDeps = $deps;
				if( $this->debug === TRUE ) $this->writeLog( 'getAssets() $this->jsDeps = ' . print_r( $this->jsDeps, TRUE ) );
			}
			if( $this->settingCacheCdn === FALSE && $this->debug === TRUE ) $this->writeLog( 'getAssets() ignored items from CDNs as option disabled in Settings' );
			if( $this->debug === TRUE ) $this->writeLog( 'getAssets() $output = ' . print_r( $output, TRUE ) );
			return $output;
			
		} // END getAssets()
		
		/**
		* 
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* @param string $type: Either 'css' or 'js'
		* 
		* @return bool $refresh: Whether we need to recompile our asset file for this type
		*/
		private function refreshRequired( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() running...' );
			$refresh = FALSE;
			if( ! $this->alternateIsDir( $this->assetsCache ) ) return TRUE;
			clearstatcache(); // ensures filemtime() is up to date		
			switch( $type )
			{
				case 'css':
					$filenameArray = glob( $this->assetsCache . '/' .  $this->styleFile . "*" );
					break;
				case 'core-js':
					$filenameArray = glob( $this->assetsCache . '/' . $this->coreScriptFile . "*" );
					break;
				case 'js':
					$filenameArray = glob( $this->assetsCache . $this->uri . $this->scriptFile . "*" );
					break;
			}
			
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() $filenameArray = "' . print_r( $filenameArray, TRUE ) . '"' );
			
			// there is no plugin generated file, so we must refresh/generate
			if( empty( $filenameArray ) || count( $filenameArray ) !== 1 )
			{
				$refresh = TRUE;
			}			
			// if the plugin generated file exists, we need to check if any inside the $filenames minification array are newer
			else
			{
				$outputFile = $filenameArray[0];
				$editTimes = array();
				$outputFileArray = array( 'filepath' => $outputFile );
				array_push( $filenames, $outputFileArray );
				foreach( $filenames as $file )
				{
					$modified = @filemtime( $file['filepath'] );
					if( $modified === FALSE )
					{
						if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() filemtime FALSE on file `' . $file['filepath'] . '`' );
						$modified = $this->timestamp;
					}
					else
					{
						$modified = substr( $modified, 0, 8 );
					}
					$editTimes[$modified] = $file;
				}
				krsort( $editTimes );
				if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() $editTimes array = `' . print_r( $editTimes, TRUE ) . '`' );
				$latest = array_shift( $editTimes );
				if( $latest['filepath'] !== $outputFileArray['filepath'] )
				{
					$refresh = TRUE;
					if( file_exists( $outputFile ) )
					{
						unlink( $outputFile );
						clearstatcache();
					}
				}
			}
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() returned: ' . var_export( $refresh, TRUE ) );
			return $refresh;
			
		} // END refreshRequired()
		
		/**
		* 
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* @param string $type: Either 'css' or 'js'
		* 
		* @return bool on success
		*/
		private function refresh( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'refresh() running...' );
			$createAssetDirectory = NULL;
			if( ! $this->alternateIsDir( $this->assetsCache ) )
			{
				$createAssetDirectory = mkdir( $this->assetsCache, 0755, TRUE );
				if( $this->debug === TRUE ) $this->writeLog( '$this->assetsCache directory creation attempted' );
			}
			
			if( $this->alternateIsDir( $this->assetsCache ) || $createAssetDirectory === TRUE )
			{
				switch( $type )
				{
					case 'css':
						$outputFile = $this->assetsCache . '/' . $this->styleFile . $this->timestamp;
						break;
					case 'core-js':
						$outputFile = $this->assetsCache . '/' . $this->coreScriptFile . $this->timestamp;
						break;
					case 'js':
						$outputFile = $this->assetsCache . $this->uri . $this->scriptFile . $this->timestamp;
						if( ! $this->alternateIsDir( $this->assetsCache . $this->uri ) )
						{
							mkdir( $this->assetsCache . $this->uri, 0755, TRUE );
						}
						break;
				} // END switch type	
				$theCode = '';
				foreach( $filenames as $handle => $file )
				{
					if( $file['filepath'] !== NULL )
					{
						$fileDirectory = dirname( $file['filepath'] );
						$fileDirectory = realpath( $fileDirectory );
					}	
		        	$contentDir = $this->rootDir;
		        	$contentUrl = $this->siteUrl;
					// cURL b/c if CSS dynamically generated w. PHP, file_get_contents( $file['filepath'] ) will return code, not CSS
					// AND using file_get_contents( $file['src'] ) will return 403 unauthourised
			        $curlOptions = array(
			            CURLOPT_URL => $file['src'],
			            CURLOPT_REFERER => $file['src'],
			            CURLOPT_HEADER => FALSE,
			            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
			            CURLOPT_RETURNTRANSFER => TRUE,
			        );
					$ch = curl_init();
	        		curl_setopt_array( $ch, $curlOptions );
	        		$code = curl_exec( $ch );
	        		curl_close( $ch );
	        		
	        		// is there code? do stuff
	        		if( strlen( $code ) !== 0 && ! empty( $code ) )
	        		{
	        			// if conditional e.g. IE CSS get rid of it, let WP do it's thing
	        			if( $type === 'css' && ! empty( $file['extra']['conditional'] ) )
	        			{
							unset( $filenames[$handle] );
							break;
						}			
						
						// if inline CSS stuff included, add to code
						if( $type === 'css' && ! empty( $file['extra']['after'] ) )
						{
							$code .= "\n" . $file['extra']['after'][0];
						}
						     			 		
		        		// CSS with relative background-image(s) but NOT "data:" / fonts set etc., convert them to absolute
		        		if( $type === 'css' && strpos( $code, 'url' ) !== FALSE && $file['filepath'] !== NULL )
		        		{
						    $code = preg_replace_callback(
						        '~url\(\s*(?![\'"]?data:)\/?(.+?)[\'"]?\s*\)~i',
						        function( $matches ) use ( $fileDirectory, $contentDir, $contentUrl )
						        {
						        	$filePath = $fileDirectory . '/' . str_replace( ['"', "'"], '', ltrim( rtrim( $matches[0], ');' ), 'url(' ) );
						        	return "url('" . esc_url( str_replace( $contentDir, $contentUrl, $filePath ) ) . "')";
						        },
						        $code
						    );
						} // END relative -> absolute
						
						// if a CSS media query file, wrap in width params
						if( $type === 'css' && strpos( $file['args'], 'width' ) !== FALSE )
						{
							$code = '@media ' . $file['args'] . ' { ' . $code . ' } ';
						}
						
						// fix URLs with // prefix so not treated as comments
						$code = str_replace( ['href="//','src="//','movie="//'], ['href="http://','src="http://','movie="http://'], $code );
						
						// braces & brackets
						$bracesBracketsLookup = [' {', ' }', '{ ', '; ', "( '", "' )", ' = ', '{ $', '{ var'];
						$bracesBracketsReplace = ['{', '}', '{', ';', "('", "')", '=', '{$', '{var'];
						
						if( $type === 'css' )
						{
							// regex adapted from: http://stackoverflow.com/q/9329552 
							$comments = '~\/\*[^*]*\*+([^/*][^*]*\*+)*\/~';
							$replace = NULL;
						}
						
						if( $type === 'js' || $type === 'core-js' )
						{
							// regex adapted from: http://stackoverflow.com/a/31907095
							// added rule for only two "//" to avoid stripping base64 lines
							// added rule for optional whitespace after "//" as some peeps do not space
							$comments = '~(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\"|\/)\/\/(?!\/+)\s?.*))~';
							$replace = NULL;
						}
						
						// strip comments
						$code = preg_replace( $comments, $replace, $code );
						
						// strip spaces in braces
						$code = str_replace( $bracesBracketsLookup, $bracesBracketsReplace, $code );
												
						// strip excessive newlines
						$code = preg_replace( '/\r/', "\n", $code );
						$code = preg_replace( '/\n+/', "\n", $code );
						
						// strip whitespace
						$code = preg_replace( '/\s+/', ' ', $code );
						
						// hacky fix for missing semicolons
						if( $type === 'js' )
						{
							if( substr( trim( $code ), -8, 8 ) === '(jQuery)' )
							{
								$code .= ';';
							}
						}
							
						$code = ltrim( $code, "\n" );
						
						$theCode .= $code;
						
						unset( $filenames[$handle] );
						
					} // END if code
					
				} // END foreach $filenames
						
				if( $type === 'css' && strpos( $theCode, '@charset "UTF-8";' ) !== FALSE )
				{
					$theCode = '@charset "UTF-8";' . "\n" . str_replace( '@charset "UTF-8";', '', $theCode );
				}
				
				$outputFile .= '.' . ( $type === 'css' ? 'css' : 'js' );
				$fh = fopen( $outputFile, 'wb' );
				fwrite( $fh, $theCode );
				fclose( $fh );
				if( $this->debug === TRUE ) $this->writeLog( 'Asset `' . $outputFile . '` written' );
				return $filenames;
			}
			else
			{				
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: $this->assetsCache directory does NOT exist - possible permissions issue' );
				return FALSE;
			}
			
		} // END refresh()
		
		/**
		* Dequeues all the assets we are replacing
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* @param string $type: Either 'css' or 'js'
		* 
		* @return bool on success
		*/
		private function requeueAssets( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'requeueAssets() running...' );
			switch( $type )
			{
				case 'css':
					foreach( $filenames as $handle => $file )
					{
						wp_dequeue_style( $handle );
						if( strpos( $handle, $this->textDomain ) === FALSE )
						{
							wp_deregister_style( $handle );
							if( $this->debug === TRUE ) $this->writeLog( 'CSS deregistered = `' . $handle . '`' );
						}
					}
					$styles = glob( $this->assetsCache . '/' . $this->styleFile . "*" );
					$styles = ltrim( str_replace( $this->rootDir, '', $styles[0] ), '/' );
					$styles = str_replace( '.php', '.css', $styles );
					wp_enqueue_style( $this->textDomain . '-styles', esc_url( site_url( $styles ) ), array(), NULL );
					if( $this->debug === TRUE ) $this->writeLog( 'CSS enqueued = `' . site_url( $styles ) . '`' );
					break;
				case 'core-js':
					foreach( $filenames as $handle => $file )
					{
						wp_deregister_script( $handle );
						if( $this->debug === TRUE ) $this->writeLog( 'Old core JS dequeued = `' . $handle . '`' );
					}
					$coreScripts = glob( $this->assetsCache . '/' . $this->coreScriptFile . "*" );
					$coreScripts = ltrim( str_replace( $this->rootDir, '', $coreScripts[0] ), '/' );
					$coreScripts = str_replace( '.php', '.js', $coreScripts );
					wp_enqueue_script( $this->textDomain . '-core', esc_url( site_url( $coreScripts ) ), array(), NULL );
					wp_deregister_script( 'jquery' );
					wp_deregister_script( 'jquery-migrate' );
					wp_register_script( 'jquery', '', array( $this->textDomain . '-core' ), NULL, TRUE );
					wp_enqueue_script( 'jquery' );
					if( $this->debug === TRUE ) $this->writeLog( 'New core JS enqueued' );
					break;
				case 'js':
					$inlineJs = '';
					foreach( $filenames as $handle => $file )
					{
						// check for inline data
						if( ! empty( $file['extra']['data'] ) )
						{
							$inlineJs .= $file['extra']['data'] . "\n";
						}
						
						if( strpos( $handle, $this->textDomain ) === FALSE )
						{
							wp_dequeue_script( $handle );
							if( $this->debug === TRUE ) $this->writeLog( 'JS script dequeued = `' . $handle . '`' );
						}
					}
					$scripts = glob( $this->assetsCache . $this->uri . $this->scriptFile . "*" );
					$scripts = ltrim( str_replace( $this->rootDir, '', $scripts[0] ), '/' );
					$scripts = str_replace( '.php', '.js', $scripts );
					$scriptsAdded = wp_register_script( $this->textDomain . '-scripties', esc_url( site_url( $scripts ) ), $this->jsDeps, NULL, TRUE );
					if( $scriptsAdded === TRUE )
					{
						wp_enqueue_script( $this->textDomain . '-scripties' );
						if( $this->debug === TRUE ) $this->writeLog( $this->textDomain . '-scripties wp_register_script SUCCESS' );
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: ' . $this->textDomain . '-scripties wp_register_script FAILED!' );
					}
					if( $inlineJs !== '' )
					{
						// strip excessive newlines
						$inlineJs = preg_replace( '/\r/', "\n", $inlineJs );
						$inlineJs = preg_replace( '/\n+/', "\n", $inlineJs );
					
						// strip whitespace
						$inlineJs = preg_replace( '/\s+/', ' ', $inlineJs );
										
						$inlineAdded = wp_add_inline_script( $this->textDomain . '-scripties', $inlineJs, 'before' );
						if( $inlineAdded === TRUE )
						{
							if( $this->debug === TRUE ) $this->writeLog( 'Inline script added = `' . $inlineJs . '`' );
						}
						else
						{
							if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Inline script add FAILED! = `' . $inlineJs . '`' );
						}
						
					}
					if( $this->debug === TRUE ) $this->writeLog( 'JS script enqueued = `' . $this->textDomain . '-scripties` with deps = `' . print_r( $this->jsDeps, TRUE ) . '`' );
					break;
					
			} // END switch type
			
			return TRUE;
			
		} // END requeueAssets()
		
		/**
		* Removes query strings from asset URLs
		* @param string $src: the src of an asset file
		* 
		* @return string: the src with version query var removed
		*/
		public function removeScriptVersion( $src )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'removeScriptVersion() running on src `' . $src . '`' );
			$parts = explode( '?ver', $src );
			return $parts[0];
			
		} // END removeScriptVersion()
		
		/**
		* Deletes a directory and its contents
		* @param string $directory: directory to empty
		* 
		* @return void
		*/
		private function recursiveRemoveDirectory( $directory )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'recursiveRemoveDirectory() running on `' . $directory . '`' );
		    if( ! $this->alternateIsDir( $directory ) )
		    {
		        if( $this->debug === TRUE ) $this->writeLog( 'ERROR: recursiveRemoveDirectory() directory `' . $directory . '` does NOT exist!' );
		        exit;
		    }
		    
		    if( substr( $directory, strlen( $directory ) - 1, 1 ) != '/' )
		    {
		        $directory .= '/';
		    }
		    
		    $files = glob( $directory . "*" );
		    
		    if( ! empty( $files ) )
		    {
			    foreach( $files as $file )
			    {
			        if( $this->alternateIsDir( $file ) )
			        {
			            $this->recursiveRemoveDirectory( $file );
			        }
			        else
			        {
			            unlink( $file );
			            clearstatcache();
			        }
			    }				
			}
		    if( $this->alternateIsDir( $directory ) ) rmdir( $directory );
		    
		} // END recursiveRemoveDirectory()
		
		/**
		* Deletes a directory and its contents
		* @param string $directory: directory to empty
		* 
		* @return void
		*/
		private function recursiveRemoveEmptyDirectory( $directory )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'recursiveRemoveEmptyDirectory() running on `' . $directory . '`' );
		    if( ! $this->alternateIsDir( $directory ) )
		    {
		        if( $this->debug === TRUE ) $this->writeLog( 'ERROR: recursiveRemoveEmptyDirectory() directory `' . $directory . '` does NOT exist!' );
		        exit;
		    }
		    
		    if( substr( $directory, strlen( $directory ) - 1, 1 ) != '/' )
		    {
		        $directory .= '/';
		    }
		    
		    $files = glob( $directory . "*" );
		    
		    if( ! empty( $files ) )
		    {
			    foreach( $files as $file )
			    {
			        if( $this->alternateIsDir( $file ) )
			        {
			            $this->recursiveRemoveEmptyDirectory( $file );
			        }
			    }				
			}
			else
			{
				if( $this->alternateIsDir( $directory ) ) rmdir( $directory );
			}		    
		    
		} // END recursiveRemoveEmptyDirectory()
		
		/**
		* Display a message
		*/
		private function notice( $message, $type = 'error' )
		{
			switch( $type )
			{
				case 'error':
					$glyph = 'thumbs-down';
					$color = '#dc3232';
					break;
				case 'updated':
					$glyph = 'thumbs-up';
					$color = '#46b450';
					break;
				case 'warning':
					$glyph = 'megaphone';
					$color = '#ff7300';
					break;
			}
			$output = '<div id="message" class="notice is-dismissible '.$type.'"><p><span style="color: '.$color.';" class="dashicons dashicons-'.$glyph.'"></span>&nbsp;&nbsp;&nbsp;<strong>';
			$output .= __( $message , $this->textDomain );
			if( $type === 'error' )
			{
				$output .= '</strong></p><p><strong>Ignore the message below that the Plugin is active, it isn\'t!';
			}
			$output .= '</strong></p></div>';
			return $output;
        } // END notice()
        
        public function messageCurlRequired()
        {
        	$message = 'Sorry, '.$this->pluginName.' requires the cURL PHP extension installed on your server. Please resolve this';
			echo $this->notice( $message );
		} // END messageCurlRequired()
        
        public function messageHtNotWritable()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' requires ".htaccess" to be writable to activate/deactivate. Some security plugins disable this. Please allow the file to be writable, for a moment. You can re-apply your security settings after activating/deactivating ' . $this->pluginName;
			echo $this->notice( $message );
		} // END messageHtNotWritable()
        
        public function messageCachingDetected()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' requires no other caching/minification Plugins be active. Please deactivate any existing Plugin(s) of this nature';
			echo $this->notice( $message );
		} // END messageCachingDetected()
        
        public function messageConflictDetected()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' does NOT work with the following Plugins due to bad/intrusive coding practices on their part. Please deactivate these Plugins if you wish to use ' . $this->pluginName;
        	$message .= '<ul>';
        	foreach( $this->conflictingPlugins as $conflictingPlugin )
        	{
				$message .= '<li>' . $conflictingPlugin['name'] . ' ~ <small>[source: <a href="' . $conflictingPlugin['ref'] . '" target="_blank" title="Opens in new window">' . $conflictingPlugin['ref'] . '</a>]</small></li>';
			}
        	$message .= '</ul>';
			echo $this->notice( $message );
		} // END messageConflictDetected()
        
        public function messageCacheFlushed()
        {
        	$message = 'Groovy! ' . $this->pluginName . ' cache has been flushed';
			echo $this->notice( $message, 'updated' );
		} // END messageCacheFlushed()
        
        public function messageSettingsSaved()
        {
        	$message = 'Awesome! ' . $this->pluginName . ' setting have been saved!';
			echo $this->notice( $message, 'updated' );
		} // END messageSettingsSaved()
		
		/**
		* Add links below plugin description
		* @param array $links: The array having default links for the plugin
		* @param string $file: The name of the plugin file
		* 
		* @return array $links: The new links array
		*/
		public function pluginMetaLinks( $links, $file )
		{
			if ( $file == plugin_basename( dirname( __FILE__ ) . '/wp-roids.php' ) )
			{
				$links[] = '<a href="' . $this->donateLink . '" target="_blank" title="Opens in new window">' . __( 'Donate via PayPal', $this->textDomain ) . '</a>';
			}
			return $links;
			
		} // END pluginMetaLinks()
		
		/**
		* Add links when viewing "Plugins"
		* @param array $links: The links that appear by "Deactivate" under the plugin name
		* 
		* @return array $links: Our new set of links
		*/
		public function pluginActionLinks( $links )
		{
			$mylinks = array(
				'<a href="' . esc_url( admin_url( 'edit.php?page=' . $this->textDomain ) ) . '">Configuração</a>',
				$this->flushCacheLink(),
				);
			return array_merge( $links, $mylinks );
			
		} // END pluginActionLinks()
		
		/**
		* Generates a clickable "Flush Cache" link
		* 
		* @return string HTML
		*/
		private function flushCacheLink( $linkText = 'Limpar Cache' )
		{
			$url = admin_url( 'admin.php?page=' . $this->textDomain );
			$link = wp_nonce_url( $url, $this->nonceAction, $this->nonceName );
			return sprintf( '<a class="flush-link" href="%1$s">%2$s</a>', esc_url( $link ), $linkText );
			
		} // END flushCacheLink()
		
		/**
		* Add "Flush Cache" link to Admin Bar
		* @param object $adminBar
		* 
		* @return void
		*/
		public function adminBarLinks( $adminBar )
		{
			if( current_user_can( 'install_plugins' ) )
			{
				$url = admin_url( 'admin.php?page=' . $this->textDomain );
				$link = wp_nonce_url( $url, $this->nonceAction, $this->nonceName );
				$adminBar->add_menu(
					[ 'id' => $this->textDomain . '-flush',
					'title' => 'Flush ' . $this->pluginName . ' Cache',
					'href'  => esc_url( $link ),
					] );
			}
			
		} // END adminBarLinks()
		
		/**
		* Add Credit Link to footer if enabled
		* 
		* @return void
		*/
		public function creditLink()
		{
			if( $this->settingCreditLink === TRUE )
			{
				echo '<p id="' . $this->textDomain . '-credit" style="clear:both;float:right;margin:0.5rem 1.75rem;font-size:11px;position:relative;transform:translateY(-250%);z-index:50000;"><a href="https://wordpress.org/plugins/wp-roids/" target="_blank" title="' . $this->pluginStrapline . ' | Opens in new tab/window">Performance enhanced by ' . $this->pluginName . '</a></p><div style="clear:both;"></div>';
			}
			
		} // END creditLink()
		
		/**
		* Called on plugin deactivation - cleans everything up as if we were never here :)
		* @return void
		*/
		public function deactivate()
		{			
			// .htaccess needs to be writable, some security plugins disable this
			// only perform this check when an admin is logged in, or it'll deactivate the plugin :/
			if( current_user_can( 'install_plugins' ) )
			{		
				$htaccess = $this->rootDir . '/.htaccess';
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN fast caching - DO NOT REMOVE THIS LINE';
				$endtext = '### END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) !== FALSE && strpos( $current, $endtext ) !== FALSE )
				{
					// .htaccess needs editing
					$desiredPerms = fileperms( $htaccess );
					chmod( $htaccess, 0644 );
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: function deactivate() `.htaccess` NOT writable!' );
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						return FALSE;
					}
					else
					{
						// restore the .htaccess file
						$pos = strpos( $current, $starttext );
						$startpoint = $pos === FALSE ? NULL : $pos;
						$pos = strrpos( $current, $endtext, $startpoint );
						$endpoint = $pos === FALSE ? NULL : $pos + strlen( $endtext );
						if( $startpoint !== NULL && $endpoint !== NULL )
						{
							$restore = substr_replace( $current, '', $startpoint, $endpoint - $startpoint);
							$fh = fopen( $htaccess, 'wb' );
							fwrite( $fh, $restore );
							fclose( $fh );
							chmod( $htaccess, $desiredPerms );
						}
					}
				} // END .htaccess needs editing
				
				// remove 1.* versions' code
				$current = file_get_contents( $htaccess );
				$starttext = '# BEGIN fast caching - DO NOT REMOVE THIS LINE';
				$endtext = '# END fast caching - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) !== FALSE && strpos( $current, $endtext ) !== FALSE )
				{
					// .htaccess needs editing
					$desiredPerms = fileperms( $htaccess );
					chmod( $htaccess, 0644 );
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: function deactivate() `.htaccess` NOT writable!' );
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						return FALSE;
					}
					else
					{
						// restore the .htaccess file
						$pos = strpos( $current, $starttext );
						$startpoint = $pos === FALSE ? NULL : $pos;
						$pos = strrpos( $current, $endtext, $startpoint );
						$endpoint = $pos === FALSE ? NULL : $pos + strlen( $endtext );
						if( $startpoint !== NULL && $endpoint !== NULL )
						{
							$restore = substr_replace( $current, '', $startpoint, $endpoint - $startpoint);
							$fh = fopen( $htaccess, 'wb' );
							fwrite( $fh, $restore );
							fclose( $fh );
							chmod( $htaccess, $desiredPerms );
						}
					}
				} // END .htaccess needs editing
					
				$backup = __DIR__ . '/ht-backup.txt';
				if( file_exists( $backup ) )
				{
					unlink( $backup );
					clearstatcache();
				}
				
				$log = __DIR__ . '/log.txt';
				if( file_exists( $log ) )
				{
					unlink( $log );
					clearstatcache();
				}
				
				// remove cache
				if( $this->alternateIsDir( $this->cacheDir ) ) $this->recursiveRemoveDirectory( $this->cacheDir );
				
				// kill the schedule
				$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
				if( $scheduleTimestamp !== FALSE )
				{
					wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
				}
			
			} // END if user can activate plugins	
			
		} // END deactivate()
		
		/**
		* Called on uninstall - actually does nothing at present
		* @return void
		*/
		public static function uninstall()
		{
			global $wpdb;
			$theClass = self::instance();
			$theClass->deactivate();				
			// delete plugin options
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE \'' . $theClass->textDomain . '%\'' );			
			
		} // END uninstall()
		
		/**
		* Create or return instance of this class
		*/
		public static function instance()
		{
			$className = get_class();
			if( ! isset( self::$instance ) && ! ( self::$instance instanceof $className ) && ( self::$instance === NULL ) )
			{
				self::$instance = new $className;
			}
			return self::$instance;
			
		} // END instance()
		
		/**
		* load admin scripts
		*/
		public function loadAdminScripts()
		{
			wp_enqueue_style( $this->textDomain.'-admin-webfonts', 'https://fonts.googleapis.com/css?family=Roboto:400,700|Roboto+Condensed', array(), NULL );
			wp_enqueue_style( $this->textDomain.'-admin-semantic-css', 'https://unpkg.com/semantic-ui@2.4.2/dist/semantic.min.css', array(), NULL );
			wp_enqueue_style( $this->textDomain.'-admin-styles', plugins_url( 'css-admin.css' , __FILE__ ), array(), NULL );
			wp_enqueue_script( $this->textDomain.'-admin-semantic-js', 'https://unpkg.com/semantic-ui@2.4.2/dist/semantic.min.js', array( 'jquery' ), NULL );
			wp_enqueue_script( $this->textDomain.'-admin-scripts', plugins_url( 'js-admin.js' , __FILE__ ), array( 'jquery' ), NULL );
			
		} // END loadAdminScripts()
		
		/**
		* add admin menu
		*/
		public function adminMenu()
		{
			// see https://developer.wordpress.org/reference/functions/add_menu_page
			add_menu_page( $this->pluginName, $this->pluginName, 'install_plugins', $this->textDomain, array( $this, 'adminPage' ), 'dashicons-dashboard', '80.01' );
			
		} // END adminMenu()
		
		/**
		* our admin page
		*/
		public function adminPage()
		{
			if( isset( $_REQUEST[$this->nonceName] ) && wp_verify_nonce( $_REQUEST[$this->nonceName], $this->nonceAction ) )
			{
				if( $_POST )
				{
					// settings form submitted
					update_option( $this->textDomain.'_settings', NULL );
					$settings = array();
					foreach( $_POST as $key => $setting )
					{
						if( $setting === 'true' )
						{
							$settings[$key] = array( 'disabled' => TRUE );
							if( $key === 'imgs' && $this->alternateIsDir( $this->imgCache ) )
							{
								$this->recursiveRemoveDirectory( $this->imgCache );
							}
						}
						elseif( $key === 'imgs-quality-jpeg' || $key === 'imgs-quality-png' || $key === 'schedule' || $key === 'debug' || $key === 'credit' )
						{
							$settings[$key] = array( 'value' => $setting );
							if( $key === 'imgs-quality-jpeg' && is_numeric( $setting ) )
							{
								$this->compressionLevelJpeg = intval( $setting );
							}
							if( $key === 'imgs-quality-png' && is_numeric( $setting ) )
							{
								$this->compressionLevelPng = intval( $setting );
							}
							if( $key === 'debug' && $setting === 'disabled' )
							{
								$this->debug = FALSE;
								$log = __DIR__ . '/log.txt';
								if( file_exists( $log ) )
								{
									unlink( $log );
									clearstatcache();
								}
							}
							if( $key === 'debug' && $setting === 'enabled' )
							{
								$this->debug = TRUE;
							}
							if( $key === 'schedule' && $setting === 'disabled' )
							{
								$this->settingFlushSchedule = FALSE;
								// kill the schedule
								$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
								if( $scheduleTimestamp !== FALSE )
								{
									wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
									if( $this->debug === TRUE ) $this->writeLog( 'CRON schedule killed!' );
								}
							}
							if( $key === 'schedule' && $setting !== 'disabled' )
							{					
								// set event to flush posts
								$this->settingFlushSchedule = $setting;
								// kill the schedule
								$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
								if( $scheduleTimestamp !== FALSE )
								{
									wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
								}
								if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
								{
								    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
									if( $this->debug === TRUE ) $this->writeLog( 'CRON schedule set!' );
								}				
							}
							if( $key === 'credit' && $setting === 'enabled' )
							{
								$this->settingCreditLink = TRUE;
							}
						}
					}
					update_option( $this->textDomain.'_settings', $settings );
					if( $this->debug === TRUE ) $this->writeLog( 'Settings updated!' );				
				}
				
				$this->flushWholeCache();
			}			
			$this->settings = get_option( $this->textDomain.'_settings', NULL );
			if( isset( $_REQUEST[$this->nonceName] ) && wp_verify_nonce( $_REQUEST[$this->nonceName], $this->nonceAction ) )
			{
				
				$this->messageCacheFlushed();
				if( $_POST ) $this->messageSettingsSaved();
			}	
			?>
			<div class="wrap">
				<p class="right">					
					<?php
					if( ! isset( $_REQUEST[$this->nonceName] ) )
					{
						echo $this->flushCacheLink( 'Empty the cache!' );
					}					
					?>
				</p>
				<h1><span class="dashicons dashicons-dashboard"></span>&nbsp;<?php echo $this->pluginName ;?></h1>
				<div class="clear"></div>
				<h4><?php echo $this->pluginStrapline; ?></h4>
				<p class="like">&hearts; <small>Gostou do Plguin /Like this plugin?&nbsp;&nbsp;&nbsp;</small><a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N"<?php echo $this->donateLink; ?>" target="_blank" title="Opens in new tab/window">Doação/Donate!</a>&nbsp;&nbsp;<a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N" target="_blank"></a></p>
				
				<div class="ui top attached tabular menu">
					<a class="active item" data-tab="first">Visão geral/Overview</a>
					<a class="item" data-tab="second">Configurações/Settings</a>
					<a class="item" data-tab="third">&quot;Isso quebrou meu site!/It Broke My Site!&quot;</a>
					<?php
					if( isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'apache' ) === FALSE )
					{
					?>
					<!--
					// TODO: for future release....
					// ref: https://wordpress.org/support/article/nginx/
					// ref: https://rijasta.wordpress.com/2017/09/07/iis-server-and-wordpress-cache-plugin/
					<a class="item" data-tab="fourth">NGINX / Windows IIS</a>
					-->
					<?php
					}
					if( $this->debug === TRUE )
					{
					?>
					<a class="item" data-tab="fifth">Debug Log</a>
					<?php
					}
					?>
				</div>
				
<div class="ui bottom attached active tab segment" data-tab="first">
    <div class="fadey">
        <h2>Instruções / Instructions</h2>
        <p><big><strong><?php echo $this->pluginName; ?> <em>deve</em> funcionar sem problemas</strong>, com a intenção de, "Keep It Simple, Stupid" <abbr title="Keep It Simple, Stupid">(KISS)</abbr></big> <sup>[<a href="https://en.wikipedia.org/wiki/KISS_principle" target="_blank" title="&quot;Keep It Simple, Stupid&quot; | Opens in new tab/window">?</a>]</sup></p>
        <p>If you <em>deseja</em> modificar ou debugar, vá para a aba de "Configurações". / If you <em>want</em> to tinker/debug, go to the "Settings" tab.</p>
        <h3>Para Verificar Se <?php echo $this->pluginName; ?> Está Funcionando / To Check <?php echo $this->pluginName; ?> Is Working</h3>
        <ul>
            <li>Veja o código fonte <sup>[<a href="http://www.computerhope.com/issues/ch000746.htm" target="_blank" title="How to view your website source code | Opens in new tab/window">?</a>]</sup> de uma Página/Postagem <strong>quando você estiver desconectado do WordPress<sup>&reg;</sup> e tiver atualizado a Página/Postagem DUAS VEZES</strong> / View the source code <sup>[<a href="http://www.computerhope.com/issues/ch000746.htm" target="_blank" title="How to view your website source code | Opens in new tab/window">?</a>]</sup> of a Page/Post <strong>when you are logged out of WordPress<sup>&reg;</sup> and have refreshed the Page/Post TWICE</strong></li>
            <li>No final, <strong>você deve ver um comentário HTML</strong> assim: <code>&lt;!-- Arquivo de cache HTML estático gerado em <?php echo date( 'M d Y H:i:s' ) . ' (' . $this->timezone . ')'; ?> por <?php echo $this->pluginName; ?> plugin --&gt;</code> / At the very bottom, <strong>you should see an HTML comment</strong> like this: <code>&lt;!-- Static HTML cache file generated at <?php echo date( 'M d Y H:i:s' ) . ' (' . $this->timezone . ')'; ?> by <?php echo $this->pluginName; ?> plugin --&gt;</code></li>
        </ul>
    </div>
    <div class="pkm-panel pkm-panel-primary fadey">
        <h2>Pedido Educado&#40;s&#41;&hellip; / Polite Request&#40;s&#41;&hellip;</h2>
        <p><big>Eu disponibilizei <?php echo $this->pluginName; ?> <strong>totalmente GRATUITO</strong>. Sem custos adicionais para atualizações, suporte, etc. <strong>TUDO É GRÁTIS!</strong></big><br>Isso me consome MUITO tempo para programar, testar, reprogramar, etc. Tempo pelo qual não sou pago. Nesse sentido, eu gentilmente peço o seguinte de vocês:</p>
        <ul>
            <li>
                <h3>Usuários Não-Lucrativos / Não-Comerciais / Non-Profit / Non-Commercial Users</h3>
                <p><big>Por favor, considere <a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N" target="_blank" title="Opens in new tab/window">dar ao <?php echo $this->pluginName; ?> uma Avaliação de 5 Estrelas &#9733;&#9733;&#9733;&#9733;&#9733;</a> para impulsionar sua popularidade / Please consider <a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N" target="_blank" title="Opens in new tab/window">giving <?php echo $this->pluginName; ?> a 5 Star &#9733;&#9733;&#9733;&#9733;&#9733; Review</a> to boost its popularity</big></p>
            </li>
            <li>
                <h3>Proprietários de Websites de Negócios / Comerciais / Business / Commercial Website Owners</h3>
                <p><big>Como acima, mas uma pequena doação em dinheiro via <a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N"<?php echo $this->donateLink; ?>" target="_blank" title="Opens in new tab/window">PayPal</a> também seria muito apreciada / As above, but a small cash donation via <a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N"<?php echo $this->donateLink; ?>" target="_blank" title="Opens in new tab/window">PayPal</a> would also be gratefully appreciated</big></p>
            </li>
            <li>
                <h3>Desenvolvedores do WordPress<sup>&reg;</sup> / WordPress<sup>&reg;</sup> Developers</h3>
                <p><big>Novamente, como acima. No entanto, eu adoraria uma doação / Again, as above. However, I would LOVE<br>
                Você sempre pode cobrar de seu cliente! ;) / You can always bill it to your client! ;)</big></p>
            </li>
            <li>
                <h3>Todos / Everybody</h3>
                <p><big>Finalmente, na parte inferior da <label for="tab-2">aba de Configurações</label>, há uma opção para adicionar um link pequeno e discreto no canto inferior direito do seu site para a página inicial do <?php echo $this->pluginName; ?> no Repositório de Plugins do WordPress<sup>&reg;</sup>. Eu realmente agradeceria se você ativasse isso &#40;se parecer ok&#41; / Finally, at the bottom of the <label for="tab-2">Settings tab</label>, there is an option to add a small, unintrusive link at the bottom right of your website to the <?php echo $this->pluginName; ?> home page at the WordPress<sup>&reg;</sup> Plugin Repository. I would really appreciate you enable this &#40;if it looks okay&#41;</big></p>
            </li>
        </ul>
        <p><big>Obrigado pelo seu tempo e apoio! / Thanks for your time and support!</big></p>
        <p><big>Phil :)</big></p>
        <p class="like">&hearts; <small>Gostou do Plugin / Like this plugin?&nbsp;&nbsp;&nbsp;</small><a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N"<?php echo $this->donateLink; ?>" target="_blank" title="Opens in new tab/window">Doação / Donate!</a>&nbsp;&nbsp;<a href="https://www.paypal.com/donate/?hosted_button_id=DESANPECWW29N" target="_blank"></a></p>
    </div>
</div>
				<div class="ui bottom attached tab segment" data-tab="second">
							<form action="" method="POST" id="<?php echo $this->textDomain.'-form';?>">
								<input style="display: none;" type="checkbox" name="scroll-hack" value="null" checked>
<div class="pkm-panel pkm-panel-primary-alt fadey">
    <h2>Configurações Principais / Core Settings</h2>
    <p>
        <big>Injete <?php echo $this->pluginName; ?> para realizar o seguinte:</big><br>
        <small>Deixe idealmente todos esses itens marcados em verde. Se houver <em>um</em> conflito de plugins — em primeiro lugar, tente desativar a otimização de JS para esse plugin na seção "Plugins JavaScript" abaixo, <em>antes</em> de desativar qualquer uma dessas opções.</small>
    </p>
    <div>
        <input type="checkbox" id="cache" name="cache" value="true"<?php echo( intval( $this->settings['cache']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="cache">Gerar arquivos <abbr title="HyperText Markup Language">HTML</abbr> estáticos em cache <sup>[<a href="https://www.maxcdn.com/one/visual-glossary/web-cache/" target="_blank" title="What is a Web Cache? | Opens in new tab/window">1</a>,<a href="https://en.wikipedia.org/wiki/Web_cache" target="_blank" title="Web cache [Wikipedia] | Opens in new tab/window">2</a>]</sup></label>
    </div>
    <div>
        <input type="checkbox" id="imgs" name="imgs" value="true"<?php echo( intval( $this->settings['imgs']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="imgs">
            Comprimir imagens carregadas via <abbr title="HyperText Markup Language">HTML</abbr> <small>&#40;não imagens de fundo <abbr title="Cascading Style Sheets">CSS</abbr> <code>background</code>&#41;</small>
        </label>
        <div class="sub-options">
            <h5>Qualidade da Imagem JPEG</h5>
            <table cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="30%"><small>Qualidade inferior, carregamento mais rápido</small></td>
                    <td width="40%"><input type="range" id="imgs-quality-jpeg" name="imgs-quality-jpeg" min="10" max="80" value="<?php echo intval( $this->compressionLevelJpeg ); ?>" step="5"></td>
                    <td width="30%"><small>Qualidade superior, carregamento mais lento</small></td>
                </tr>
            </table>
            <h5>Qualidade da Imagem PNG</h5>
            <table cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td width="30%"><small>Qualidade inferior, carregamento mais rápido</small></td>
                    <td width="40%"><input type="range" id="imgs-quality-png" name="imgs-quality-png" min="10" max="80" value="<?php echo intval( $this->compressionLevelPng ); ?>" step="5"></td>
                    <td width="30%"><small>Qualidade superior, carregamento mais lento</small></td>
                </tr>
            </table>
        </div>
    </div>
    <div>
        <input type="checkbox" id="html" name="html" value="true"<?php echo( intval( $this->settings['html']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="html">Minificar <abbr title="HyperText Markup Language">HTML</abbr></label>
    </div>
    <div>
        <input type="checkbox" id="defer" name="defer" value="true"<?php echo( intval( $this->settings['defer']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="defer">Adiar o carregamento do arquivo <abbr title="JavaScript">JS</abbr> minificado gerado pelo <?php echo $this->pluginName; ?></label>
    </div>
    <div>
        <input type="checkbox" id="theme" name="theme" value="true"<?php echo( intval( $this->settings['theme']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="theme">Minificar e reunir <abbr title="Cascading Style Sheets">CSS</abbr> &amp; <abbr title="JavaScript">JS</abbr> no tema atual "<?php echo $this->theme->Name; ?>"</label>
    </div>
    <div>
        <input type="checkbox" id="cdn" name="cdn" value="true"<?php echo( intval( $this->settings['cdn']['disabled'] ) === 1 ? ' checked' : ''); ?>>
        <label for="cdn">Minificar e reunir <abbr title="Cascading Style Sheets">CSS</abbr> &amp; <abbr title="JavaScript">JS</abbr> carregados via <abbr title="Content Delivery Network">CDN</abbr></label>
    </div>
    <?php submit_button(); ?>
</div>

<div class="pkm-panel pkm-panel-primary-alt fadey">
    <h2>JavaScript dos Plugins / Plugins JavaScript</h2>
    <p>
        <big>Clique nos nomes dos plugins para alternar a otimização de quaisquer ativos <abbr title="JavaScript">JS</abbr> na fila.</big><br>
        <small>&#40;Útil para depuração&#41;</small>
    </p>		
									<?php
									$allPlugins = get_plugins();
									$ignores = array(
										$this->pluginName,
										'iThemes Security',
										'Wordfence Security',
									);
									foreach( $allPlugins as $pluginFile => $pluginInfo )
									{
										$allPlugins[$pluginFile]['isActive'] = is_plugin_active( $pluginFile ) ? 'true' : 'false';
										$pluginKey = array_search( $pluginFile, array_column( $this->cachingPlugins, 'slug' ) );
										if( ( $pluginKey !== FALSE && is_numeric( $pluginKey ) ) || in_array( $pluginInfo['Name'], $ignores ) )
										{
											unset( $allPlugins[$pluginFile] );
										}
										
									}
									foreach( $allPlugins as $pluginFile => $pluginInfo )
									{
										$pluginFileKey = str_replace( '.', '_', $pluginFile );
										if( $this->settings !== NULL && isset( $this->settings[$pluginFileKey] ) )
										{
											$pluginSettings = $this->settings[$pluginFileKey];
										}
										else
										{
											$pluginSettings = array(
												'disabled' => FALSE,
											);
										}
										?>
										<div>
										<input type="checkbox" id="<?php echo $pluginFile; ?>" name="<?php echo $pluginFile; ?>" value="true"<?php echo( intval( $pluginSettings['disabled'] ) === 1 ? ' checked' : ''); ?><?php echo( $pluginInfo['isActive'] === 'false' ? ' disabled' : ''); ?>>
										<label for="<?php echo $pluginFile; ?>">&nbsp;<?php echo $pluginInfo['Name']; ?></label>
										</div>
										<?php
									}
									?>
									<?php submit_button(); ?>
								</div>
								
								<div class="pkm-panel pkm-panel-primary-alt fadey">
									<h2>Additional Settings</h2>
									<?php
									$scheduleOptions = array(
										[ 'slug' => 'every_five_minutes', 'name' => 'Every 5 Minutes' ],
										[ 'slug' => 'hourly', 'name' => 'Hourly' ],
										[ 'slug' => 'daily', 'name' => 'Daily' ],
										[ 'slug' => 'weekly', 'name' => 'Weekly' ],
										[ 'slug' => 'disabled', 'name' => 'Never' ],
									);
									$scheduleOptions = array_reverse( $scheduleOptions );
									?>
									<div class="field-wrap">
										<big>Flush Posts Cache Schedule</big>
										<span class="dashicons dashicons-clock<?php echo( ! isset( $this->settings['schedule'] ) || $this->settings['schedule']['value'] === 'disabled' ? ' off' : '' ); ?>"></span>
										<?php
										foreach( $scheduleOptions as $scheduleOption )
										{
										?>
										<span>
											<input type="radio" name="schedule" id="schedule-<?php echo $scheduleOption['slug']; ?>" value="<?php echo $scheduleOption['slug']; ?>"<?php echo( $scheduleOption['slug'] === $this->settings['schedule']['value'] ? ' checked' : c && $scheduleOption['slug'] === 'disabled' ? ' checked' : '' ); ?>>
											<label for="schedule-<?php echo $scheduleOption['slug']; ?>"<?php echo( $scheduleOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $scheduleOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="field-wrap">
										<big>Footer Credit Link</big>
										<span class="dashicons dashicons-admin-links<?php echo( $this->settings['credit']['value'] === 'disabled' ? ' off' : ! isset( $this->settings['credit'] ) ? ' off' : '' ); ?>"></span>
										<?php
										$debugOptions = array(
											[ 'slug' => 'enabled', 'name' => 'On' ],
											[ 'slug' => 'disabled', 'name' => 'Off' ],
										);
										$debugOptions = array_reverse( $debugOptions );
										foreach( $debugOptions as $debugOption )
										{
										?>
										<span>
											<input type="radio" name="credit" id="credit-<?php echo $debugOption['slug']; ?>" value="<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === $this->settings['credit']['value'] ? ' checked' : ! isset( $this->settings['credit'] ) && $debugOption['slug'] === 'disabled' ? ' checked' : '' ); ?>>
											<label for="credit-<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $debugOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="field-wrap">
										<big>Debug Log</big>
										<span class="dashicons dashicons-admin-tools<?php echo( $this->settings['debug']['value'] === 'disabled' ? ' off' : ! isset( $this->settings['debug'] ) ? ' off' : '' ); ?>"></span>
										<?php
										$debugOptions = array(
											[ 'slug' => 'enabled', 'name' => 'On' ],
											[ 'slug' => 'disabled', 'name' => 'Off' ],
										);
										$debugOptions = array_reverse( $debugOptions );
										foreach( $debugOptions as $debugOption )
										{
										?>
										<span>
											<input type="radio" name="debug" id="debug-<?php echo $debugOption['slug']; ?>" value="<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === $this->settings['debug']['value'] ? ' checked' : ! isset( $this->settings['debug'] ) && $debugOption['slug'] === 'disabled' ? ' checked' : '' ); ?>>
											<label for="debug-<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $debugOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="clear"></div>
									<?php submit_button(); ?>
								</div>
								<?php wp_nonce_field( $this->nonceAction, $this->nonceName ); ?>
							</form>
				</div>
				<div class="ui bottom attached tab segment" data-tab="third">
<div class="pkm-panel pkm-panel-warning fadey">
    <h2>Resolução de Problemas / Troubleshooting Errors</h2>
    <p>I testei <?php echo $this->pluginName; ?> em vários sites que construí e funciona bem.</p>
    <p>No entanto, não posso levar em conta conflitos com os milhares de Plugins e Temas de outras fontes, alguns dos quais <em>podem</em> ser mal codificados.</p>
    <p><strong>Se isso acontecer com você, siga as seguintes etapas, tendo sua página inicial aberta em outro navegador. Ou faça logout após cada alteração de configuração se estiver usando o mesmo navegador. Após cada etapa, atualize sua página inicial DUAS VEZES</strong></p>
    <ol class="big">
        <li>Altere o tema do seu site para "Twenty Nineteen" &#40;ou um dos outros temas "Twenty&hellip;"&#41;. Se funcionar, você tem um tema problemático</li>
        <li>Se ainda estiver quebrado, vá para a <a href="<?php echo admin_url( 'plugins.php' ); ?>">página de Plugins do WordPress<sup>&reg;</sup></a> e desative todos os Plugins &#40;exceto <?php echo $this->pluginName; ?>, obviamente&#41;. Se <?php echo $this->pluginName; ?> começar a funcionar, temos um conflito de plugins</li>
        <li>Reative cada plugin um por um e atualize sua página inicial cada vez até quebrar</li>
        <li>Se <em>ainda</em> estiver quebrado após a etapa acima, vá para a <label for="tab-2">aba de Configurações</label> e tente desativar a otimização de <abbr title="JavaScript">JS</abbr> para o Plugin que provocou um erro na etapa anterior - isso é feito na segunda seção "Plugins JavaScript"</li>
        <li>Por fim, se não houve melhoria, vá para a <label for="tab-2">aba de Configurações</label> e experimente alternar opções na primeira seção "Configurações Principais"</li>
    </ol>

    <p>Responderei às questões o mais rapidamente possível</p>
</div>

				<?php
				if( isset( $_SERVER['SERVER_SOFTWARE'] ) && stripos( $_SERVER['SERVER_SOFTWARE'], 'apache' ) === FALSE )
				{
				?>
				<!--<div class="ui bottom attached tab segment" data-tab="fourth">
					[TBC]
				</div>-->
				<?php
				}
				if( $this->debug === TRUE )
				{
				?>
				<div class="ui bottom attached tab segment" data-tab="fifth">
					<?php $this->wpRoidsDebug(); ?>
				</div>
				<?php
				}
				?>
			</div>
			<?php
			
		} // END adminPage()
		
	} // END class WPRoidsPhil
	
	// fire her up!
	WPRoidsPhil::instance();
	
} // END if class_exists()
