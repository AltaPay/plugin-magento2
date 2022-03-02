import Order from '../PageObjects/objects'

describe('Magento2', function () {

    it('TC # 4: Subscription', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.signin()
        ord.subscription_product()
        cy.get('body').then(($a) => {
            if ($a.find("label:contains('Subscribe to this product.')").length) {
                cy.contains('Subscribe to this product.')
                    .click({ force: true })
                ord.subscrition_check()
                ord.subscription_payment()
                ord.admin()
                ord.capture()
            }
            else {
                cy.log('Subscription product not found')
                this.skip()
            }
            cy.wait(3000)
        })
    })

})