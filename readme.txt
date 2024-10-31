=== Prioritize Hooks ===
Contributors: jweathe
Donate link: http://jonathanweatherhead.com
Tags: override, hooks, actions, filters
Requires at least: 3.2
Tested up to: 3.8
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Prioritize Hooks allows the overriding of the priority of various filters and actions hooked by plugins and themes.

== Description ==

Prioritize Hooks allows the overriding of the priority of various filters and actions hooked by plugins and themes.
A list of non-core actions and filters registered before the `admin_init` action will be shown in the
Prioritize Hooks settings page, with the option of overriding the priority of any that you should so wish. At the moment,
the hook cannot be changed, just the priority of that callback within its respective hook. Note that priorities will not
be overridden until the `wp_loaded` action is run. To disabled a hook, use hyphen(-) as the priority.
Leave a priority blank to reset it.

== Installation ==

1. Unpack the plugin zip and upload the contents to the /wp-content/plugins/ directory. Alternatively, upload the plugin zip via the install screen within the WordPress Plugins manager
2. Activate the plugin through the Plugins manager in WordPress
3. Override hooks in the override page under Tools.

== Frequently Asked Questions ==

= Can I override hooks registered after admin_init? =

No, not through the settings page. However, the plugin is written in a way that allows for a theme/plugin to call on the overriding functionality at any time.

== Screenshots ==
1. A sample view of the Prioritize Hooks settings page where I have overridden the AddCommentsAdds filter attached to `the_content` hook.

== Changelog ==

= 1.2 =
* Added functionality for disabling hooks. use hyphen(-) as the priority.
* Moved Prioritize Hooks under Tools
* Updated code quality to modern WordPress standards

= 1.1.4 =
* minor code changes

= 1.1.2 =
* Added jQuery UI accordion to the settings page to tidy away the callbacks

= 1.0.3 =
* First stable version.

== Upgrade Notice ==

= 1.0.3 =
First stable version.