<?php

if (isset($_POST['action']) && $_POST['action'] == 'auth'){
    if(!empty($_POST['frob'])) {
        $wpFlickrEmbed->flickr_api_frob = $_POST['frob'];
    }
    $result = $wpFlickrEmbed->flickrGetToken();
    if(!empty($result) && $result['stat'] == 'ok') {
        $wpFlickrEmbed->update_settings(array(
            'username' => $result['auth']['user']['username'],
            'user_id' => $result['auth']['user']['nsid'],
        ));
    }else{
        $wpFlickrEmbed->flickr_api_frob = null;
        $errors[] = __('Cannot get Flickr user informations.<br />Please authorize "Wordpress Flickr Embed" on flickr.', 'wp-flickr-embed');
    }
}
if (isset($_POST['action']) && $_POST['action'] == 'update'){
        unset($_POST['action']);
        $wpFlickrEmbed->update_settings($_POST);
}
if (isset($_POST['action']) && $_POST['action'] == 'clear'){
    $wpFlickrEmbed->update_settings(array(
        'username' => '',
        'user_id' => '',
    ));
}

$settings = $wpFlickrEmbed->settings;
?>

<?php if(!empty($errors)): ?>
<div id="message" class="error fade"><p><strong><?php echo join('<br/>', $errors) ?></strong></p></div>
<?php elseif(!empty($_POST) && $_POST['action'] == 'update'): ?>
<div id="message" class="updated fade"><p><strong><?php _e('Flickr authorization complete.', 'wp-flickr-embed') ?></strong></p></div>
<?php elseif(!empty($_POST) && $_POST['action'] == 'clear'): ?>
<div id="message" class="updated fade"><p><strong><?php _e('Flickr user information cleared.', 'wp-flickr-embed') ?></strong></p></div>
<?php elseif(!empty($wpFlickrEmbed->disabled)): ?>
<div id="message" class="updated fade"><p><strong><?php _e('Your version of PHP does not support curl, and allow_url_fopen is disabled.<br />Thus is preventing Flickr authentication.', 'wp-flickr-embed') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<h2><?php _e('Media Flickr', 'wp-flickr-embed') ?></h2>

<h3><?php _e('User informations', 'wp-flickr-embed') ?></h3>

<?php if(!empty($wpFlickrEmbed->settings['username'])): ?>
<form name="wpFlickrEmbed" method="post" onsubmit="return confirm('<?php _e('Are you sure you want to clear Flickr authentication?', 'wp-flickr-embed') ?>')">
<input type="hidden" name="action" value="clear" />
<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Flickr Username', 'wp-flickr-embed') ?>: </th>
        <td>
            <?php echo htmlspecialchars($settings['username']); ?>
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Flickr User ID', 'wp-flickr-embed') ?>: </th>
        <td>
            <?php echo htmlspecialchars($settings['user_id']); ?>
        </td>
    </tr>
</table>
<p class="submit"><input type="submit" value="<?php _e('Clear user information &raquo;', 'wp-flickr-embed'); ?>" /></p>
</form>
<?php else: ?>
<p>
<?php _e('Please authorize according to the following instructions.', 'wp-flickr-embed') ?>
</p>
<form name="wpFlickrEmbed" method="post" >
<input type="hidden" name="action" value="auth" />
<input type="hidden" name="frob" value="<?php echo $wpFlickrEmbed->flickrGetFrob() ?>" />
<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Step1', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="button" value="<?php _e('Flickr authenticate', 'wp-flickr-embed') ?>" onclick="window.open('<?php echo $wpFlickrEmbed->flickrGetAuthUrl() ?>')" />
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Step2', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="submit" value="<?php _e('Finish authenticate', 'wp-flickr-embed') ?>" />
        </td>
    </tr>
</table>
</form>
<?php endif; ?>

<h3><?php _e('Media Flickr Options', 'wp-flickr-embed') ?></h3>

<form name="wpFlickrEmbed" method="post" >
<input type="hidden" name="action" value="update" />
<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('Link the photo to', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="radio" id="link_flickr" name="photo_link" value="0" <?php if(empty($settings['photo_link'])){ ?>checked="checked" <?php } ?>/> <label for="link_flickr"><?php _e('The photo page of Flickr', 'wp-flickr-embed'); ?></label><br />
            <input type="radio" id="link_photo" name="photo_link" value="1" <?php if(!empty($settings['photo_link'])){ ?>checked="checked" <?php } ?>/> <label for="link_photo"><?php _e('The photo directly', 'wp-flickr-embed'); ?></label><br />
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('The "rel" attribute of link tag', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="text" name="link_rel" value="<?php echo htmlspecialchars($settings['link_rel']); ?>" /><br />
            <small><?php _e("(if you want to use the Lightbox, set \"lightbox\")", 'wp-flickr-embed'); ?></small>
        </td>
    </tr>
    <tr>
        <th width="33%" valign="top" scope="row"><?php _e('The "class" attribute of link tag', 'wp-flickr-embed') ?>: </th>
        <td>
            <input type="text" name="link_class" value="<?php echo htmlspecialchars($settings['link_class']); ?>" /><br />
            <small><?php _e("(if you want to use the Lightview, set \"lightview\")", 'wp-flickr-embed'); ?></small>
        </td>
    </tr>
</table>
<p class="submit"><input type="submit" value="<?php _e('Update options &raquo;', 'wp-flickr-embed'); ?>" /></p>
</form>
</div>
