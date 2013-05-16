<?php
global $wpFlickrEmbed;

// did we just try and authenticate with Flickr? (see includes/flickrAuthenticator.php)
if (!empty($_SESSION['wp-flickr-embed']) && !empty($_SESSION['wp-flickr-embed']['auth'])) {
    $result = $_SESSION['wp-flickr-embed']['auth'];
    unset($_SESSION['wp-flickr-embed']['auth']);

    if ('success' === $result) {
        $wpFlickrEmbed->saveFlickrAuthentication();
        $messages[] = __('Flickr authorization successful', 'wp-flickr-embed');
    } else {
        $errors[] = __('Oops, something went wrong whilst trying to authenticate with Flickr', 'wp-flickr-embed');
    }
}
else if ('clear_flickr' === $_POST['action']){
    $wpFlickrEmbed->clearFlickrAuthentication();
}
else if ('update' === $_POST['action']){
        unset($_POST['action']);
        $wpFlickrEmbed->update_settings($_POST);
}

$settings = $wpFlickrEmbed->settings;
?>

<?php if (!empty($wpFlickrEmbed->disabled)): ?>
    <div id="message" class="error fade"><p><strong><?php _e('Your version of PHP does not support curl, and allow_url_fopen is disabled.<br />Thus is preventing Flickr authentication.', 'wp-flickr-embed') ?></strong></p></div>
<?php endif; ?>

<?php if(!empty($errors)): ?>
    <div id="message" class="error fade"><p><strong><?php echo join('<br />', $errors) ?></strong></p></div>
<?php elseif(!empty($messages)): ?>
    <div id="message" class="updated fade"><p><strong><?php echo join('<br />', $messages) ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<h2><?php _e('Wordpress Flickr Embed', 'wp-flickr-embed') ?></h2>

<h3><?php _e('Flickr authorization', 'wp-flickr-embed') ?></h3>

<?php if(!empty($settings[$wpFlickrEmbed::FLICKR_USER_NSID])): ?>
    <form name="wpFlickrEmbed" method="post" onsubmit="return confirm('<?php _e('Are you sure you want to clear Flickr authentication?', 'wp-flickr-embed') ?>')">
        <input type="hidden" name="action" value="clear_flickr" />
        <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
            <tr>
                <th width="33%" valign="top" scope="row"><?php _e('Flickr Username', 'wp-flickr-embed') ?>: </th>
                <td><?php echo htmlspecialchars($settings[$wpFlickrEmbed::FLICKR_USER_NAME]); ?></td>
            </tr>
            <tr>
                <th width="33%" valign="top" scope="row"><?php _e('Flickr User ID', 'wp-flickr-embed') ?>: </th>
                <td><?php echo htmlspecialchars($settings[$wpFlickrEmbed::FLICKR_USER_NSID]); ?></td>
            </tr>
        </table>
        <p class="submit"><input type="submit" value="<?php _e('Clear user information &raquo;', 'wp-flickr-embed'); ?>" /></p>
    </form>
<?php else: ?>
    <p><?php _e('Please authorize Wordpress Flickr Embed to access your Flickr account.', 'wp-flickr-embed') ?></p>
    <form name="wpFlickrEmbed" method="post" action="<?php echo $wpFlickrEmbed->pluginURI ?>/include/flickrAuthenticator.php">
        <input type="hidden" name="action" value="auth_flickr" />
        <input type="hidden" name="optionsPageUrl" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
        <input type="submit" value="<?php _e('Authorize with Flickr', 'wp-flickr-embed') ?>" />
    </form>
<?php endif; ?>

<h3><?php _e('Options', 'wp-flickr-embed') ?></h3>

<form name="wpFlickrEmbed" method="post"">
<input type="hidden" name="action" value="update" />
<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Link the photo to', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="radio" id="link_flickr" name="<?php echo $wpFlickrEmbed::OPTION_PHOTO_LINK ?>" value="0" <?php if(empty($settings[$wpFlickrEmbed::OPTION_PHOTO_LINK])){ ?>checked="checked" <?php } ?>/> <label for="link_flickr"><?php _e('The photo page of Flickr', 'wp-flickr-embed'); ?></label><br />
            <input type="radio" id="link_photo" name="<?php echo $wpFlickrEmbed::OPTION_PHOTO_LINK ?>" value="1" <?php if(!empty($settings[$wpFlickrEmbed::OPTION_PHOTO_LINK])){ ?>checked="checked" <?php } ?>/> <label for="link_photo"><?php _e('The photo directly', 'wp-flickr-embed'); ?></label><br />
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('The "rel" attribute of link tag', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="text" name="<?php echo $wpFlickrEmbed::OPTION_LINK_REL ?>" value="<?php echo htmlspecialchars($settings[$wpFlickrEmbed::OPTION_LINK_REL]); ?>" /><br />
            <small><?php _e("(if you want to use the Lightbox, set \"lightbox\")", 'wp-flickr-embed'); ?></small>
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('The "class" attribute of link tag', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="text" name="<?php echo $wpFlickrEmbed::OPTION_LINK_CLASS ?>" value="<?php echo htmlspecialchars($settings[$wpFlickrEmbed::OPTION_LINK_CLASS]); ?>" /><br />
            <small><?php _e("(if you want to use the Lightview, set \"lightview\")", 'wp-flickr-embed'); ?></small>
        </td>
    </tr>
</table>
<p class="submit"><input type="submit" value="<?php _e('Update options &raquo;', 'wp-flickr-embed'); ?>" /></p>
</form>
</div>
