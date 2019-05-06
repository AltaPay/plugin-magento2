# Valitor Magento2-2 extension

Valitor has made it much easier for you as merchant/developer to receive secure payments in your Magento2
web shop.


== Change log ==

** Version 0.6.0

	* Improvements:
            - Rebranding from Altapay to Valitor
	        - Platform and plugin versioning information sent to the payment gateway
	        - Added support for virtual products
	* Bug fix:
	        - Validation Error not been shown at back button from checkout page
	        - Order Status stall in "Pending"
	        - Payment capture often fails

** Version 0.5.0

    * Improvement: 
            - Added failed message as order history comment for failed captures
            - Payment statuses handled properly in the notification callback
    * Bug fix:
            - Handled correctly the cancelled payment status
            - Added a fix for the percentage discounts, on item and cart level, for invoice payment methods. 
            	-- Note: only one type of discounts (item or cart) can be used per order

** Version 0.4.2

    * Bug fix: 
            - Item unit price sent when capture
            - Order status set according to the configuration from the admin panel
            - Localization files encoding
            - Corrected the localization file available for the Norwegian language: Bokmål

** Version 0.4.1

    * Bug fix: 
            - Wrong reference (namespace) for the plugin
            - Module failing in rendering the callback form

** Version 0.4.0

    * Improvements: 
            - Added multi language support (Danish, German, Finnish, Swedish, Norwegian and French); other languages will default to English
            - Added a custom column in the order view with correct terminal name, based on the  order's store scope
            - Improved the communication with the payment gateway

** Version 0.3.3

    * Improvement: Revert the usage of a cupon if the payment is canceled by the consumer through the back button
    * Bug fix:
            - Fix the order-cleanup script to be aplicable only to Altapay transactions
            - Fix the order status show in the history comments from the order view

** Version 0.3.2

    * Bug fix:
            - Fix the order-cleanup script to be applicable only to Altapay orders
            - Fix the terminal name in the order view, according to store level

** Version 0.3.1

    * Bug fix: cancel the order if the consumer moves away from the payment form by using the back button in the browser

** Version 0.3.0
    
    * Improvement: payment form with the order details 
    * Bug fix: empty cart if consumer uses the back button from the payment form

** Version 0.2.1

    * Bug fix: 
            - Terms and Condition checkbox in checkout page
            - Order status, before payment, set according to the setting from the store

** Version 0.2.0

    * Improvements: 
            - update the order with the correct status and state in accordance to the payment gateway response 
            - use StoreScope on all connections to the payment gateway
            - add Enable option for terminals on store level

** Version 0.1.11

    * Bug fix: Support for scope

** Version 0.1.10

    * Improvements: 
            - orderLines (including taxAmount) added in the Refund request
            - taxAmount added to Capture request


** Version 0.1.9

    * Bug fixes: error message not shown in case of a payment gateway error
    * Client library updated: new element in the XML response for CreatePaymentRequest

** Version 0.1.8

    * Bug fix: amount type set to float
    
** Version 0.1.7

    * Bug fix: unit price and "handling" GoodsType
    
** Version 0.1.6

    * Support for tax information in the order lines

