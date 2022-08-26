# Changelog
All notable changes to this project will be documented in this file.

## [3.0.0]
**Improvements**
- Add support when cart and catalog rules are applied simultaneously
- Make text "No saved credit cards" translatable

**Fixes**
- Cancel order issues when there is no transaction
- Order failing issue when applying a fixed discount on the cart
- Product stock not updating when order status change from cancel to processing
- Saved credit cards grid styling for mobile view

## [2.0.5]
**Fixes**
- Resolve compilation issue for Magento 2.1.9 and below
- Add missing upgrade scripts

## [2.0.4]
**Improvements**
- Support multi-language for order summary section in form rendering

## [2.0.3]
**Improvements**
- Added support for terminal sorting

## [2.0.2]
**Fixes**
- Remove payment terminal shown upon editing order from backend
- Fix "Could not load HTML" issue cause by X-Magento-Tags

## [2.0.1]
**Improvements**
- Redirect failed orders to cart details page

## [2.0.0]
**Improvements**
- Code formatted to Magento's recommended coding standards

**Fixes**
- Fix variable undefined issue

## [1.1.1]
**Improvements**
- Update plugin using the new altapay/api-php dependency

## [1.1.0]

**Improvements**
- Rebranding from Valitor to Altapay
- Supporting fixed product tax configurations

**Fixes**
- Fixed order creation issue with free shipping
- Fixed translation issue for status code
            
## [1.0.0]
**Improvements**
- Added plugin disclaimer
- Code refactored according to latest coding standards
- Added support for Klarna Payments (Klarna reintegration) and credit card token
- Added the option of choosing a logo for each payment method
- Added new parameters, according to the payment gateway Klarna Payments updates, for the following:
    - Create payment request
    - Capture and refund
- Added support for AVS
- Added support for fixed amount and Buy X get Y free discount type

**Fixes**
- Discount applied to shipping not sent to the payment gateway accordingly
- Order details dependent on the current tax configuration rather than the one at the time when order was placed


## [0.14.0]
**Improvements**
- Completed the rebranding changes
- Revamped orderlines for capture and refund calls
- Added support for bundle product and multiple tax rules

**Fixes**
- Failed order when coupon code applied only to shipping
- Duplicated confirmation email sent when e-payments
- Rounding mismatch issue on compensation amounts
        
## [0.13.0]
**Improvements**
- Revamp orderlines on various coupon scenarios
- New enhancements related to various types of discounts and tax scenarios
- Compensation amount with shipping
- Added support for:
    - catalog discounts in relation to the latest updates on orderlines 
    - applied discount on virtual products

**Fixes**
- Partial captures failing on certain cases when Klarna used as payment method
- Exception thrown on certain cases when refunds are made 

## [0.12.0]
**Improvements**
- Added support for configurable products

## [0.11.0]
**Improvements**
- List of supported languages is dynamically fetched; only supported by the payment gateway are available
- Terminal dropdown list with default option
- Added support discounts applied to shipping
- Handle correctly virtual and downloadable products at checkout completion

**Fixes**
- Payment method not always shown correctly, according to the store configuration
- Multiple issues on shipping orderline
- Terminal enabled based on Default instead of Store configuration level
- Capture and refund failing on certain cases
- Amounts having more than two digits not handled correctly
- Error message related to the back button shown when successful payment

## [0.10.0]
**Improvements**
- Added support for coupons
- Browser back button improvements
- Separate order line for cart rules sent the payment gateway
- Improvements on handling discounts on price including tax
- Changed private methods to protected to allow easier rewrites(credits to Martin René Sørensen, through pull request)

**Fixes**
- Unit price not fetched correctly on price including taxes
- Order status history comment added when consumer gets redirected to the payment gateway

## [0.9.0]
**Improvements**
- Added more details in the history comment for failed orders
    * Note:
            - Only discounts in percentage, two digits, are supported for payments made with Klarna
		
## [0.8.0]
**Improvements**
- New database table according to the branding changes
- Several refactored files                     
- Database update for cleanup job after the rebranding changes
- Added a second batch of branding changes (renamed layout files and references)

**Fixes**
- Error not showing on browser back buton usage.
- Discounts not handled properly due to unitPrice and discount percentage (the reason for the Klarna failed payments)
- PHP 7.2 limitation has been removed

## [0.7.0]
**Improvements**
- Invoice automatically generated when autocapture or ePayment is used
- Added section for order status when AutoCapture is on

**Fixes**
- Notification flow broken
- Wrong module version sent to the payment gateway
* Note:
    - before installing this version please run:
            
        ```sh $ bin/magento module:uninstall SDM_Altapay -r```

## [0.6.0]
**Improvements**
- Rebranding from Altapay to Valitor
- Platform and plugin versioning information sent to the payment gateway
- Added support for virtual products

**Fixes**
- Validation Error not been shown at back button from checkout page
- Order Status stall in "Pending"
- Payment capture often fails

## [0.5.0]
**Improvements**
- Added failed message as order history comment for failed captures
- Payment statuses handled properly in the notification callback

**Fixes**
- Handled correctly the cancelled payment status
- Added a fix for the percentage discounts, on item and cart level, for invoice payment methods. 
	-- Note: only one type of discounts (item or cart) can be used per order

## [0.4.2]
**Fixes** 
- Item unit price sent when capture
- Order status set according to the configuration from the admin panel
- Localization files encoding
- Corrected the localization file available for the Norwegian language: Bokmål

## [0.4.1]
**Fixes** 
- Wrong reference (namespace) for the plugin
- Module failing in rendering the callback form

## [0.4.0]
**Improvements** 
- Added multi language support (Danish, German, Finnish, Swedish, Norwegian and French); other languages will default to English
- Added a custom column in the order view with correct terminal name, based on the  order's store scope
- Improved the communication with the payment gateway

## [0.3.3]
**Improvements**
- Revert the usage of a cupon if the payment is canceled by the consumer through the back button

**Fixes**
- Fix the order-cleanup script to be aplicable only to Altapay transactions
- Fix the order status show in the history comments from the order view

## [0.3.2]
**Fixes**
- Fix the order-cleanup script to be applicable only to Altapay orders
- Fix the terminal name in the order view, according to store level

## [0.3.1]
**Fixes**
- Cancel the order if the consumer moves away from the payment form by using the back button in the browser

## [0.3.0]
**Improvements**
- Payment form with the order details

**Fixes**
- Empty cart if consumer uses the back button from the payment form

## [0.2.1]
**Fixes** 
- Terms and Condition checkbox in checkout page
- Order status, before payment, set according to the setting from the store

## [0.2.0]
**Improvements** 
- Update the order with the correct status and state in accordance to the payment gateway response 
- Use StoreScope on all connections to the payment gateway
- Add Enable option for terminals on store level

## [0.1.11]
**Fixes** 
- Support for store scope

## [0.1.10]
**Improvements** 
- OrderLines (including taxAmount) added in the Refund request
- Tax amount added to Capture request

## [0.1.9]
**Fixes**
- Error message not shown in case of a payment gateway error
    * Client library updated: new element in the XML response for CreatePaymentRequest

## [0.1.8]
**Fixes**
- Amount type set to float
    
## [0.1.7]
**Fixes**
- Unit price and "handling" GoodsType
    
## [0.1.6]
- Support for tax information in the order lines