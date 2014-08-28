<?php
require_once(dirname(__FILE__).'/include/class.constants.php');

global $type, $tab, $wpFlickrEmbed;

add_filter('media_upload_tabs', array($wpFlickrEmbed, 'modifyMediaTab'));

media_upload_header();
?>
<link type="text/css" rel="stylesheet" href="<?php echo $wpFlickrEmbed->pluginURI ?>/wp-flickr-embed-upload-frame.css" />
<div id="loader">Loading</div>
<form method="get" class="media-upload-form type-form" onsubmit="return false">
    <div id="ajax_error_msg"></div>
    <input type="hidden" name="type" value="<?php echo $type ?>" />
    <input type="hidden" name="tab" value="<?php echo $tab ?>" />
    <div id="search-filter" style="text-align: left">
        <?php _e('Search:', 'wp-flickr-embed') ?>
        <input type="text" id="flickr_search_query" />
        <?php if(!empty($wpFlickrEmbed->settings[WPFlickrEmbed_Constants::FLICKR_USER_NSID])): ?>
            <select id="photoset" name="photoset"></select>
            <input type="radio" id="flickr_search_0" name="flickr_search" class="searchTypes" value="own" checked="checked"/><label for="flickr_search_0"><?php _e('Your Photos', 'wp-flickr-embed') ?></label>
            <input type="radio" id="flickr_search_1" name="flickr_search" class="searchTypes" value="sets"/><label for="flickr_search_1"><?php _e('Your Sets', 'wp-flickr-embed') ?></label>
            <input type="radio" id="flickr_search_2" name="flickr_search" class="searchTypes" value="everyone"/><label for="flickr_search_2"><?php _e('Everyone\'s Photos', 'wp-flickr-embed') ?></label>
        <?php endif; ?>
        <input type="submit" onclick="wpFlickrEmbed.searchPhoto(0)" value="<?php _e('Go', 'wp-flickr-embed'); ?>" class="button" />
    </div>
    <h3><?php _e('Flickr photos', 'wp-flickr-embed') ?><span id="pages"></span></h3>
    <div class="extra_filters">
        <label for="sort_by">Sort by:</label>
        <select id="sort_by">
            <option value="date-posted-desc">Date posted (desc)</option>
            <option value="date-posted-asc">Date posted (asc)</option>
            <option value="date-taken-desc">Date taken (desc)</option>
            <option value="date-taken-asc">Date taken (asc)</option>
        </select>
    </div>
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
            <div class="alignment">
                <input type="radio" id="alignment_none" name="alignment" value="none" /> <label for="alignment_none"><?php _e('Default', 'wp-flickr-embed') ?></label>
            </div>
            <div class="alignment">
                <input type="radio" id="alignment_left" name="alignment" value="left" /> <label for="alignment_left"><?php _e('Left', 'wp-flickr-embed') ?></label>
            </div>
            <div class="alignment">
                <input type="radio" id="alignment_center" name="alignment" value="center" /> <label for="alignment_center"><?php _e('Center', 'wp-flickr-embed') ?></label>
            </div>
            <div class="alignment">
                <input type="radio" id="alignment_right" name="alignment" value="right" /> <label for="alignment_right"><?php _e('Right', 'wp-flickr-embed') ?></label>
            </div>
        </div>
    </div>
    <?php if (1 == $wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_PHOTO_LINK]): ?>
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
<script type="text/javascript">
    <!--
    var is_msie = /*@cc_on!@*/false;

    var plugin_uri = '<?php echo $wpFlickrEmbed->pluginURI ?>';
    var plugin_img_uri = plugin_uri + '/images';

    var flickr_user_id = '<?php echo $wpFlickrEmbed->settings[WPFlickrEmbed_Constants::FLICKR_USER_NSID] ?>';
    var sign_request_url = '<?php echo $wpFlickrEmbed->getSignRequestApiUrl() ?>';

    var flickr_errors = {
        0: "<?php _e('No photos found', 'wp-flickr-embed') ?>",
        1: "<?php _e('Too many tags in ALL query', 'wp-flickr-embed') ?>",
        2: "<?php _e('Unknown user', 'wp-flickr-embed') ?>",
        3: "<?php _e('Parameter-less searches have been disabled', 'wp-flickr-embed') ?>",
        4: "<?php _e('You don\'t have permission to view this pool', 'wp-flickr-embed') ?>",
        10: "<?php _e('Sorry, the Flickr search API is not currently available.', 'wp-flickr-embed') ?>",
        11: "<?php _e('No valid machine tags', 'wp-flickr-embed') ?>",
        12: "<?php _e('Exceeded maximum allowable machine tags', 'wp-flickr-embed') ?>",
        100: "<?php _e('Invalid API Key', 'wp-flickr-embed') ?>",
        105: "<?php _e('Service currently unavailable', 'wp-flickr-embed') ?>",
        999: "<?php _e('Unknown error', 'wp-flickr-embed') ?>"
    };

    var msg_pages = '<?php _e('(%1$s / %2$s page(s), %3$s photo(s))', 'wp-flickr-embed')?>';

    var setting_photo_link = <?php echo !empty($wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_PHOTO_LINK]) ? $wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_PHOTO_LINK] : 0 ?>;
    var setting_link_rel = '<?php echo !empty($wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_LINK_REL]) ? $wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_LINK_REL] : '' ?>';
    var setting_link_class = '<?php echo !empty($wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_LINK_CLASS]) ? $wpFlickrEmbed->settings[WPFlickrEmbed_Constants::OPTION_LINK_CLASS] : '' ?>';
    //-->
</script>
<script type="text/javascript" src="<?php echo $wpFlickrEmbed->pluginURI ?>/json2.js"></script>
<script type="text/javascript" src="<?php echo $wpFlickrEmbed->pluginURI ?>/wp-flickr-embed.js"></script>

