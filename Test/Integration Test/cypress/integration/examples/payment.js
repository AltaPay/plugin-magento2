import Order from '../PageObjects/objects'




describe ('Magento2', function(){

    it('CC Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.signin()
<<<<<<< HEAD
=======
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
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
<<<<<<< HEAD
=======
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
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
<<<<<<< HEAD
=======
        // ord.email_field('saadidrees57@gmail.com')
        // ord.pass_field('admin@123')
        // ord.login_btn()
>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
        ord.subscription_product()
        ord.subscription_payment()
        ord.admin()
        ord.capture_subscription()
<<<<<<< HEAD
=======
        //ord.capture()
>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
    })

})