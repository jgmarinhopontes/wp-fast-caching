=== fast caching ===

Contributors: jgmarinhopontes
Tags: cache,performance,pagespeed,optimize,caching
Requires at least: 4.2
Tested up to: 5.7.2
Requires PHP: 5.6.0
Stable tag: 4.0.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

The fastest caching plugin for WordPress. FASTER than WP Super Cache, W3 Total Cache, WP Fastest Cache and many more...

== Description ==

**Fast AF caching! Optimize your website performance for the best page speed**

- Minifies HTML, JavaScript and CSS files
- Defers loading of JavaScript
- Compresses JPEG and PNG images
- Generates static HTML pages

Try it and see the results for yourself...

= Getting Started =

No complicated settings to deal with;

1. Deactivate/delete any current caching or minification plugins
2. Install fast caching
3. Activate fast caching
4. Log out
5. Refresh your home page TWICE

= "It broke my site, arrrrrrgh!" =

**Do the following steps, having your home page open in another browser. Or log out after each setting change if using the same browser. After each step refresh your home page TWICE**

1. Switch your site's theme to "Twenty Twenty" (or one of the other "Twenty..." Themes). If it then works, you have a moody theme
2. If still broken, go to the WordPress Plugins page and disable all Plugins (except fast caching, obviously). If fast caching starts to work, we have a plugin conflit
3. Reactivate each plugin one by one and refresh your home page each time time until it breaks
4. If _still_ broken after the above step, go to the Settings tab and try disabling JS optimisation for the Plugin which triggered an error in the previous step - this is done in the second section "Plugins JavaScript"
5. Finally, if no improvement has occurred, go to the Settings tab and experiment with toggling options in the first section "Core Settings"
6. If you have the time, it would help greatly if you can [log a support topic on the Support Page](https://wordpress.org/support/plugin/wp-roids) and tell me as much as you can about what happened

= How Fast Is It? =

In testing, fast caching was FASTER than:

- WP Super Cache
- W3 Total Cache
- WP Fastest Cache
- Comet Cache
- Autoptimize
- WP Speed of Light
- ...and many more!

= Where Can I Check Site Speed? =

Either of these two sites is good:

- [Pingdom](https://tools.pingdom.com)
- [GTmetrix](https://gtmetrix.com)

= Software Requirements =

In addition to the [WordPress Requirements](https://wordpress.org/about/requirements/), fast caching requires the following:

-	**`.htaccess` file to be writable**

	some security plugins disable this, so just turn this protection off for a minute during install/activation	
-	**PHP version greater than 5.6.0**

	It WILL throw errors on activation if not! No damage should occur though	
-	**PHP cURL extension enabled**

	Usually is by default on most decent hosts

== Installation ==

**NOTE:** fast caching requires the `.htaccess` file to have permissions set to 644 at time of activation. Some security plugins (quite rightly) change the permissions to disable editing. Please temporarily disable this functionality for a moment whilst activating fast caching.

The quickest and easiest way to install is via the `Plugins > Add New` feature within WordPress. But if you must, manual installation instructions follow...

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.

== Frequently Asked Questions ==

= Is fast caching compatible with iThemes Security? =

Yes. But if you have `.htaccess` protected (**Security > Settings > System Tweaks**), you will need to disable this when activating fast caching. You can re-enable it after activation

= Is fast caching compatible with Jetpack? =

Yes

= Is fast caching compatible with Yoast SEO? =

Yes

= Is fast caching compatible with WooCommerce? =

Yes

= Is fast caching compatible with Storefront Theme for WooCommerce? =

Yes

= Is fast caching compatible with Storefront Pro (Premium) plugin for WooCommerce? =

Yes

= Is fast caching compatible with WPBakery Visual Composer? =

Yes

= Is fast caching compatible with Page Builder by SiteOrigin? =

Yes

= Is fast caching compatible with Genesis Framework and Themes? =

Yes. Though I have only tested with Metro Pro theme

= Is fast caching compatible with the "Instagram Feed" plugin? =

Yes, but you must tick the "Are you using an Ajax powered theme?" option to "Yes"

= Is fast caching compatible with the "Disqus Comment System" plugin? =

Yes

= Is fast caching compatible with the "Visual Form Builder" plugin? =

Yes

= Is fast caching compatible with the "Simple Share Buttons Adder" plugin? =

Yes

= Is fast caching compatible with the "Revolution Slider" plugin? =

Yes, but you have to disable minification and caching of its JavaScript in the fast caching Settings

= Is fast caching compatible with the "WordPress Gallery Plugin – NextGEN Gallery" plugin? =

No. This plugin intrusively removes queued JavaScript. [See this support question](https://wordpress.org/support/topic/all-marketing-crappy-product-does-not-even-follow-good-coding-practices)

= Is fast caching compatible with Cloudflare? =

Yes, but you may want to flush your Cloudflare cache after activating fast caching. Also disable the minification options at Cloudflare

= Does fast caching work on NGINX servers? =

Yes, it uses a fallback for non-Apache configurations

= Does fast caching work on IIS (Windows) servers? =

Yes, it uses a fallback for non-Apache configurations

== Upgrade Notice ==

= v4.0.0 =

Archive/Category/Tag pages NOW cached
Lots of background activity reduced

= v3.2.4 =

PHP warning for a foreach loop
Type forcing in PHP functions
Donation URL ;)

= v3.2.3 =

Error warning pedantry for PHP DOMDocument

= v3.2.1 =

Logic condition regarding DONOTCACHEPAGE tweaked

= v3.2.0 =

Logic condition was preventing some storing of HTML files

= v3.1.1 =

Minor bug fix affecting some users

= v3.1.0 =

Fixes to image compression

= v3.0.0 =

Massive new release/overhaul, upgrade ASAFP!

= v2.2.0 =

More regex fixes on asset minification

= v2.1.1 =

Regex fixes for `data:image` and `base64` strings

= v2.1.0 =

Formatting error fix for Visual Composer

= v2.0.1 =

Minor tweak

= v2.0.0 =

Urgent update, install immediately!

= v1.3.6 =

Compatibility check that caused deactivation fixed

= v1.3.5 =

JS comment crash fixed

= v1.3.4 =

rewritebase error, was killing some sites dead AF, sorry

= v1.3.3 =

Minor fixes

= v1.3.1 =

Switched off debugger

= v1.3.0 =

Another HUGE update! Recommend deactivating and reactivating after update to ensure correct operation

= v1.2.0 =

HUGE update! Recommend deactivating and reactivating after update to ensure correct operation

= v1.1.4 =

More asset enqueuing issue fixes

= v1.1.3 =

Asset enqueuing issue fixes

= v1.1.2 =

Version numbering cockup

= v1.1.1 =

Issue fixes

= v1.1.0 =

Several improvements and issue fixes

= v1.0.0 =

Requires WordPress 4.2+

== Changelog ==

= v4.0.2 =

- **Fixed:** Caching already cached files is not clever :/

= v4.0.1 =

- **Rollback:** Type-hinting on PHP functions as many folks not yet on PHP 7

= v4.0.0 =

- **Improved:** Archive/Category/Tag pages NOW cached
- **Improved:** Lots of background activity reduced

= v3.2.4 =

- **Fixed:** PHP warning for a foreach loop
- **Improved:** Type forcing in PHP functions
- **Tweaked:** Donation URL ;)

= v3.2.3 =

- **Improved:** Error warning pedantry for PHP DOMDocument

= v3.2.1 =

- **Improved:** Logic condition regarding DONOTCACHEPAGE tweaked

= v3.2.0 =

- **Fixed:** Logic condition was preventing some storing of HTML files

= v3.1.1 =

- **Fixed:** Bug caused by variable named incorrectly

= v3.1.0 =

- **Fixed:** Image compression sometimes causing Server Errors

- **Improved:** Image compression levels editable in Settings

= v3.0.0 MASSIVE NEW RELEASE! MUCH AWESOMENESS! =

- **Fixed:** Inline JavaScript was not being loaded correctly - several previous conflicts resolved by this

- **Improved:** NEW! Settings page added with several options for configuration, though fast caching should still work just fine for most users simply by activating it

- **Improved:** NEW! Optional image compression (is on by default)

- **Improved:** NEW! WP CRON scheduler to clear cache at a choice of intervals (weekly by default)

- **Improved:** NEW! Optional caching and minification of CSS and JavaScript loaded from external CDN sources

- **Improved:** NEW! Debug logging mode

= v2.2.0 =

- **Improved:** Regex for stripping CSS and Javascript comments rewritten and separated
	* The Javascript one, in particular, was treating `base64` strings containing two or more `//` as a comment and deleting them
	
- **Improved:** Fallback serving of cache files via PHP when `.htaccess` has a hiccup was sometimes pulling single post file on achive pages :/

= v2.1.1 =

- **Fixed:** Regex tweak for handling `data:image` background images in CSS

- **Fixed:** Regex tweak where some `base64` encoded strings containing two or more `/` being stripped believing they were comments

= v2.1.0 =

- **Fixed:** There is a teeny-tiny glitch in [WPBakery's Visual Composer](https://vc.wpbakery.com/) that was causing massive display errors

- **Improved:** Negated the need to deactivate and reactivate to remove v1 `.htaccess` rules

- **Improved:** HTML `<!-- fast caching comments -->` with better explanations

- **Improved:** Some additional cookie and WooCommerce checks

= v2.0.1 =

- **Minor Fix:** Code to remove v1 rules from `.htaccess` **DEACTIVATE AND REACTIVATE fast caching ASAP**

= v2.0.0 Big Improvements! =

- **Fixed:** CSS and Javascript minifying and enqueuing had issues with:
	* Some inline items were being missed out or rendered improperly
	* Absolute paths to some assets such as images and fonts loaded via `@font-face` were sometimes incorrect
	* Some items were queued twice
	* Some items were not queued at all!
	
- **Fixed:** HTML minification was sometimes removing legitimate spaces, for example between two `<a>` tags
	
- **Fixed:** Caching function was sometimes running twice, adding unecessary overhead
	
- **Improved:** Some `.htaccess` rules
- **New:** Function checking in background `.htaccess` content is OK, with automatic/silent reboot of plugin if not
- **New:** Additional explanatory HTML `<-- comments -->` added to bottom of cache pages

= v1.3.6 =

- **Fixed:** Issue with PHP `is_writable()` check on `.htaccess` was causing plugin to not activate at all or silently deactivate upon next login

= v1.3.5 =

- **Fixed:** Regex to remove inline JS comments was failing if no space after `//`
- **Imprevement:** Compatability check when activating new plugins. On failure automatically deactivates fast caching and restores `.htaccess` file

= v1.3.4 =

 - **Fixed:** `.htaccess` rewritebase error, was killing some sites dead AF, sorry

= v1.3.3 =

 - **Fixed:** `.htaccess` PHP fallback has conditions checking user not logged in
 - **Fixed:** WordFence JS conflict fixed

= v1.3.1 =

 - Disabled the debug logging script, you don't need me filling your server up!

= v1.3.0 =

 - **Fixed:** Directories and URLs management for assets
 - **Fixed:** Localized scripts with dependencies now requeued with their data AND new cache script dependency

= v1.2.0 =

- **Fixed:** Combining and minifying CSS and Javascript **MASSIVELY IMPROVED!**
	* For CSS, now deals with conditional stylesheets e.g. `<!--[if lt IE 9]>`, this was causing a few hiccups
	* For Javascript, now deals with scripts that load data via inline `<script>` tags containing `/* <![CDATA[ */`
	* Minification process now does newlines first, then whitespace. Was the other way around, which could cause issues

= v1.1.4 =

- **Fixed:** Sorry, I meant site domain !== FALSE. I'm an idiot, going to bed

= v1.1.3 =

- **Fixed:** Removed googleapis.com = FALSE check of v1.1.1 and replaced with site domain = TRUE check
- **Fixed:** Check for WooCommerce assets made more specific to WC core only, now excludes "helper" WC plugins

= v1.1.2 =

- **Fixed:** Version numbering blip

= v1.1.1 =

- **Fixed:** Encoding of `.htaccess` rules template changed from Windows to UNIX as was adding extra line breaks
- **Fixed:** Additional check to avoid googleapis.com assets being considered "local"
- **Fixed:** Removed lookup of non-existent parameter from "Flush Cache" links

= v1.1.0 =

- "Flush Cache" links and buttons added
- Five minutes applied to browser caching (was previously an hour)
- Whole HTML cache flush on Page/Post create/update/delete, so that home pages/widgets etc. are updated
- **Fixed:** PHP Workaround for hosts who struggle with basic `.htaccess` rewrites :/ #SMH
- **Fixed:** Additional checks before editing `.htaccess` as sometimes lines were being repeated, my bad
- **Fixed:** For you poor souls who are hosted with 1&1, a bizarre home directory inconsistency
- **Fixed:** Scheduled task to flush HTML cache hourly wasn't clearing properly on deactivation

= v1.0.0 =

- Initial release