<?php

interface WPFlickrEmbed_Constants {
    const FLICKR_API_KEY = '3f02754ebb3a1fb2d79f62200d3744e0';
    const FLICKR_SECRET = '4af3a6dcd49af4a7';

    const FLICKR_USER_FULLNAME = 'flickr_user_fullname';
    const FLICKR_USER_NAME = 'flickr_username';
    const FLICKR_USER_NSID = 'flickr_user_id';
    const FLICKR_OAUTH_TOKEN = 'flickr_oauth_token';
    const FLICKR_OAUTH_TOKEN_SECRET = 'flickr_oauth_token_secret';

    const OPTION_PHOTO_LINK = 'photo_link';
    const OPTION_LINK_REL = 'link_rel';
    const OPTION_LINK_CLASS = 'link_class';

    const SIGN_URL_PARAM_NAME = '__wpfe_sign';
    const FLICKR_AUTH_URL_PARAM_NAME = '__wpfe_flickr';

    const DISABLED_REASON_CURL_FOPEN = 'curl_fopen';
    const DISABLED_REASON_PHP_VERSION = 'php_version';
}

