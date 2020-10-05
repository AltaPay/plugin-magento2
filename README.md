# Valitor Magento2-2 extension

Valitor has made it much easier for you as merchant/developer to receive secure payments in your Magento2
web shop.


== Change log ==

** Version 1.1.1

    * Improvements:
            - Update plugin using the new altapay/api-php dependency

** Version 1.1.0

    * Improvements:
            - Rebranding from Valitor to Altapay
            - Supporting fixed product tax configurations
    * Bug fixes:
            - Fixed order creation issue with free shipping
            - Fixed translation issue for status code
            
** Version 1.0.0

    * Improvements:
            - Added plugin disclaimer
            - Code refactored according to latest coding standards
            - Added support for Klarna Payments (Klarna reintegration) and credit card token
            - Added the option of choosing a logo for each payment method
            - Added new parameters, according to the payment gateway Klarna Payments updates, for the following:
                - Create payment request
                - Capture and refund
            - Added support for AVS
            - Added support for fixed amount and Buy X get Y free discount type
    * Bug fixes:
            - Discount applied to shipping not sent to the payment gateway accordingly
            - Order details dependent on the current tax configuration rather than the one at the time when order was placed


** Version 0.14.0

    * Improvements:
            - Completed the rebranding changes
            - Revamped orderlines for capture and refund calls
            - Added support for bundle product and multiple tax rules
    * Bug fixes:
            - Failed order when coupon code applied only to shipping
            - Duplicated confirmation email sent when e-payments
            - Rounding mismatch issue on compensation amounts
        
** Version 0.13.0

    * Improvements: 
            - Revamp orderlines on various coupon scenarios
            - New enhancements related to various types of discounts and tax scenarios
            - Compensation amount with shipping
            - Added support for:
                - catalog discounts in relation to the latest updates on orderlines 
                - applied discount on virtual products
    * Bug fixes:
            - Partial captures failing on certain cases when Klarna used as payment method
            - Exception thrown on certain cases when refunds are made 

** Version 0.12.0

    * Improvement:
            - Added support for configurable products

** Version 0.11.0

    * Improvements:
            - List of supported languages is dynamically fetched; only supported by the payment gateway are available
            - Terminal dropdown list with default option
            - Added support discounts applied to shipping
            - Handle correctly virtual and downloadable products at checkout completion
    * Bug fixes:
            - Payment method not always shown correctly, according to the store configuration
            - Multiple issues on shipping orderline
            - Terminal enabled based on Default instead of Store configuration level
            - Capture and refund failing on certain cases
            - Amounts having more than two digits not handled correctly
            - Error message related to the back button shown when successful payment

** Version 0.10.0

    * Improvements:
            - Added support for coupons
            - Browser back button improvements
            - Separate order line for cart rules sent the payment gateway
            - Improvements on handling discounts on price including tax
            - Changed private methods to protected to allow easier rewrites(credits to Martin René Sørensen, through pull request)
    * Bug fixes:
            - Unit price not fetched correctly on price including taxes
            - Order status history comment added when consumer gets redirected to the payment gateway

** Version 0.9.0

    * Improvement:
            - Added more details in the history comment for failed orders
    * Note:
            - Only discounts in percentage, two digits, are supported for payments made with Klarna
		
** Version 0.8.0

	* Improvements:
            - New database table according to the branding changes                  
            - Several refactored files                     
            - Database update for cleanup job after the rebranding changes
            - Added a second batch of branding changes (renamed layout files and references)
	* Bug fixes:
            - Error not showing on browser back buton usage.
            - Discounts not handled properly due to unitPrice and discount percentage (the reason for the Klarna failed payments)
            - PHP 7.2 limitation has been removed

** Version 0.7.0

	* Improvements:
            - Invoice automatically generated when autocapture or ePayment is used
            - Added section for order status when AutoCapture is on
	* Bug fixes:
	        - Notification flow broken
	        - Wrong module version sent to the payment gateway
	            
	* Note:
	        - before installing this version please run:
                ```sh
                    $ bin/magento module:uninstall SDM_Altapay -r
                ```

** Version 0.6.0

	* Improvements:
            - Rebranding from Altapay to Valitor
	        - Platform and plugin versioning information sent to the payment gateway
	        - Added support for virtual products
	* Bug fixes:
	        - Validation Error not been shown at back button from checkout page
	        - Order Status stall in "Pending"
	        - Payment capture often fails

** Version 0.5.0

    * Improvement: 
            - Added failed message as order history comment for failed captures
            - Payment statuses handled properly in the notification callback
    * Bug fixes:
            - Handled correctly the cancelled payment status
            - Added a fix for the percentage discounts, on item and cart level, for invoice payment methods. 
            	-- Note: only one type of discounts (item or cart) can be used per order

** Version 0.4.2

    * Bug fixes: 
            - Item unit price sent when capture
            - Order status set according to the configuration from the admin panel
            - Localization files encoding
            - Corrected the localization file available for the Norwegian language: Bokmål

** Version 0.4.1

    * Bug fixes: 
            - Wrong reference (namespace) for the plugin
            - Module failing in rendering the callback form

** Version 0.4.0

    * Improvements: 
            - Added multi language support (Danish, German, Finnish, Swedish, Norwegian and French); other languages will default to English
            - Added a custom column in the order view with correct terminal name, based on the  order's store scope
            - Improved the communication with the payment gateway

** Version 0.3.3

    * Improvement: 
            - Revert the usage of a cupon if the payment is canceled by the consumer through the back button
    * Bug fixes:
            - Fix the order-cleanup script to be aplicable only to Altapay transactions
            - Fix the order status show in the history comments from the order view

** Version 0.3.2

    * Bug fixes:
            - Fix the order-cleanup script to be applicable only to Altapay orders
            - Fix the terminal name in the order view, according to store level

** Version 0.3.1

    * Bug fix: 
            - Cancel the order if the consumer moves away from the payment form by using the back button in the browser

** Version 0.3.0
    
    * Improvement: 
            - Payment form with the order details 
    * Bug fix: 
            - Empty cart if consumer uses the back button from the payment form

** Version 0.2.1

    * Bug fixes: 
            - Terms and Condition checkbox in checkout page
            - Order status, before payment, set according to the setting from the store

** Version 0.2.0

    * Improvements: 
            - Update the order with the correct status and state in accordance to the payment gateway response 
            - Use StoreScope on all connections to the payment gateway
            - Add Enable option for terminals on store level

** Version 0.1.11

    * Bug fix: 
            - Support for store scope

** Version 0.1.10

    * Improvements: 
            - OrderLines (including taxAmount) added in the Refund request
            - Tax amount added to Capture request

** Version 0.1.9

    * Bug fix: 
            - Error message not shown in case of a payment gateway error
    * Client library updated: new element in the XML response for CreatePaymentRequest

** Version 0.1.8

    * Bug fix: 
            - Amount type set to float
    
** Version 0.1.7

    * Bug fix: 
            - Unit price and "handling" GoodsType
    
** Version 0.1.6

    * Support for tax information in the order lines
