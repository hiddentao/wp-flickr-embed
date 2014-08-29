<?php


/**
 * Companion class to admin page.
 */
class WpFlickrEmbed_Admin_Page implements WPFlickrEmbed_Constants {
    private $_errors = array();
    private $_messages  = array();
    private $_slug = 'wp-flickr-embed';



    public function __construct() {
        global $wpFlickrEmbed;

        if ($wpFlickrEmbed->isDisabled()) {

            $this->_errors[] = __('Sorry, this plugin cannot currently be used.', $this->_slug);

            switch ($wpFlickrEmbed->isDisabled()) {
                case self::DISABLED_REASON_CURL_FOPEN:
                    $this->_errors[] = __('Your version of PHP does not support curl, and allow_url_fopen is disabled.<br />Thus is preventing Flickr authentication.', $this->_slug);
                    break;
                case self::DISABLED_REASON_PHP_VERSION:
                    $this->_errors[] = __('This plugin requires PHP 5.2 or above.', $this->_slug);
                    break;
            }
        }

        // flickr auth result
        $authResult = @$_GET['auth_result'];
        if ('success' === $authResult) {
            $this->_messages[] = __('Flickr authorization successful.', $this->_slug);
        } else if ('fail' === $authResult) {
            $this->_errors[] = __('Oops, something went wrong whilst trying to authorize access to Flickr.', $this->_slug);
        }
    }


    /**
     * Handle form submissions.
     */
    public function handleFormSubmissions() {
        global $wpFlickrEmbed;

        if ('clear_flickr' === $_POST['action']){
            $wpFlickrEmbed->clearFlickrAuthentication();
            $this->_messages[] = __('Flickr authorization cleared', $this->_slug);
        }
        else if ('update' === $_POST['action']){
            $wpFlickrEmbed->update_settings($_POST);
            $this->_messages[] = __('Options updated', $this->_slug);
        }

        unset($_POST['action']);
    }


    /**
     * Show options form.
     */
    public function drawAdminForms() {
        add_meta_box( $this->_slug . '-flickr-auth', __( 'Flickr authorization', $this->_slug ), array( &$this, 'drawFlickrAuthMetaBox' ), $this->_slug, 'normal');
        add_meta_box( $this->_slug . '-options', __( 'Options', $this->_slug ), array( &$this, 'drawOptionsMetaBox' ), $this->_slug, 'normal');
        do_meta_boxes($this->_slug, 'normal', '');
    }


    /**
     * Callback for rendering flickr auth meta box.
     */
    public function drawFlickrAuthMetaBox() {
        global $wpFlickrEmbed;
        ?>
        <?php if($wpFlickrEmbed->authenticatedWithFlickr()): ?>
            <form action="" method="post" onsubmit="return confirm('<?php _e('Are you sure you want to remove Flickr authorization?', $this->_slug) ?>')">
                <input type="hidden" name="action" value="clear_flickr" />
                <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                    <tr>
                        <th width="33%" valign="top" scope="row"><?php _e('Flickr Username', $this->_slug) ?>: </th>
                        <td><?php echo htmlspecialchars($wpFlickrEmbed->settings[self::FLICKR_USER_NAME]); ?></td>
                    </tr>
                    <tr>
                        <th width="33%" valign="top" scope="row"><?php _e('Flickr User ID', $this->_slug) ?>: </th>
                        <td><?php echo htmlspecialchars($wpFlickrEmbed->settings[self::FLICKR_USER_NSID]); ?></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" value="<?php _e('Remove authorization &raquo;', $this->_slug); ?>" /></p>
            </form>
        <?php else: ?>
            <p><?php _e('Please authorize access to your Flickr account if you want to be able to insert your private photos.', $this->_slug) ?></p>
            <form name="wpFlickrEmbed" method="get" action="<?php echo $wpFlickrEmbed->getSiteHomeUrl() ?>">
                <input type="hidden"
                       name="<?php echo self::FLICKR_AUTH_URL_PARAM_NAME ?>"
                       value="<?php echo admin_url('options-general.php?' .$_SERVER['QUERY_STRING']) ?>"
                    />
                <input type="submit" value="<?php _e('Authorize with Flickr', $this->_slug) ?>" />
            </form>
        <?php endif; ?>
    <?php
    }


    /**
     * Callback for rendering options meta box.
     */
    public function drawOptionsMetaBox() {
        global $wpFlickrEmbed;

        ?>
        <form action="<?php $_SERVER['REQUEST_URI'] ?>" method="post">
            <input type="hidden" name="action" value="update" />
            <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
                <tr>
                    <th width="33%" valign="top" scope="row"><?php _e('Link the photo to', $this->_slug) ?>: </th>
                    <td>
                        <input type="radio" id="link_flickr" name="<?php echo self::OPTION_PHOTO_LINK ?>" value="0" <?php if (0 == $wpFlickrEmbed->settings[self::OPTION_PHOTO_LINK]) { ?>checked="checked" <?php } ?>/> <label for="link_flickr"><?php _e('The photo page of Flickr', $this->_slug); ?></label><br />
                        <input type="radio" id="link_photo" name="<?php echo self::OPTION_PHOTO_LINK ?>" value="1" <?php if (1 == $wpFlickrEmbed->settings[self::OPTION_PHOTO_LINK]) { ?>checked="checked" <?php } ?>/> <label for="link_photo"><?php _e('The photo directly', $this->_slug); ?></label><br />
                        <input type="radio" id="link_none" name="<?php echo self::OPTION_PHOTO_LINK ?>" value="2" <?php if (2 == $wpFlickrEmbed->settings[self::OPTION_PHOTO_LINK]) { ?>checked="checked" <?php } ?>/> <label for="link_none"><?php _e('Nothing (note that this may conflict with Flickr <a href="https://www.flickr.com/help/guidelines">guidelines</a>)', $this->_slug); ?></label><br />
                    </td>
                </tr>
                <tr>
                    <th width="33%" valign="top" scope="row"><?php _e('The "rel" attribute of link tag', $this->_slug) ?>: </th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_LINK_REL ?>" value="<?php echo htmlspecialchars($wpFlickrEmbed->settings[self::OPTION_LINK_REL]); ?>" /><br />
                        <small><?php _e("(if you want to use the Lightbox, set \"lightbox\")", $this->_slug); ?></small>
                    </td>
                </tr>
                <tr>
                    <th width="33%" valign="top" scope="row"><?php _e('The "class" attribute of link tag', $this->_slug) ?>: </th>
                    <td>
                        <input type="text" name="<?php echo self::OPTION_LINK_CLASS ?>" value="<?php echo htmlspecialchars($wpFlickrEmbed->settings[self::OPTION_LINK_CLASS]); ?>" /><br />
                        <small><?php _e("(if you want to use the Lightview, set \"lightview\")", $this->_slug); ?></small>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" value="<?php _e('Update options &raquo;', $this->_slug); ?>" /></p>
        </form>
        <?php
    }


    /**
     * Show the sidebar.
     */
    public function draw_sidebar() {
        $this->drawWidebarWidget('wp-flickr-embed-support', 'Need help?', array(&$this, 'drawSidebarSupportWidget'));
        $this->drawWidebarWidget('wp-flickr-embed-spread-word', 'Spread the word!', array(&$this, 'drawSidebarSpreadWordWidget'));
    }


    /**
     * Draw a sidebar widget.
     *
     * @param string $id      ID of the widget.
     * @param string $title   Title of the widget.
     * @param string $contentFn Callback for content.
     */
    private function drawWidebarWidget( $id, $title, $contentFn ) {
        ?>
            <div id="<?php echo $id; ?>" class="wp-flickr-embed-widget">
                <h2><?php echo $title; ?></h2>
                <?php call_user_func($contentFn) ?>
            </div>
        <?php
    }


    /**
     * Draw the 'support' sidebar widget.
     */
    private function drawSidebarSupportWidget() {
        ?>
            <p>If you are having problems with this plugin, please ask a question in the <a href="http://l.hiddentao.com/wp-flickr-embed-support">support forums</a>.</p>
        <?php
    }


    /**
     * Draw the 'spread the word' sidebar widget.
     */
    private function drawSidebarSpreadWordWidget() {
        ?>
            <p>Want to help make this plugin even better? All donations are used to improve this plugin, so please donate $5, $10 or $20!</p>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="SZZWUQ5D4ZFZE">
                <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online.">
                <img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
            </form>
            <p>Or you could:</p>
            <ul>
                <li><a href="http://l.hiddentao.com/wp-flickr-embed-rate-it">Rate the plugin 5★ on WordPress.org</a></li>
                <li><a href="http://l.hiddentao.com/wp-flickr-embed-blog-about-it">Blog about it &amp; link to the plugin page</a></li>
        <?php
    }




    /**
     * Get property.
     */
    public function __get($property) {
        $propertyFuncName = 'get'.ucfirst((string)$property);

        if( ! is_callable( array($this, $propertyFuncName) ) )
            throw new Exception('Bad property: ' . $property);

        return call_user_func( array($this, $propertyFuncName) );
    }


    public function getErrors() { return $this->_errors; }
    public function getMessages() { return $this->_messages; }
}
