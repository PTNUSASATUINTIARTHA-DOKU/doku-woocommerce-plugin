=== DOKU Payment ===
Contributors: dokuplugin
Donate link: https://doku.com/
Tags: Payment Gateway, Payment                                                                 
Requires at least: 2.2
Tested up to: 6.6
Stable tag: 1.3.13
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 
== Description ==
 
Accept payment through various payment channels with DOKU. Make it easy for your customers to purchase on your store.
 
For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.
 
A few notes about the sections above:
 
* "Contributors" is a comma separated list of wordpress.org usernames
* "Tags" is a comma separated list of tags that apply to the plugin
* "Requires at least" is the lowest version that the plugin will work on
* "Tested up to" is the highest version that you've *successfully used to test the plugin*
* Stable tag must indicate the Subversion "tag" of the latest stable version
 
Note that the `readme.txt` value of stable tag is the one that is the defining one for the plugin.  If the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used for displaying information about the plugin.
 
If you develop in trunk, you can update the trunk `readme.txt` to reflect changes in your in-development version, without having that information incorrectly disclosed about the current stable version that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.
 
If no stable tag is provided, your users may not get the correct version of your code.
 
== Upgrade Notice ==
 
= 1.3.13 =
DOKU Payment compatible with WooCommerce Checkout Block.

== A brief Markdown Example ==


## Requirements

- WordPress 5.6 or higher. This plugin is tested with Wordpress 6.2
- WooCommerce 4.9.0 or higher. This plugin is tested with WooCommerce v7.2.1
- PHP v5.6 or higher
- MySQL v5.6 or higher
- DOKU account:
    - For testing purpose, please register to the Sandbox environment and retrieve the Client ID & Secret Key. Learn more about the sandbox environment [here](https://jokul.doku.com/docs/docs/getting-started/explore-sandbox)
    - For real transaction, please register to the Production environment and retrieve the Client ID & Secret Key. Learn more about the production registration process [here](https://jokul.doku.com/docs/docs/getting-started/register-user)

## DOKU Already Supported `doku_log`
​
This `doku_log` is useful to help simplify the process of checking if an issue occurs related to the payment process using the DOKU Plugin. If there are problems or problems using the plugin, you can contact our team by sending this doku_log file. `Doku_log` will record all transaction processes from any channel by date.

​
## How to use and take doku_log file?
​
1. Open your `WooCommerce_dir` directory on your store's webserver.
2. Create folder `doku_log` in your directory store's, so plugin will automatically track log in your store's webserver.
3. Then check `doku_log` and open file in your store's webserver.
4. You will see `doku log` file by date.
5. And you can download the file. 
6. If an issue occurs, you can send this `doku_log` file to the team to make it easier to find the cause of the issue.

**Checkout**
Easily embed our well-crafted yet customizable DOKU payment page for your website. With a single integration, you can start accepting payments on your web. With a single integration, Checkout allows you to accept payments from various DOKU payment channels. 

 ![General Configuration](https://i.ibb.co/rMbyngg/screencapture-sandboxenv-devwoolatest-wp-admin-admin-php-2022-04-06-09-26-39.png)

    - **Environment**: For testing purpose, select Sandbox. For accepting real transactions, select Production
    - **Sandbox Client ID**: Client ID you retrieved from the Sandbox environment DOKU Back Office
    - **Sandbox Secret Key**: Secret Key you retrieved from the Sandbox environment DOKU Back Office
    - **Production Client ID**: Client ID you retrieved from the Production environment DOKU Back Office
    - **Production Secret Key**: Secret Key you retrieved from the Production environment DOKU Back Office
    - **Expiry Time**: Input the time that for VA expiration in minutes
    - **Notification URL**: Copy this URL and paste the URL into the DOKU Back Office. Learn more about how to setup Notification URL [here](https://jokul.doku.com/docs/docs/after-payment/setup-notification-url)
    - **QRIS Notification URL** : Copy this URL and and contact our support team to help paste in QRIS Backoffice. This channel only support if youre enabling Checkout as a payment method.
    - **Email Notifications** : You can activated the feature send emails for VA and O2O channels. This email contains how to pay for the VA or Paycode.
    - **Sub Account Feature** : This feature helps you to routing your payment into your Sub Account ID. You can see the details for payment flow if youre using this feature [here](https://jokul.doku.com/docs/docs/jokul-sub-account/jokul-sub-account-overview)
1. Click Save Changes button
1. Go Back to Payments Tab
1. Now your customer should be able to see the payment channels and you start receiving payments

### Checkout Configuration

![DOKU Checkout Configuration](https://i.ibb.co/v4LqSfj/Screen-Shot-2022-04-06-at-10-16-05.png)

To show the Checkout options to your customers, simply toggle the channel that you wish to show. DOKU Checkout allows you to accept payments from various DOKU payment channels. You can enable or disable the payment channel that you want to show in your store view in DOKU Backoffice Configuration.

![DOKU Checkout Configuration Details](https://i.ibb.co/MPGD1B1/Screen-Shot-2022-04-06-at-10-19-44.png)

You can also click Manage to edit how the Checkout channels will be shown to your customers by clicking the Manage button. 
Below you can update the QRIS Credential that youre already get from our Support Team.
  
Links require brackets and parenthesis:
 
Here's a link to [WordPress](https://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax]. Link titles are optional, naturally.
 
Blockquotes are email style:
 
> Asterisks for *emphasis*. Double it up  for **strong**.
 
And Backticks for code:
 
`<?php code(); ?>`