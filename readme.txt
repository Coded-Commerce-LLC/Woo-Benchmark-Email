=== Woo Benchmark Email ===
Contributors: seanconklin, randywsandberg
Donate link: https://codedcommerce.com/donate
Tags: abandoned cart, customer newsletters, marketing automation, Benchmark Email, WooCommerce
Requires at least: 4.0.1
Tested up to: 5.0
Requires PHP: 5.6
Stable tag: 1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Connects WooCommerce with Benchmark Email - syncing customers and abandoned carts.

== Description ==

[youtube https://www.youtube.com/watch?v=c0BGIXT8xMk]

Feature: Connect to Benchmark Email ReST API
* Separate from optional Benchmark Email Lite plugin, this setting connects to one API key for WooCommerce specific communications.
* Settings located in WP Admin > WooCommerce > Settings > Advanced > Benchmark Email

Feature: Customer carts to Woo Abandoned Carts list
* Any time somebody clicks to go to the checkout page their email address, name, and cart details get sent to the Woo Abandoned Carts contact list.
* They may be logged in and this field might be pre-populated, still works.
* They may be making a purchase as a Guest and the field gets caught as typed.
* They may be authenticated yet not have Woo history, so they type the email in.
* There is a 2 second delay to ensure they are done typing the email before it sends.
* The email is validates as a properly formatted email before it gets sent to Benchmark.
* Use Automation Pro to manage the templates, timing of emails, and eventual deletion from list since subscription to this list is for short-term usage only.
* A URL and order data are included, so Automation Pro can manage the workflow.
* Benchmark is to provide the prebuilt Automation Pro template for our users.

Feature: Customer orders to Woo Customers list
* Also gets them removed from the Woo Abandoned Carts contact list since they have purchased.
* They get added to the Woo Customers list only if they select the checkbox.
* They get added to the Woo Customers list if there is no checkbox to select (if label disabled in settings).

Feature: Sync all order history to Woo Customers list
* Copies all historic orders, whether Guest or Registered customers to Woo Customers list.
* Uses AJAX to prevent timeouts, but may run for some time on larger stores.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woo-benchmark-email` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the `WooCommerce->Settings->Advanced->Benchmark Email` screen to configure the plugin

== Screenshots ==

1. The settings panel where you place your API key
2. A sample user completing checkout showing the data being captured behind the scenes as viewed from the inspector

== Changelog ==

= 1.1 on 2018-11-01 =
* Added sister product activation and installation checks and message

= 1.0 on 2018-09-23 =
* Initial release

== Upgrade Notice ==

= 1.1 =
* Minor update

= 1.0 =
* Initial release

== Support ==

[Sign Up](http://www.benchmarkemail.com/Register) for your free Benchmark Email account.

Obtain your Benchmark Email API Key by logging into Benchmark Email, click on your Username, then click Integrations, now select the API Key option from the Left or Dropdown menu, last copy “Your API Key.”

Need help? Please call Benchmark Email at 800.430.4095
