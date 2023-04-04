# DOKU WooCommerce Plugin

DOKU makes it easy for you accept payments from various channels. DOKU also highly concerned the payment experience for your customers when they are on your store. With this plugin, you can set it up on your WooCommerce website easily and make great payment experience for your customers.

## Requirements

- WordPress 5.6 or higher. This plugin is tested with Wordpress 6.2
- WooCommerce 4.9.0 or higher. This plugin is tested with WooCommerce v7.2.1
- PHP v5.6 or higher
- MySQL v5.6 or higher
- DOKU account:
    - For testing purpose, please register to the Sandbox environment and retrieve the Client ID & Secret Key. Learn more about the sandbox environment [here](https://jokul.doku.com/docs/docs/getting-started/explore-sandbox)
    - For real transaction, please register to the Production environment and retrieve the Client ID & Secret Key. Learn more about the production registration process [here](https://jokul.doku.com/docs/docs/getting-started/register-user)

## DOKU WooComerce Already Supported `doku_log`
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

## Payment Channels Supported

**Direct API**
1. Virtual Account:
    - BCA VA
    - Bank Mandiri VA
    - Bank Syariah Mandiri VA
    - DOKU VA

2. Credit Card
3. Alfamart O2O

**Checkout**
Easily embed our well-crafted yet customizable DOKU payment page for your website. With a single integration, you can start accepting payments on your web. With a single integration, Checkout allows you to accept payments from various DOKU payment channels. 

## How to Install

1. Download the plugin from this Repository
1. Extract the plugin and compress the folder "woo-doku-jokul" into zip file
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

### VA Configuration

![VA Configuration](https://i.ibb.co/3r73zdj/Screen-Shot-2021-03-24-at-21-25-53.png)

To show the VA options to your customers, simply toggle the channel that you wish to show.

![VA Configuration Details](https://i.ibb.co/3dyW0j5/Screen-Shot-2021-03-24-at-21-25-22.png)

You can also click Manage to edit how the VA channels will be shown to your customers by clicking the Manage button.

### Credit Card Configuration

![Credit Card Configuration](https://i.ibb.co/Y02Tr3T/Screen-Shot-2021-05-06-at-14-35-31.png)

To show the Credit Card options to your customers, simply toggle the channel that you wish to show.

![Credit Card Configuration Details](https://i.ibb.co/hfFkXrr/Screen-Shot-2021-05-06-at-14-41-53.png)

You can also click Manage to edit how the Credit Card channels will be shown to your customers by clicking the Manage button.

### Alfamart O2O Configuration

![Alfamart O2O Configuration](https://i.ibb.co/Y02Tr3T/Screen-Shot-2021-05-06-at-14-35-31.png)

To show the Alfamart O2O options to your customers, simply toggle the channel that you wish to show.

![Alfamart O2O Configuration Details](https://i.ibb.co/kDMrm45/Screen-Shot-2021-05-06-at-14-40-29.png)

You can also click Manage to edit how the Alfamart O2O channels will be shown to your customers by clicking the Manage button.

### Checkout Configuration

![Jokul Checkout Configuration](https://i.ibb.co/v4LqSfj/Screen-Shot-2022-04-06-at-10-16-05.png)

To show the Checkout options to your customers, simply toggle the channel that you wish to show. DOKU Checkout allows you to accept payments from various DOKU payment channels. You can enable or disable the payment channel that you want to show in your store view in DOKU Backoffice Configuration.

![Jokul Checkout Configuration Details](https://i.ibb.co/MPGD1B1/Screen-Shot-2022-04-06-at-10-19-44.png)

You can also click Manage to edit how the Checkout channels will be shown to your customers by clicking the Manage button. 
Below you can update the QRIS Credential that youre already get from our Support Team.




