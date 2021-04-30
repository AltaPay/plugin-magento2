require('cypress-xpath')

class Order
{
    clrcookies(){
        cy.clearCookies()
    }
    visit()
    {
        cy.fixture('config').then((url)=>{
        cy.visit(url.url) 
        cy.contains('Sign In').click()   
            })    
    }

    signin(){
        cy.fixture('config').then((signin)=>{
        cy.get('[id=email]').type(signin.email)
        cy.get('#pass').type(signin.pass)
        cy.get('#send2').click()
        })
    }

    addproduct()
    {
        cy.contains('Fusion Backpack').wait(3000).click()
        cy.get('[id^=qty]').clear()
        cy.get('[id^=qty]').type('3')
        cy.wait(2000)
        cy.contains('Add to Cart').click()
        cy.wait(3000)
        cy.get('.message-success > div > a').wait(2000).click()
        //cy.contains('shopping cart').click()
        cy.wait(5000)
        cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
        //cy.contains('Proceed to Checkout').wait(3000).click()
        cy.wait(10000)
        cy.get('.button').click()
    }

    cc_payment(){
        cy.get('#terminal1').click()
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(2000)
        cy.get('#creditCardNumberInput').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(3000)

        cy.get('.base').should('have.text', 'Thank you for your purchase!')
        
        cy.get('#maincontent > div.columns > div > div.checkout-success > p:nth-child(1) > a > strong').then(($btn) => {

            // store the button's text
            const txt = $btn.text()
            cy.log(txt)
            }
            )
       
        }

    klarna_payment(){

        cy.get('#terminal2').click()
        cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(5000)
        cy.wait(3000)
        cy.get('[id=submitbutton]').click().wait(3000)
        cy.wait(3000)
        cy.get('[id=klarna-pay-later-fullscreen]').then(function($iFrame){
            const mobileNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-phone-number]')
            cy.wrap(mobileNum).type('(452) 012-3456')
            const personalNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-national-identification-number]')
            cy.wrap(personalNum).type('1012201234')
            const submit = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-continue-button]')
            cy.wrap(submit).click()
            
        })
        
        cy.wait(3000)
        cy.get('.base').should('have.text', 'Thank you for your purchase!')
        
        cy.get('#maincontent > div.columns > div > div.checkout-success > p:nth-child(1) > a > strong').then(($btn) => {

            // store the button's text
            const txt = $btn.text()
            cy.log(txt)
            }
            )
    }

    admin()
    {
        cy.fixture('config').then((admin)=>{
            cy.visit(admin.adminURL)
            cy.get('#username').type(admin.adminUsername)
            cy.get('#login').type(admin.adminPass)
            cy.get('.action-login').click().wait(2000)
            })

        // cy.visit('http://34.253.195.24/magento3/admin')
        // cy.get('#username').type('admin')
        // cy.get('#login').type('admin@1234')
        // cy.get('.action-login').click().wait(2000)
    }

    capture()
        {
            cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
            cy.get('.item-sales-order > a').click().wait(7000)
            // cy.log(txt)
            cy.wait(2000)
            cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
            cy.get('#order_invoice > span').wait(5000).click()
            cy.wait(6000)
            cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
            //cy.contains('Submit Invoice').click()
            cy.wait(6000)
            cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')
            cy.xpath('//*[@id="sales_order_view_tabs_order_invoices"]/span[1]').click()
            cy.xpath('//*[@id="sales_order_view_tabs_order_invoices_content"]/div/div[3]/table/tbody/tr').click()
            cy.wait(2000)
            cy.get('#credit-memo > span').click()
            cy.wait(2000)
            cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/div[2]/section[2]/div[2]/div[2]/div[3]/div[3]/button[2]/span').click()
            cy.wait(1000)
            cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'We refunded')

            
        
        }

    subscription_product()
        {
            cy.get('[id=ui-id-6]').wait(1000).trigger('mouseover')
            cy.get('[id=ui-id-25]').wait(1000).click()
            cy.get('#product-item-info_1 > .details > .name > .product-item-link').click()
            cy.get('[for="radio_subscribe_product"]').wait(1000).click()
            cy.contains('Add to Cart').click()
            cy.wait(2000)
            cy.get('.message-success > div > a').click()
            cy.wait(5000)
            cy.get('.checkout-methods-items > :nth-child(1) > .action').click()
            cy.wait(3000)
            cy.get('.button').click()
        }

    subscription_payment()
        {
            cy.get('#terminal5').click()
            cy.get('._active > .payment-method-content > :nth-child(5) > div.primary > .action').click().wait(3000)
            cy.get('#creditCardNumberInput').type('4111111111111111')
            cy.get('#emonth').type('01')
            cy.get('#eyear').type('2023')
            cy.get('#cvcInput').type('123')
            cy.get('#cardholderNameInput').type('testname')
            cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(6000)

            cy.get('.base').should('have.text', 'Thank you for your purchase!')
            
            cy.get('#maincontent > div.columns > div > div.checkout-success > p:nth-child(1) > a > strong').then(($btn) => {

            // store the button's text
            const txt = $btn.text()
            cy.log(txt)
            }
            )
        }    
    
        capture_subscription()
        {
            cy.get('#menu-magento-sales-sales > [onclick="return false;"]').click().wait(3000)
            cy.get('.item-sales-order > a').click().wait(5000)
            // cy.log(txt)
            cy.xpath('//*[@id="container"]/div/div[4]/table/tbody/tr[1]/td[2]/div').click()
            cy.get('#order_invoice > span').wait(5000).click()
            cy.wait(5000)
            cy.xpath('/html/body/div[2]/main/div[2]/div/div/form/section[4]/section[2]/div[2]/div[2]/div[2]/div[4]/button/span').click()
            //cy.contains('Submit Invoice').click()
            cy.wait(5000)
            cy.get(':nth-child(1) > .note-list-comment').should('include.text', 'Captured amount')
        }

    }    


export default Order