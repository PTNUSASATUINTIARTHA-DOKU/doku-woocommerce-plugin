# Jokul WooCommerce Plugin

Jokul makes it easy for you accept payments from various channels. Jokul also highly concerned the payment experience for your customers when they are on your store. With this plugin, you can set it up on your WooCommerce website easily and make great payment experience for your customers.

## Requirements

- WordPress 5.6 or higher. This plugin is tested with Wordpress 5.6
- WooCommerce 4.9.0 or higher. This plugin is tested with WooCommerce v4.9.0
- PHP v5.6 or higher
- MySQL v5.6 or higher
- Jokul account:
    - For testing purpose, please register to the Sandbox environment and retrieve the Client ID & Secret Key. Learn more about the sandbox environment [here](https://jokul.doku.com/docs/docs/getting-started/explore-sandbox)
    - For real transaction, please register to the Production environment and retrieve the Client ID & Secret Key. Learn more about the production registration process [here](https://jokul.doku.com/docs/docs/getting-started/register-user)

## Payment Channels Supported

1. Virtual Account:
    - BCA VA
    - Bank Mandiri VA
    - Bank Syariah Mandiri VA
    - Permata VA
    - DOKU VA

## How to Install

1. Download the plugin from this Repository
1. Extract the plugin and compress the folder "jokul-woocommerce" into zip file
1. Login to your WordPress Admin Panel
1. Go to Plugins > Add New
1. Click Upload Plugin and select the zip file
1. Click Install Now
1. Click Activate the plugin
1. Done! You are ready to setup the plugin

## Plugin Usage

### General Configuration

1. Login to your WordPress Admin Panel
1. Click Module > Settings
1. Click Payments tab
1. You will find "Jokul - General Configuration"
1. Toggle the Enabled to ON
1. Here is the fileds that you required to set:

    ![General Configuration](https://i.ibb.co/y84krxh/Screen-Shot-2021-03-24-at-21-24-41.png)

    - **Environment**: For testing purpose, select Sandbox. For accepting real transactions, select Production
    - **Sandbox Client ID**: Client ID you retrieved from the Sandbox environment Jokul Back Office
    - **Sandbox Secret Key**: Secret Key you retrieved from the Sandbox environment Jokul Back Office
    - **Production Client ID**: Client ID you retrieved from the Production environment Jokul Back Office
    - **Production Secret Key**: Secret Key you retrieved from the Production environment Jokul Back Office
    - **Expiry Time**: Input the time that for VA expiration in minutes
    - **Notification URL**: Copy this URL and paste the URL into the Jokul Back Office. Learn more about how to setup Notification URL [here](https://jokul.doku.com/docs/docs/after-payment/setup-notification-url)
1. Click Save Changes button
1. Go Back to Payments Tab
1. Now your customer should be able to see the payment channels and you start receiving payments

### VA Configuration

![VA Configuration](https://i.ibb.co/3r73zdj/Screen-Shot-2021-03-24-at-21-25-53.png)

To show the VA options to your customers, simply toggle the channel that you wish to show.

![VA Configuration Details](https://i.ibb.co/3dyW0j5/Screen-Shot-2021-03-24-at-21-25-22.png)

You can also click Manage to edit how the VA channels will be shown to your customers by clicking the Manage button.
