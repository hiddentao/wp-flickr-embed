=== Wordpress Flickr Embed ===

Plugin Name: Wordpress Flickr Embed
Contributors: yuji.od, randomaniac
Author URI: http://hiddentao.com
Plugin URI: https://github.com/hiddentao/wp-flickr-embed
Tags: admin, images, posts, flickr, embed
Requires at least: 3.4.1
Tested up to: 3.4.1
Stable tag: 1.0.0

== Description ==

Wordpress Flickr Embed is a Wordpress plugin that provides an interactive interface for adding Flickr images to your posts. The interface is conveniently launched from the visual editor toolbar.

This is a fork of the (http://wordpress.org/extend/plugins/wordpress-media-flickr/) plugin for Wordpress and provides numerous bug fixes and enhancements over the original.

Features:
* Search for an select any public photo or a private one from your photostream (requires Flickr authorization).
* Insert a photo anywhere in your post, choosing its title, size and how it should be aligned to the text.
* Link the photo back to its Flickr page or to a different-sized version of itself.
* Supports Lightbox and Lightview popups.

Source on Github: https://github.com/hiddentao/wp-flickr-embed


== Installation ==

1. Download and unzip the zip file into your Wordpress `plugins` folder such that the plugin files are at: `wp-content/plugins/wp-flickr-embed/...`
1. Activate the plugin within your blog's administration options.
1. Goto menu `Settings > Flickr Embed`. Here you can authenticate with Flickr and edit other settings.
1. All done!

== Screenshots ==

1. The Wordpress Flickr Embed options page under the `Settings` menu
1. The Wordpress Flickr Embed panel that appears on the post insertion screen

== Frequently Asked Questions ==

= How do I authenticate the plugin with my Flickr account? =

1. Goto `Settings > Flickr Embed` and click `Flickr Authenticate`.
2. This will take you to Flickr where upon you should login and follow the instructions.
3. Once you've authorized it in Flickr go back to `Settings > Flickr Embed` and click `Finish authenticate`.

= I don't have a Flickr account. Can I still use the plugin? =

Yes you can. You will still be able to insert any public photo available on Flickr. Authentication is only needed if you wish to access your own private photos on Flickr.

= How do I get it working with Lightbox? =

1. Goto `Settings > Flickr Embed`
2. Set `Link the photo to` to `the photo itself`.
3. Set `The "rel" attribute of link tag` option to `lightbox`.
4. Click `Update options`.


== Changelog ==

= 1.0.0 (Nov 5, 2012) =
* First version
