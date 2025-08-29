# KORT-Payments-Woocommerce-Plugin

[![WooCommerce](https://img.shields.io/badge/WooCommerce-2.0%2B-blue.svg)](https://woocommerce.com/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Plugin Name:** KORT_Woo_Gateway

KORT Payment's WooCommerce plugin has arrived for all of your payment needs. Start accepting credit cards on your WooCommerce 2.5+ site, with a valid SSL connection (sorry, non-self-signed only) and cURL activated on your server, you will be able to process payments using your PayHQ Merchant account.

---

## Installation

**[Click here to download a new KORT_Woo_Gateway](https://github.com/Payfirma/New_Payfirma_Woo_Gateway/blob/master/download/New_Payfirma_Woo_Gateway.zip)**

**Note:** If you already installed the previous version, please delete the previous version and reinstall with the new version (4.1)

![Plugin Screenshot](https://user-images.githubusercontent.com/67436452/153306571-5a356d01-5a67-4789-b195-eacb08c3f0b1.png)

The first step in your integration will be installing WooCommerce to your WordPress storefront.

Once you've logged into your WordPress account, you'll be able to find the Plugins category in the main menu to the left side of the page, commonly just below Appearance. Click on **Plugins** to expand available options before clicking on the revealed option **Add New**.

![Installation Step 1](https://user-images.githubusercontent.com/67436452/113324599-282db580-92cc-11eb-8ddd-f895eda8fe55.png)

Under the Install Plugins header, you'll find several options; the second, **Upload**, will enable you to select your .zip of the KORT WooCommerce plugin. Click on the **Choose File** button, and find your .zip of the KORT WooCommerce plugin.

![Installation Step 2](https://user-images.githubusercontent.com/67436452/113324873-7a6ed680-92cc-11eb-99a4-0ede994c18c2.png)

Once your plugin is selected, click on **Install Now**. The plugin will install itself automatically, and after your installation is finished, click on **Activate Plugin**, just beneath the notification.

![Installation Step 3](https://user-images.githubusercontent.com/67436452/113324927-8d81a680-92cc-11eb-8a3c-26c99740f3c1.png)

---

## Configuration

One of our mandatory requirements for PayHQ is an SSL connection for credit card transactions. To force this, select your newly accessible WooCommerce option in the main menu to the left side of the page. Once the WooCommerce options have expanded in the menu, click on **Settings**.

![WooCommerce Settings](https://user-images.githubusercontent.com/67436452/113325053-bb66eb00-92cc-11eb-83e5-6355fb225484.png)

From the Settings page, you will automatically be brought to the General Settings Tab. Ensure your Base Location and Currency are set.

<img width="688" alt="General Settings" src="https://user-images.githubusercontent.com/67436452/113325332-231d3600-92cd-11eb-8ff9-4149c5204af8.png">

Go to **Payments** tab within Settings,

![Payments Tab](https://user-images.githubusercontent.com/67436452/113325663-958e1600-92cd-11eb-937d-925813e497ec.png)

Go to **Manage**

This is where you will be able to enter in your assigned Iframe Access Token, as provided through PayHQ Settings.

**[PayHQ Settings](https://hq.payfirma.com/#/settings/hpp)**

![PayHQ Settings](https://user-images.githubusercontent.com/67436452/153472764-1a6b8760-e63e-434b-ad2b-7437050e4f12.png)

---

## To get Iframe Access Token

Please, Login at PayHQ and go to Settings.

**[PayHQ Settings](https://hq.payfirma.com/#/settings/hpp)**

![PayHQ Login](https://user-images.githubusercontent.com/67436452/153307893-f063df7d-8459-42fd-876c-eb364c9fc489.png)

### Create Iframe Access Token

![Create Token](https://user-images.githubusercontent.com/67436452/153307684-87572649-d819-43fa-acab-19f53b6fb226.png)

---

## Changelog
### Version 4.7
- Improved button detection reliability by moving button identification logic before async operations to prevent currentTarget changes

### Version 4.6
- Fixed Safari compatibility issue with pay button submission by replacing unreliable `:focus` detection with `currentTarget` approach

### Version 4.5
- Fixed issue where credit card fields were validated even when PayFirma was not the selected payment method.
- Checkout process now correctly checks if PayFirma is selected before running validation.

### Version 4.4
- Rebrand from Payfirma → KORT Payments, fixed version check bug preventing activation for woocommerce version 10.0.0+

### Version 4.3
- Fixed customer payment page from order details

### Version 4.2
- Updated the order place button about conflict with other payment options.
- Update MerrcoPayfirma logo

### Version 4.1
- Fixed with css

### Version 4.0
- Integrate with Payfirma iframe application

### Version 3.1
- update plugin with V2 API
- change key & merchant_id to Client Id & Client Secret

---

## Development Notes
- Delete contents of the download folder `rm -rf download/*`
- Create new zip `zip -r download/New_Payfirma_Woo_Gateway.zip . -x "download/*"`

