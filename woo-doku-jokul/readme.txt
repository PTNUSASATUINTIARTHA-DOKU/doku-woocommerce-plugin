=== DOKU Payment ===
Contributors: dokuplugin
Donate link: https://doku.com/
Tags: Payment Gateway, Payment, Credit Card, DOKU, woocommerce                                                                 
Requires at least: 2.2
Tested up to: 6.7
Stable tag: 1.3.23
Requires PHP: 5.6v
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
DOKU plugin offers a secure payment with DOKU Checkout, letting customers choose from various payment methods and complete transactions seamlessly.
 
== Description ==

DOKU plugin offers a seamless, secure payment solution allowing your customers to choose from various payment methods and complete transactions directly on your WooCommerce store.

- **Multiple Payment Methods**: Accept credit cards, bank transfers, e-wallets, and other methods.
- **Seamless Checkout**: Deliver a fast and secure payment experience for your customers.
- **Customizable Checkout**: Tailor the checkout page to match your store’s branding and design.
- **Sandbox/Production Mode**: Switch between test and live environments for smooth integration.

Refer to our [documentation](https://docs.doku.com/accept-payments/integration-tools/e-commerce-and-plugins/woocommerce-wordpress) for more information and tips.

== Upgrade Notice ==

= 1.3.23 =
Enhance QRIS status notification
 
== Change Log ==

= 1.3.23 =
Enhance QRIS status notification

= 1.3.22 =
Smooth Redirection to DOKU Checkout Page

= 1.3.21 =
Added support for WC_Cart add fee to enable custom fee handling.

= 1.3.20 =
Enhancement to allow optional phone number input

= 1.3.19 =
Enhancement QRIS notification status

= 1.3.18 =
Added new featured recover abandoned cart system.
Customers can now recover their carts even after the expiry time has passed.

= 1.3.17 =
Improved readme formatting and updated documentation and code standardization.

1.3.16 -
Fixing minor bug and adjust code to wordpress standards.

1.3.15 -
DOKU Payment Support Tax and Fee for indodana.

1.3.14 -
DOKU Payment compatible with WooCommerce Checkout Block.

## Installation Overview
1. Install the DOKU Payment plugin.
2. Activate the plugin from the Plugins page in your WordPress dashboard.
3. Configure DOKU Payment settings in WooCommerce and DOKU Dashboard.

## Requirements
- WordPress 5.6 or higher
- WooCommerce 4.9.0 or higher
- PHP 5.6 or higher
- MySQL 5.6 or higher

== Installation ==

Before installing **DOKU Payment**, ensure that **WooCommerce** is already installed and activated on your WordPress site.

1. Install DOKU payment from our WordPress plugin store or under Plugins menu - Add New Plugin in your WordPress dashboard
2. Go to Plugins page, and activate DOKU Payment

After activation, follow these steps to configure the plugin:

### Step 1: Configure DOKU Payment in WooCommerce

1. Go to **WooCommerce** (in sidebar) > **Settings** > **Payments** tab.
2. Make sure **DOKU-Checkout** and **DOKU General-Configuration** are enable and click **Manage** on **DOKU General-Configuration**.
3. Fill in the fields:
   - Tick Enable DOKU 
   - Choose **Sandbox** (for testing) or **Production** (for live payments).
   - **Client ID** and **Secret Key** (from your DOKU Dashboard).
   - Set the **Expiry Time** for payment sessions (in minutes).

### Step 2: Configure DOKU Dashboard

1. Log in to your **DOKU Dashboard** for testing [https://sandbox.doku.com/bo/login](https://sandbox.doku.com/bo/login) for live payments [https://dashboard.doku.com](https://dashboard.doku.com)
2. Navigate to **Settings** > **Payments Settings**
3. Configure the payment methods you wish to accept, and for each channel, click **Configure**
4. Copy the **Notification URL** from the WooCommerce settings and paste it into the corresponding payment channel settings in your DOKU Dashboard

This also applies to QRIS Notification URL. You may do so by going to QR Payment page under Payment Settings section

== Frequently Asked Questions ==
= Do I need to have a DOKU Merchant account to this plugin ? =
Yes. Sign up on DOKU Business Account [Registration page](https://dashboard.doku.com).

= How do I get my API credentials (Client ID and Secret Key)? =
To obtain your API credentials:

1. Log in to your **DOKU Dashboard**.
2. Navigate to **Settings** > **API Keys**.
3. Your **Client ID** and **Secret Key** will be displayed here. 

Copy these credentials and paste them into the WooCommerce DOKU Payment settings.

= Why do I have to configure notification URL ? =
This step is important because it is where DOKU will notify WooCommerce to update the payment status. If you don't configure it in your dashboard, DOKU won't be able to send the payment status change to WooCommerce.

= How can I get support from DOKU? =
For support, visit our [Help Center](https://help.doku.com/en/support/home) to read FAQs, troubleshoot, or submit a support ticket.

== Screenshots ==
1. Checkout Page