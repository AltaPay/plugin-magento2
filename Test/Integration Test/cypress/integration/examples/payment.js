import Order from '../PageObjects/objects'




describe ('Magento2', function(){

    it('CC Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.signin()
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
        ord.addproduct()
        ord.cc_payment()
        ord.admin()
        ord.capture()
    })

    it('Klarna Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.signin()
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
        ord.addproduct()
        ord.klarna_payment()
        ord.admin()
        ord.capture()
    })

    it('Subscription', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.signin()
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
        ord.subscription_product()
        ord.subscription_payment()
        ord.admin()
        ord.capture_subscription()
        //ord.capture()
    })

})