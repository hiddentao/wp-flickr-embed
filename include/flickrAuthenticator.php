<?php
/** This script is purely responsible for authenticating with Flickr and then redirecting back to plugin admin page. */

require_once(dirname(__FILE__).'/../DPZ/Flickr.php');
use \DPZ\Flickr;

require_once(dirname(__FILE__).'/class.constants.php');


$optionsPageUrl = @$_GET['optionsPageUrl'];
// if we still don't have return URL then user is trying to call script directly
if (empty($optionsPageUrl)) {
    die('Please don\'t call this script directly. Authenticate with Flickr from within the WP Embed Flickr options page instead.');
}


$currentPageUrl = sprintf('%s://%s:%d%s?optionsPageUrl=%s',
    (@$_SERVER['HTTPS'] == "on") ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    $_SERVER['SERVER_PORT'],
    $_SERVER['SCRIPT_NAME'],
    urlencode($optionsPageUrl)
);

$flickrAPI = new Flickr(
    WPFlickrEmbed_Constants::FLICKR_API_KEY,
    WPFlickrEmbed_Constants::FLICKR_SECRET,
    $currentPageUrl
);


// first time we're calling this?
$oauth_verifier = @$_GET['oauth_verifier'];
if (empty( $oauth_verifier )) {
    // clear auth
    $flickrAPI->signout();
}


/*
 * The first time this script is called it's hopefully from the options page. The auth call should redirect to Flickr.
 *
 * The second time this script is called it's hopefully from Flickr after user has successfully authenticated the app.
 */
if ($flickrAPI->authenticate('read')) {
    // redirect to options page and inform that auth is complete
    @$_SESSION['wp-flickr-embed']['auth'] = 'success';

} else {
    // redirect to options page with error
    @$_SESSION['wp-flickr-embed']['auth'] = 'fail';
}

// back to options page
header(sprintf('Location: %s', $optionsPageUrl));


