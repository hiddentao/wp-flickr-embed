<?php
/*
Plugin Name: Wordpress Flickr Embed
Plugin URI: https://github.com/hiddentao/wp-flickr-embed
Description: Insert Flickr images into your post using an interactive popup, launched from the visual editor toolbar.
Author: Ramesh Nair
Version: 1.2.3
Author URI: http://hiddentao.com
*/


if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}


/**
 * Class loader.
 */
function wp_flickr_embed_class_loader($class) {
    if (0 !== stripos($class, 'WpFlickrEmbed')) {
        return;
    }
    // class name is in form:  WpFlickrEmbed_Xyz_Yyy
    $shortClassName = str_replace('_', '-', substr($class, stripos($class, '_') + 1));
    require(dirname(__FILE__).'/include/class.' . strtolower($shortClassName) . '.php');
}
spl_autoload_register('wp_flickr_embed_class_loader');




class WpFlickrEmbed implements WPFlickrEmbed_Constants {
    private $_slug = 'wp-flickr-embed';

    var $pluginURI = null;
    var $settings = array();
    var $default_settings = array(
        self::FLICKR_USER_FULLNAME => '',
        self::FLICKR_USER_NAME => '',
        self::FLICKR_USER_NSID => '',
        self::FLICKR_OAUTH_TOKEN => '',
        self::FLICKR_OAUTH_TOKEN_SECRET => '',
        self::OPTION_PHOTO_LINK => '0',
        self::OPTION_LINK_REL => '',
        self::OPTION_LINK_CLASS => '',
    );

    function WpFlickrEmbed() {
        global $wp;

        $this->includeDir =  dirname(__FILE__). '/include';
        $this->pagesDir = $this->includeDir . '/pages';

        $this->settings = get_option(get_class($this));

        $flush_settings = false;
        foreach($this->default_settings as $key => $value) {
            if(!isset($this->settings[$key])) {
                $this->settings[$key] = $value;
                $flush_settings = true;
            }
        }
        if($flush_settings) {
            $this->update_settings();
        }

        $this->pluginURI = plugin_dir_url(__FILE__);
        // Avoid 'insecure content' loading errors.
        if (is_ssl())
            $this->pluginURI = str_replace('http:', 'https:', $this->pluginURI);


        add_action('init', array(&$this, 'hook_init'));
        add_action('template_redirect', array(&$this, 'hook_template_redirect'));
        add_action('media_buttons', array(&$this, 'addMediaButton'), 20);
        add_action('media_upload_flickr', array(&$this, 'media_upload_flickr'));
        add_action('admin_menu', array(&$this, 'addAdminMenu'));
        add_action('admin_print_scripts', array(&$this, 'adminPrintScripts'));
        add_action('admin_print_styles', array(&$this, 'adminPrintStyles'));

        // check that we can do use this plugin
        if(!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
            $this->_disabled = self::DISABLED_REASON_CURL_FOPEN;
        } else if (PHP_VERSION_ID < 50200) {
            $this->_disabled = self::DISABLED_REASON_PHP_VERSION;
        }

        if ($this->isViewingOurAdminPage()) {
            $this->_currentURL = admin_url(sprintf('options-general.php?%s', $_SERVER['QUERY_STRING']));
        } else {
            $this->_currentURL = null;
        }

        $this->flickrAPI = new WpFlickrEmbed_Flickr(
            self::FLICKR_API_KEY,
            self::FLICKR_SECRET,
            $this->_currentURL
        );

        // are we authenticated with flickr? if so then set up auth params
        if (!empty($this->settings[self::FLICKR_OAUTH_TOKEN])) {
            $this->flickrAPI->useOAuthAccessCredentials(array(
                WpFlickrEmbed_Flickr::USER_FULL_NAME => $this->settings[self::FLICKR_USER_FULLNAME],
                WpFlickrEmbed_Flickr::USER_NAME => $this->settings[self::FLICKR_USER_NAME],
                WpFlickrEmbed_Flickr::USER_NSID => $this->settings[self::FLICKR_USER_NSID],
                WpFlickrEmbed_Flickr::OAUTH_ACCESS_TOKEN => $this->settings[self::FLICKR_OAUTH_TOKEN],
                WpFlickrEmbed_Flickr::OAUTH_ACCESS_TOKEN_SECRET => $this->settings[self::FLICKR_OAUTH_TOKEN_SECRET],
            ));
        }

    }


    /**
     * Get whether this plugin is disabled.
     *
     * @return bool FALSE if not; otherwise the reason for being disabled.
     */
    function isDisabled() {
        return $this->_disabled ? $this->_disabled : FALSE;
    }



    /**
     * Hook for 'init' action.
     */
    function hook_init() {
        global $wp;
        $wp->add_query_var( self::SIGN_URL_PARAM_NAME );
        $wp->add_query_var( self::FLICKR_AUTH_URL_PARAM_NAME );
    }


    /**
     * Hook for 'template_redirect' action.
     */
    function hook_template_redirect() {
        // sign a flickr request
        $wpfe_sign = get_query_var(self::SIGN_URL_PARAM_NAME);
        if (!empty($wpfe_sign)) {
            // parse JSON string
            $json = @json_decode(stripslashes($wpfe_sign));

            if (empty($json) || !is_object($json)) {
                die('Invalid JSON input: ' . $wpfe_sign);
            } else {
                if (empty($json->method)) {
                    die('No Flickr API method specified: ' . print_r($json,true));
                }

                // api key
                $json->api_key = self::FLICKR_API_KEY;

                // get signed call
                $ret = $this->flickrAPI->getSignedUrlParams($json->method, get_object_vars($json), array(
                    'use_secure_api' => TRUE
                ));

                header("Content-type: application/json");
                print json_encode($ret);
                exit();
            }
        }

        // flickr authorization process
        $wpfe_flickr_auth = get_query_var(self::FLICKR_AUTH_URL_PARAM_NAME);
        if (!empty($wpfe_flickr_auth)) {
            // first time call?
            $oauth_verifier = $_GET['oauth_verifier'];
            if (empty($oauth_verifier)) {
                // want flickr to come back here
                $current_url = sprintf('%s?%s=%s',
                    trailingslashit($this->getSiteHomeUrl()), self::FLICKR_AUTH_URL_PARAM_NAME, urlencode($wpfe_flickr_auth));

                $this->flickrAPI = new WpFlickrEmbed_Flickr(
                    self::FLICKR_API_KEY,
                    self::FLICKR_SECRET,
                    $current_url
                );

                // sign out and re-auth
                $this->flickrAPI->signOut();
                if (!$this->flickrAPI->authenticate('read')) {
                    header('Location: ' . $wpfe_flickr_auth . '&auth_result=fail');
                }
            } else {
                // complete auth
                if ($this->flickrAPI->authenticate('read')) {
                    $this->saveFlickrAuthentication();
                    header('Location: ' . $wpfe_flickr_auth . '&auth_result=success');
                } else {
                    header('Location: ' . $wpfe_flickr_auth . '&auth_result=fail');
                }
            }

            exit();
        }
    }


    function addMediaButton() {
        global $post_ID, $temp_ID;
        $uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
        $media_upload_iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";

        $wp_flickr_embed_iframe_src = apply_filters('wp_flickr_embed_iframe_src', "$media_upload_iframe_src&amp;type=flickr&amp;tab=flickr");
        $wp_flickr_embed_title = __('Add Flickr photo', 'wp-flickr-embed');

        echo "<a href=\"{$wp_flickr_embed_iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640\" class=\"thickbox\" title=\"$wp_flickr_embed_title\"><img src=\"{$this->pluginURI}/images/media-flickr.png\" alt=\"$wp_flickr_embed_title\" /></a>";
    }

    function modifyMediaTab($tabs) {
        return array(
            'flickr' =>  __('Flickr photo', 'wp-flickr-embed')
        );
    }

    function addAdminMenu() {
        if (function_exists('add_options_page')) {
            add_options_page(__('WP Flickr Embed', 'wp-flickr-embed'), __('WP Flickr Embed', 'wp-flickr-embed'), 8, $this->pagesDir.'/admin.php');
        }
    }

    /**
     * Handler for 'admin_print_scripts' hook.
     */
    function adminPrintScripts() {
        if ($this->isViewingOurAdminPage()) {
            wp_enqueue_script('postbox');
            wp_enqueue_script('dashboard');
        }
    }



    public function getSignRequestApiUrl() {
        return sprintf('%s?%s=', trailingslashit($this->getSiteHomeUrl()), self::SIGN_URL_PARAM_NAME);
    }


    /**
     * Get website home URL.
     *
     * @return string
     */
    public function getSiteHomeUrl() {
        // if we're in SSL then request SSL version
        return home_url('', is_ssl() ? 'https' : 'http');
    }


    /**
     * Handler for 'admin_print_styles' hook.
     */
    public function adminPrintStyles() {
        if ($this->isViewingOurAdminPage()) {
            wp_enqueue_style('dashboard');
            wp_enqueue_style('wp-flickr-embed-admin', $this->pluginURI . '/wp-flickr-embed-admin.css');
        }
    }


    /**
     * Get whether user is viewing our admin page.
     */
    private function isViewingOurAdminPage() {
        return (isset($_GET['page']) && 0 === stripos($_GET['page'], $this->_slug));
    }


    function media_upload_flickr() {
        wp_iframe('media_upload_type_flickr');
    }

    /**
     * Update settings.
     * @param null $settings
     */
    function update_settings($settings=null){
        if($settings) {
            foreach($settings as $key => $val) {
                $this->settings[$key] = $val;
            }
        }

        $_settings = array();
        foreach($this->settings as $key => $value) {
            if(isset($this->default_settings[$key])) {
                $_settings[$key] = $value;
            }
        }

        update_option(get_class($this), $_settings);
    }


    /**
     * Clear Flickr authentication credentials.
     */
    function clearFlickrAuthentication() {
        $this->flickrAPI->signout();
        $this->update_settings(array(
            self::FLICKR_USER_FULLNAME => '',
            self::FLICKR_USER_NAME => '',
            self::FLICKR_USER_NSID => '',
            self::FLICKR_OAUTH_TOKEN => '',
            self::FLICKR_OAUTH_TOKEN_SECRET => '',
        ));
    }

    /**
     * Save Flickr authentication data into settings.
     */
    function saveFlickrAuthentication() {
        $this->update_settings(array(
            self::FLICKR_USER_FULLNAME => $this->flickrAPI->getOauthData(WpFlickrEmbed_Flickr::USER_FULL_NAME),
            self::FLICKR_USER_NAME => $this->flickrAPI->getOauthData(WpFlickrEmbed_Flickr::USER_NAME),
            self::FLICKR_USER_NSID => $this->flickrAPI->getOauthData(WpFlickrEmbed_Flickr::USER_NSID),
            self::FLICKR_OAUTH_TOKEN => $this->flickrAPI->getOauthData(WpFlickrEmbed_Flickr::OAUTH_ACCESS_TOKEN),
            self::FLICKR_OAUTH_TOKEN_SECRET => $this->flickrAPI->getOauthData(WpFlickrEmbed_Flickr::OAUTH_ACCESS_TOKEN_SECRET),
        ));
    }


    /**
     * Get whether user has authenticated with Flickr.
     *
     * @return bool true if so; false otherwise.
     */
    function authenticatedWithFlickr() {
        return !empty($this->settings[self::FLICKR_OAUTH_TOKEN]);
    }
}

/** Get the iFRAME contents */
function media_upload_type_flickr() {
    global $wpFlickrEmbed;

    if ($wpFlickrEmbed->isDisabled()) {
        echo '<p>' . __('The Wordpress Flickr Embed plugin has been disabled. Please visit the plugin\'s options page.', 'wp-flickr-embed') . '</p>';
    } else {
        require(dirname(__FILE__) .'/wp-flickr-embed-upload-frame.php');
    }
}

$wpFlickrEmbed = new WpFlickrEmbed;
