<?php
/*
Plugin Name: Wordpress Flickr Embed
Plugin URI: https://github.com/hiddentao/wp-flickr-embed
Description: Insert Flickr images into your post using an interactive popup, launched from the visual editor toolbar.
Author: Ramesh Nair
Version: 1.1
Author URI: http://hiddentao.com
*/

require_once(dirname(__FILE__).'/DPZ/Flickr.php');
use \DPZ\Flickr;

require_once(dirname(__FILE__).'/include/constants.php');


class WpFlickrEmbed implements WPFlickrEmbedConstants {
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

        $this->pluginURI = get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__));
        // Avoid 'insecure content' loading errors.
        if (is_ssl())
            $this->pluginURI = str_replace('http:', 'https:', $this->pluginURI);


        add_action('init', array(&$this, 'hook_init'));
        add_action('template_redirect', array(&$this, 'hook_template_redirect'));
        add_action('media_buttons', array(&$this, 'addMediaButton'), 20);
        add_action('media_upload_flickr', array(&$this, 'media_upload_flickr'));
        add_action('admin_menu', array(&$this, 'addAdminMenu'));

        // check auth enabled
        if(!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
            $this->disabled = true;
        }

        $this->flickrAPI = new Flickr(
            self::FLICKR_API_KEY,
            self::FLICKR_SECRET
        );

        // are we authenticated with flickr? if so then set up auth params
        if (!empty($this->settings[self::FLICKR_OAUTH_TOKEN])) {
            $this->flickrAPI->useOAuthAccessCredentials(array(
                \DPZ\Flickr::USER_FULL_NAME => $this->settings[self::FLICKR_USER_FULLNAME],
                \DPZ\Flickr::USER_NAME => $this->settings[self::FLICKR_USER_NAME],
                \DPZ\Flickr::USER_NSID => $this->settings[self::FLICKR_USER_NSID],
                \DPZ\Flickr::OAUTH_ACCESS_TOKEN => $this->settings[self::FLICKR_OAUTH_TOKEN],
                \DPZ\Flickr::OAUTH_ACCESS_TOKEN_SECRET => $this->settings[self::FLICKR_OAUTH_TOKEN_SECRET],
            ));
        }

    }


    /**
     * Hook for 'init' action.
     */
    function hook_init() {
        global $wp;
        $wp->add_query_var( self::AJAX_URL_PARAM_NAME );
    }


    /**
     * Hook for 'template_redirect' action.
     */
    function hook_template_redirect() {
        $wpfe_call = get_query_var(self::AJAX_URL_PARAM_NAME);

        if (!empty($wpfe_call)) {
            // parse JSON string
            $json = @json_decode(stripslashes($wpfe_call));

            if (empty($json) || !is_object($json)) {
                die('Invalid JSON input: ' . $wpfe_call);
            } else {
                if (empty($json->method)) {
                    die('No Flickr API method specified: ' . print_r($json,true));
                }

                // make the method call
                $response = $this->flickrAPI->call($json->method, get_object_vars($json));

                header("Content-type: application/json");
                print(str_replace('\/', '/', json_encode($response)));

                exit();
            }
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
            add_options_page(__('WP Flickr Embed', 'wp-flickr-embed'), __('WP Flickr Embed', 'wp-flickr-embed'), 8, dirname(__FILE__)."/wp-flickr-embed-admin.php");
        }
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
            self::FLICKR_USER_FULLNAME => $this->flickrAPI->getOauthData(Flickr::USER_FULL_NAME),
            self::FLICKR_USER_NAME => $this->flickrAPI->getOauthData(Flickr::USER_NAME),
            self::FLICKR_USER_NSID => $this->flickrAPI->getOauthData(Flickr::USER_NSID),
            self::FLICKR_OAUTH_TOKEN => $this->flickrAPI->getOauthData(Flickr::OAUTH_ACCESS_TOKEN),
            self::FLICKR_OAUTH_TOKEN_SECRET => $this->flickrAPI->getOauthData(Flickr::OAUTH_ACCESS_TOKEN_SECRET),
        ));
    }

}

/** Get the iFRAME contents */
function media_upload_type_flickr() {
    require(dirname(__FILE__) .'/wp-flickr-embed-upload-frame.php');
}

$wpFlickrEmbed = new WpFlickrEmbed;
