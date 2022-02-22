import Order from '../PageObjects/objects'

describe('Magento2', function () {

    it('TC # 1: CC full capture and refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.clrcookies()
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').wait(5000).then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.clrcookies()
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }

                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 2: Klarna full capture and refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').wait(5000).then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.refund()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 3: iDEAL Payment', function () {
        const ord = new Order()
        ord.visit()
        cy.get('body').then(($body) => {

            if ($body.text().includes('€') === false) {
                ord.admin()
                ord.change_currency_to_EUR_for_iDEAL()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.iDEAL_EUR_TERMINAL != "") {
                    cy.get('body').wait(5000).then(($a) => {
                        if ($a.find("label:contains('" + admin.iDEAL_EUR_TERMINAL + "')").length) {
                            ord.ideal_payment(admin.iDEAL_EUR_TERMINAL)
                            ord.admin()
                            ord.ideal_refund()
                        } else {
                            cy.log(admin.iDEAL_EUR_TERMINAL + ' not found in page')
                            this.skip()
                        }

                    })
                }
                else {
                    cy.log('iDEAl_EUR_TERMINAL skipped')
                    this.skip()
                }
            })
        })
    })

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

        })
    })

    it('TC # 5: CC partial capture', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.partial_capture()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 6: Klarna partial capture', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.partial_capture()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 7: CC partial refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.partial_refund()

                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 8: Klarna partial refund', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('DKK') === false) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addpartial_product()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.capture()
                            ord.partial_refund()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 9: CC release payment', function () {
        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                            ord.cc_payment(admin.CC_TERMINAL_NAME)
                            ord.admin()
                            ord.release_payment()
                        } else {
                            cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('CC_TERMINAL_NAME skipped')
                    this.skip()
                }
            })
        })
    })

    it('TC # 10: Klarna release payment', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
            }
            ord.visit()
            ord.addproduct()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                            ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                            ord.admin()
                            ord.release_payment()
                        } else {
                            cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                            this.skip()
                        }
                    })
                }
                else {
                    cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                    this.skip()
                }
            })

        })
    })

})