<?php
/*
Plugin Name: Wordpress Flickr Embed
Plugin URI: http://factage.com/yu-ji/tag/wp-flickr-embed
Description: Insert Flickr images into your post using an interactive popup, launched from the visual editor toolbar.
Author: Ramesh Nair
Version: 1.0.0
Author URI: http://hiddentao.com
*/

class WpFlickrEmbed {
    var $pluginURI = null;
    var $settings = array();
    var $default_settings = array(
        'username' => '',
        'user_id' => '',
        'photo_link' => '0',
        'link_rel' => '',
        'link_class' => '',
    );

    var $flickr_auth_url = 'http://flickr.com/services/auth/?';
    var $flickr_api_url = 'http://api.flickr.com/services/rest/?';

    var $flickr_api_key = '3f02754ebb3a1fb2d79f62200d3744e0';
    var $flickr_api_secret = '4af3a6dcd49af4a7';
    var $flickr_api_frob = null;

    function WpFlickrEmbed() {
//        load_plugin_textdomain('wp-flickr-embed', false, basename(dirname(__FILE__)) . '/languages');

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


        add_action('media_buttons', array($this, 'addMediaButton'), 20);
        add_action('media_upload_flickr', array($this, 'media_upload_flickr'));
        add_action('admin_menu', array(&$this, 'addAdminMenu'));

        // check auth enabled
        if(!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
            $this->disabled = true;
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
            add_options_page(__('Flickr Embed', 'wp-flickr-embed'), __('Flickr Embed', 'wp-flickr-embed'), 8, dirname(__FILE__)."/wp-flickr-embed-admin.php");
        }
    }

    function media_upload_flickr() {
        wp_iframe('media_upload_type_flickr');
    }

    function update_settings($settings=null){
        if($settings) {
            foreach($settings as $key => $val) {
                $this->settings[$key] = $val;
            }
        }

        // Boolean values
        // if (!empty($settings['isafter']))
        //  $this->settings['isafter'] = true;
        // else
        //  $this->settings['isafter'] = false;

        $_settings = array();
        foreach($this->settings as $key => $value) {
            if(isset($this->default_settings[$key])) {
                $_settings[$key] = $value;
            }
        }

        update_option(get_class($this), $_settings);
    }

    function flickrGetToken() {
        $params = array(
            'api_key' => $this->flickr_api_key,
            'format' => 'php_serial',
            'frob' => $this->flickrGetFrob(),
            'method' => 'flickr.auth.getToken',
            'api_sig' => $this->flickrGenerateSignature('format', 'php_serial', 'frob', $this->flickrGetFrob(), 'method', 'flickr.auth.getToken'),
        );
        $result = unserialize($this->get_contents($this->flickr_api_url.http_build_query($params)));
        if(!empty($result) && $result['stat'] == 'ok') {
            return $result;
        }else{
            return null;
        }
    }

    function flickrGetFrob() {
        if(empty($this->flickr_api_frob)) {
            $params = array(
                'api_key' => $this->flickr_api_key,
                'method' => 'flickr.auth.getFrob',
                'format' => 'php_serial',
                'api_sig' => $this->flickrGenerateSignature('format', 'php_serial', 'method', 'flickr.auth.getFrob'),
            );
            $result = unserialize($this->get_contents($this->flickr_api_url.http_build_query($params)));
            if(!empty($result) && $result['stat'] == 'ok') {
                $this->flickr_api_frob = $result['frob']['_content'];
            }
        }
        return $this->flickr_api_frob;
    }

    function flickrGetAuthUrl() {
        $params = array(
            'api_key' => $this->flickr_api_key,
            'frob' => $this->flickrGetFrob(),
            'perms' => 'read',
            'api_sig' => $this->flickrGenerateSignature('frob', $this->flickrGetFrob(), 'perms', 'read'),
        );
        return $this->flickr_auth_url.http_build_query($params);
    }

    function flickrGenerateSignature() {
        $args = func_get_args();
        $raws = array(
            $this->flickr_api_secret,
            'api_key',
            $this->flickr_api_key,
        );
        return md5(join('', $raws).join('', $args));
    }

    function get_contents($url) {
        if(function_exists('curl_init')) {
            $ch = curl_init();
            $timeout = 5; // set to zero for no timeout
            curl_setopt ($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            curl_close($ch);
            return $file_contents;
        }else{
            return file_get_contents($url);
        }
    }
}

function media_upload_type_flickr() {
    global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types, $wpFlickrEmbed;

    add_filter('media_upload_tabs', array($wpFlickrEmbed, 'modifyMediaTab'));

    media_upload_header();
    ?>
<style type="text/css">
    h3 {
        margin: 0px;
    }
    .flickr_photo {
        width: 90px;
        padding: 5px 7px;
        float: left;
        height: 110px;
    }
    .flickr_image {
        border: 0px;
        width: 75px;
        height: 75px;
        cursor: pointer;
    }
    .flickr_title {
        font-size: 80%;
        cursor: pointer;
        padding-top: 2px;
    }
    #search-filter label {
        display: inline;
        font-size: 80%;
    }
    #loader {
        display: none;
        position: absolute;
        top: 40px;
        left: 40px;
        background-color: #666;
        text-align: center;
        padding: 20px;
        vertical-align: baseline;
        color: #fff;
        font-size: 18px;
        border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        font-weight: bold;
    }
    #loader img {
        display: inline-block;
    }
    #pager {
    }
    #prev_page {
        display: none;
        font-weight: bold;
        float: left;
        padding-bottom: 10px;
    }
    #next_page {
        display: none;
        font-weight: bold;
        float: right;
        padding-bottom: 10px;
    }
    #pages {
        font-size: 70%;
        font-weight: normal;
    }
    #items {
        text-align: center;
    }
    form input {
        vertical-align: middle;
    }
    #put_dialog {
        display: none;
        position: absolute;
        border: 1px solid #888;
        background-color: #fff;
        top: 30px;
        left: 0px;
        width: 90%;
        padding: 10px;
    }
    #put_dialog div{
        padding-top: 10px;
    }
    #put_background {
        position: absolute;
        display: none;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 100%;
        background-color: #fff;
        filter:alpha(opacity=75); /*IE*/
        -moz-opacity:0.75; /*FF*/
        opacity:0.75;
    }
    #alignment_preview,
    .size_preview {
        text-align: center;
    }
    #buttons {
        text-align: center;
    }
    #alignments {
        padding-left: 40px;
    }
    .sizes {
        padding-left: 20px;
    }
    .sizes div {
        padding: 0px;
    }
    #select_size {
        float: left;
        width: 30%;
    }
    #select_alignment {
        float: left;
        width: 33%;
    }
    #select_lightbox_size {
        float: left;
        width: 30%;
    }
    #options {
        display: both;
        clear: both;
        margin-top: 10px;
        text-align: center;
    }

    select#photoset {
        width: 170px;
        vertical-align: middle;
        padding: 0px;
        display: none;
    }
    div.div_size_o_disabled {
        display: none;
        color: #888;
    }
    #search-filter  {
        text-align: left;
    }
    #search-filter .extra_filters {
        margin: 5px 0;
    }
    h3 {
        padding-top: 10px;
    }
        /* clearfix */
    .pkg:after{
        content: ".";
        display: block;
        clear: both;
        height: 0px;
        visibility:hidden;
    }
    .pkg{ display: inline-block; }
        /* no ie mac \*/
    * html .pkg{ height: 1%; }
    .pkg{ display: block; }
        /* */
</style>
<div id="loader"><img src="<?php echo $wpFlickrEmbed->pluginURI ?>/images/loading.gif" /> Loading</div>
<form method="get" class="media-upload-form type-form" onsubmit="return false">
    <input type="hidden" name="type" value="<?php echo $type ?>" />
    <input type="hidden" name="tab" value="<?php echo $tab ?>" />
    <div id="search-filter" style="text-align: left">
        <?php _e('Search:', 'wp-flickr-embed') ?>
        <input type="text" id="flickr_search_query" />
        <?php if(!empty($wpFlickrEmbed->settings['username'])): ?>
        <select id="photoset" name="photoset"></select>
        <input type="radio" id="flickr_search_0" name="flickr_search" class="searchTypes" value="own" checked="checked"/><label for="flickr_search_0"><?php _e('Your Photos', 'wp-flickr-embed') ?></label>
        <input type="radio" id="flickr_search_1" name="flickr_search" class="searchTypes" value="sets"/><label for="flickr_search_1"><?php _e('Your Sets', 'wp-flickr-embed') ?></label>
        <input type="radio" id="flickr_search_2" name="flickr_search" class="searchTypes" value="everyone"/><label for="flickr_search_2"><?php _e('Everyone\'s Photos', 'wp-flickr-embed') ?></label>
        <?php endif; ?>
        <input type="submit" onclick="wpFlickrEmbed.searchPhoto(0)" value="<?php _e('Search photo', 'wp-flickr-embed'); ?>" class="button" />
        <div class="extra_filters">
            <label for="sort_by">Sort by:</label>
            <select id="sort_by">
                <option value="date-posted-desc">Date posted (desc)</option>
                <option value="date-posted-asc">Date posted (asc)</option>
                <option value="date-taken-desc">Date taken (desc)</option>
                <option value="date-taken-asc">Date taken (asc)</option>
            </select>
        </div>
    </div>
    <h3><?php _e('Flickr photos', 'wp-flickr-embed') ?><span id="pages"></span></h3>
    <div id="pager">
        <div id="prev_page">
            <a href="javascript:void(0)" onclick="return wpFlickrEmbed.searchPhoto(-1)"><?php _e('&laquo; Prev page', 'wp-flickr-embed') ?></a>
        </div>
        <div id="next_page">
            <a href="javascript:void(0)" onclick="return wpFlickrEmbed.searchPhoto(+1)"><?php _e('Next page &raquo;', 'wp-flickr-embed') ?></a>
        </div>
        <br style="clear: both;" />
    </div>
    <div id="items" class="pkg">
    </div>
</form>
<div id="put_background"></div>
<form onsubmit="return false" id="put_dialog">
    <div id="select_size" class="selector">
        1. <?php _e('Select size of photo', 'wp-flickr-embed') ?>
        <div id="size_preview" class="size_preview"><img id="size_image" class="size_image" rel="none" src="<?php echo $wpFlickrEmbed->pluginURI ?>/images/size_thumbnail.png" alt=""/></div>
        <div class="sizes"></div>
    </div>
    <div id="select_alignment" class="selector">
        2. <?php _e('Select alignment of photo', 'wp-flickr-embed') ?>
        <div id="alignment_preview"><img id="alignment_image" rel="none" src="<?php echo $wpFlickrEmbed->pluginURI ?>/images/alignment_none.png" alt=""/></div>
        <div id="alignments">
            <input type="radio" id="alignment_none" name="alignment" value="none" /> <label for="alignment_none"><?php _e('Default', 'wp-flickr-embed') ?></label><br />
            <input type="radio" id="alignment_left" name="alignment" value="left" /> <label for="alignment_left"><?php _e('Left', 'wp-flickr-embed') ?></label><br />
            <input type="radio" id="alignment_center" name="alignment" value="center" /> <label for="alignment_center"><?php _e('Center', 'wp-flickr-embed') ?></label><br />
            <input type="radio" id="alignment_right" name="alignment" value="right" /> <label for="alignment_right"><?php _e('Right', 'wp-flickr-embed') ?></label><br />
        </div>
    </div>
    <?php if (!empty($wpFlickrEmbed->settings['photo_link'])): ?>
    <div id="select_lightbox_size" class="selector">
        3. <?php _e('Select size of lightbox photo', 'wp-flickr-embed') ?>
        <div id="lightbox_size_preview" class="size_preview"><img id="lightbox_size_image" class="size_image" rel="none" src="<?php echo $wpFlickrEmbed->pluginURI ?>/images/size_thumbnail.png" alt=""/></div>
        <div class="sizes"></div>
    </div>
    <?php endif; ?>
    <div style="clear: both" />
    <div id="options">
        <label for="photo_title"><?php _e('Photo title'); ?></label>
        <input type="text" id="photo_title" name="photo_title" value="" /><br /><br />

        <input type="checkbox" id="continue_insert" name="continue_insert" value="1" />
        <label for="continue_insert"><?php _e('Continue to insert another photo after this.', 'wp-flickr-embed') ?></label><br />
    </div>
    <div id="buttons">
        <input type="button" value="<?php _e('Cancel', 'wp-flickr-embed') ?>" onclick="wpFlickrEmbed.cancelInsertImage()" class="button"/>
        <input type="submit" value="<?php _e('Insert', 'wp-flickr-embed') ?>" onclick="wpFlickrEmbed.insertImage()" class="button"/>
    </div>
</form>
<script type="text/javascript" src="<?php echo $wpFlickrEmbed->pluginURI ?>/wp-flickr-embed.js"></script>
<script type="text/javascript">
    <!--
    var is_msie = /*@cc_on!@*/false;

    var plugin_uri = '<?php echo $wpFlickrEmbed->pluginURI ?>';
    var plugin_img_uri = plugin_uri + '/images';
    var flickr_api_url = '<?php echo $wpFlickrEmbed->flickr_api_url ?>';

    var flickr_user_id = '<?php echo !empty($wpFlickrEmbed->settings['user_id']) ? $wpFlickrEmbed->settings['user_id'] : '' ?>';
    var flickr_api_key = '<?php echo $wpFlickrEmbed->flickr_api_key ?>';
    var flickr_errors = {
        0: "<?php _e('Not found photo', 'wp-flickr-embed') ?>",
        1: "<?php _e('Too many tags in ALL query', 'wp-flickr-embed') ?>",
        2: "<?php _e('Unknown user', 'wp-flickr-embed') ?>",
        3: "<?php _e('Parameterless searches have been disabled', 'wp-flickr-embed') ?>",
        4: "<?php _e('You don\'t have permission to view this pool', 'wp-flickr-embed') ?>",
        10: "<?php _e('Sorry, the Flickr search API is not currently available.', 'wp-flickr-embed') ?>",
        11: "<?php _e('No valid machine tags', 'wp-flickr-embed') ?>",
        12: "<?php _e('Exceeded maximum allowable machine tags', 'wp-flickr-embed') ?>",
        100: "<?php _e('Invalid API Key', 'wp-flickr-embed') ?>",
        105: "<?php _e('Service currently unavailable', 'wp-flickr-embed') ?>",
        999: "<?php _e('Unknown error', 'wp-flickr-embed') ?>"
    };

    var msg_pages = '<?php _e('(%1$s / %2$s page(s), %3$s photo(s))', 'wp-flickr-embed')?>';

    var setting_photo_link = <?php echo !empty($wpFlickrEmbed->settings['photo_link']) ? 1 : 0 ?>;
    var setting_link_rel = '<?php echo !empty($wpFlickrEmbed->settings['link_rel']) ? $wpFlickrEmbed->settings['link_rel'] : '' ?>';
    var setting_link_class = '<?php echo !empty($wpFlickrEmbed->settings['link_class']) ? $wpFlickrEmbed->settings['link_class'] : '' ?>';
    //-->
</script>
<?php
}

$wpFlickrEmbed = new WpFlickrEmbed;
