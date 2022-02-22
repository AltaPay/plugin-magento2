require('cypress-xpath')

class Order {
    clrcookies() {
        cy.clearCookies()
    }

    visit() {
        cy.fixture('config').then((url) => {
            cy.visit(url.shopURL)
        })
    }

    addproduct() {
        cy.contains('Fusion Backpack').wait(1000).click().wait(2000)
        cy.contains('Add to Cart').click().wait(8000)
        cy.get('.showcart').click().wait(2000)
        cy.get('#top-cart-btn-checkout').click().wait(7000)
        cy.get('#customer-email-fieldset > .required > .control > #customer-email').type('demo@example.com')
        cy.get('body').then(($a) => {
            if ($a.find('[name="firstname"]').length) {
                cy.get('[name="firstname"]').type('Testperson-dk')
            }
            if ($a.find('[name="lastname"]').length) {
                cy.get('[name="lastname"]').type('Testperson-dk')
            }
            if ($a.find('[name="country_id"]').length) {
                cy.get('[name="country_id"]').select('Denmark')
            }
            if ($a.find('[name="street[0]"]').length) {
                cy.get('[name="street[0]"]').type('Sæffleberggate 56,1 mf')
            }
            if ($a.find('[name="city"]').length) {
                cy.get('[name="city"]').type('Varde')
            }

            if ($a.find('[name="postcode"]').length) {
                cy.get('[name="postcode"]').type('6800')
            }
            if ($a.find('[name="telephone"]').length) {
                cy.get('[name="telephone"]').type('20123456').wait(6000)
            }
        })
        cy.get('body').then(($p) => {
            if ($p.find(".radio").length) {
                cy.get('.radio').click({ multiple: true })
            }
        })
        cy.get('.button').click().wait(10000)
    }

    cc_payment(CC_TERMINAL_NAME) {
        cy.contains(CC_TERMINAL_NAME).click({ force: true })
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(6000)
        cy.get('#creditCardNumberInput').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(3000)
    }

    klarna_payment(KLARNA_DKK_TERMINAL_NAME) {
        cy.contains(KLARNA_DKK_TERMINAL_NAME).click({ force: true })
        cy.wait(3000)
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(7000)
        cy.get('[id=submitbutton]').click().wait(5000)
        cy.wait(5000)
        cy.get('[id=klarna-pay-later-fullscreen]').then(function ($iFrame) {
            const mobileNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-phone-number]')
            cy.wrap(mobileNum).type('(452) 012-3456')
            const personalNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-national-identification-number]')
            cy.wrap(personalNum).type('1012201234')
            const submit = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-continue-button]')
            cy.wrap(submit).click().wait(2000)
        })
    }

    admin() {
        cy.clearCookies()
        cy.fixture('config').then((admin) => {
            cy.visit(admin.adminURL)
            cy.get('#username').type(admin.adminUsername)
            cy.get('#login').type(admin.adminPass)
            cy.get('.action-login').click().wait(2000)
        })

    }

    capture() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(4000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click().wait(3000)
        cy.get('#order_invoice > span').wait(5000).click().wait(3000)
        cy.wait(6000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
        cy.wait(6000)

    }

    refund() {
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('body').then(($a) => {
            if ($a.find('[title="Credit Memo"]').length) {
                cy.get('[title="Credit Memo"]').click().wait(2000)
            }
        })
        cy.wait(2000)
        cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]/span').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    change_currency_to_EUR_for_iDEAL() {
        cy.get('#menu-magento-backend-stores > [onclick="return false;"]').click()
        cy.get('.item-system-config > a').click()
        cy.contains('Currency Setup').click().wait(2000)
        cy.get('#currency_options_base').wait(2000).select('Euro')
        cy.get('#currency_options_default').wait(2000).select('Euro')
        cy.get('#currency_options_allow').wait(2000).select('Euro')
        cy.get('#save').click().wait(2000)
        //Flush cache
        cy.get('#menu-magento-backend-system > [onclick="return false;"]').scrollIntoView().click()
        cy.get('.item-system-cache > a').click()
        cy.get('#flush_magento').click()
    }

    ideal_payment(iDEAl_EUR_TERMINAL) {
        cy.contains(iDEAl_EUR_TERMINAL).click({ force: true })
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(4000)
        cy.get('#idealIssuer').select('AltaPay test issuer 1')
        cy.get('#pensioPaymentIdealSubmitButton').click()
        cy.get('[type="text"]').type('shahbaz.anjum123-facilitator@gmail.com')
        cy.get('[type="password"]').type('Altapay@12345')
        cy.get('#SignInButton').click()
        cy.get(':nth-child(3) > #successSubmit').click().wait(1000)

    }

    change_currency_to_DKK() {
        cy.get('#menu-magento-backend-stores > [onclick="return false;"]').click()
        cy.get('.item-system-config > a').click()
        cy.get('#save').click()
        cy.contains('Currency Setup').click()
        cy.get('#currency_options_base').select('Danish Krone')
        cy.get('#currency_options_default').select('Danish Krone')
        cy.get('#currency_options_allow').select('Danish Krone')
        cy.get('#save').click().wait(2000)
        //Flush cache
        cy.get('#menu-magento-backend-system > [onclick="return false;"]').scrollIntoView().click()
        cy.get('.item-system-cache > a').click()
        cy.get('#flush_magento').click()
    }

    ideal_refund() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click()
        cy.get('.item-sales-order > a').click().wait(9000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_creditmemo > span').click()
        cy.contains('Refund Offline').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    addpartial_product() {
        cy.contains('Push It Messenger Bag').wait(3000).click().wait(2000)
        cy.get('#product-addtocart-button').click().wait(3000)
        cy.get('.logo').click().wait(2000)
    }

    partial_capture() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order_invoice > span').wait(5000).click()
        cy.wait(6000)
        cy.get('.even > :nth-child(1) > .col-qty-invoice > .input-text').clear().type('0')
        cy.contains("Update Qty's").click()
        cy.wait(6000)
        cy.contains('Submit Invoice').click()
        cy.wait(6000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')
    }

    partial_refund() {
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').wait(2000).click()
        cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').wait(2000).click()
        cy.wait(2000)
        cy.get('body').then(($a) => {
            if ($a.find('[title="Credit Memo"]').length) {
                cy.get('[title="Credit Memo"]').click().wait(2000)
            }
        })
        cy.wait(2000)
        cy.get('.even > :nth-child(1) > .col-refund > .input-text').clear().type('0')
        cy.get('.col-refund > span').click()
        cy.contains("Update Qty's").click().wait(4000)
        cy.xpath('/html/body/div[3]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]').click()
        cy.wait(3000)
        cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')
    }

    release_payment() {
        cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
        cy.get('.item-sales-order > a').click().wait(7000)
        cy.wait(2000)
        cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
        cy.get('#order-view-cancel-button').click()
        cy.get('.confirm > .modal-inner-wrap > .modal-footer > .action-primary').click()

    }

    signin() {
        cy.contains('Create an Account').click()
        cy.get('#firstname').type('Testperson-dk')
        cy.get('#lastname').type('Testperson-dk')

        function generateRandomString() {
            let text = "";
            let alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"

            for (let i = 0; i < 10; i++)
                text += alphabet.charAt(Math.floor(Math.random() * alphabet.length))
            return text;

        }
        const generatedUsername = generateRandomString()
        const generatedPassword = generateRandomString()
        cy.get('#email_address').type(generatedUsername + '@example.com')
        cy.get('#password').type(generatedPassword)
        cy.get('#password-confirmation').type(generatedPassword)
        cy.get('#form-validate > .actions-toolbar > div.primary > .action').click()

        //Manage Shipping details
        cy.contains('Manage Addresses').click()
        cy.get('#street_1').type('Sæffleberggate 56,1 mf')
        cy.get('#telephone').type('20123456')
        cy.get('#country').select('Denmark')
        cy.get('#city').type('Varde')
        cy.get('#zip').type('6800')
        cy.get('#form-validate > .actions-toolbar > div.primary > .action').click()
    }

    subscription_product() {
        cy.get('img').click()
        cy.contains('Push It Messenger Bag').click({ force: true }).wait(5000)

    }

    subscrition_check() {
        cy.get('[for="radio_subscribe_product"]').wait(1000).click()
        cy.get('#product-addtocart-button').click()
        cy.wait(2000)
        cy.get('.message-success > div > a').click()
        cy.wait(5000)
        cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
        cy.wait(3000)

        cy.get('.button').click().wait(3000)

    }
    //Subscription payment
    subscription_payment() {
        cy.fixture('config').then((admin) => {
            cy.contains(admin.SUBSCRIPTION_TERMINAL_NAME).click({ force: true })
            cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(3000)
            cy.get('#creditCardNumberInput').type('4111111111111111')
            cy.get('#emonth').type('01')
            cy.get('#eyear').type('2023')
            cy.get('#cvcInput').type('123')
            cy.get('#cardholderNameInput').type('testname')
            cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(6000)

        })
    }
}

export default Order