# Magento-1-Latest

## Overview

Latest version of Magento1 extension to integrate with the Laybuy payment system.

This version replaces the original Magento extension which should be disabled once this version is installed and 
configured.

## Installation
    
### Install by uploading files:

#### Download the module as "zip" archive

1. Download the latest release

2. Upload the unzipped files to your server	

3. Enable the module configuration, see below for configuration options. 

## Configuration

For configuration go to System > Configuration > Sales > Payment Methods > Laybuy Payment

##### Sandbox
This should only be enabled if you have been provided with Laybuy sandbox credentials for testing.

##### Debug
If enabled all Laybuy requests and responses will be logged to var/log/laybuy_debug.log.  
This file should be manually created after installing the extension and ensure you have Magento logging enabled.

##### Payment Action
There are two payment actions:  
1. **Authorise and Capture** will only create orders in Magento for successful Laybuy payments. This is default.  
2. **Order** will create orders in Magento prior to Laybuy payment, the order status will be updated for cancelled or 
successful payments.

##### Is checkout page using a Onestep Checkout extension?
Set this to Yes if you are using a Onestep Checkout extension instead of the default Magento checkout.

##### Merchant Credentials
Merchant ID, Merchant API Key and Merchant Currency are all required.  
A Laybuy merchant account is restricted to only one currency. If you have a multi-store Magento set up then you will
need to configure these credentials at the website configuration scope.

##### Transfer Line Items
If enabled this will transfer details of each product purchased to Laybuy.

##### Display Installment Amounts
The Laybuy installment amounts can be displayed on category, product and cart pages by enabling these options. These blocks
are inserted into the page using default Magento classes so if you have a customised theme then these may need updating
to successfully add these blocks to your pages.

##### Custom Product Price Block Class
For the product page you can set the class to add the block after if your theme does not use the default price classes.

##### Show Full Logo in Price Breakdown
This option dictates whether to display the full Laybuy logo or just the Laybuy icon in the price installments block.

##### Disable Laybuy Info Page
The extension creates a /laybuy information page. You can add a link to your site to this page to provide information
on how Laybuy works. This option should be disabled if you already have a CMS page using this URL path.