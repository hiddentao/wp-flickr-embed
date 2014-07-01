<?php
global $wpFlickrEmbed;

$admin = new WpFlickrEmbed_Admin_Page();

// do form submissions
$admin->handleFormSubmissions();

$errors = $admin->errors;
$messages = $admin->messages;
?>

<div class="wrap wp-flickr-embed-admin">
    <h2><?php _e('Wordpress Flickr Embed', 'wp-flickr-embed') ?></h2>

    <?php if(!empty($errors)): ?>
    <div id="message" class="error fade"><p><strong><?php echo join('<br />', $errors) ?></strong></p></div>
    <?php endif; ?>

    <?php if(!empty($messages)): ?>
    <div id="message" class="updated fade"><p><strong><?php echo join('<br />', $messages) ?></strong></p></div>
    <?php endif; ?>

    <?php if (!$wpFlickrEmbed->isDisabled()): ?>
        <div class="metabox-holder">
            <div class="wp-flickr-embed-options postbox-container">
                <?php $admin->drawAdminForms() ?>
            </div>
            <div class="wp-flickr-embed-sidebar">
                <?php $admin->draw_sidebar() ?>
            </div>
        </div>
    <?php endif; ?>

</div>

