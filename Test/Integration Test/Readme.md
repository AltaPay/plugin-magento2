# How to run cypress test successfully in your environment 

<<<<<<< HEAD
=======
In order to run the test cases successfully, you need to follow the mentioned guidlines.

>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
## Prerequisites: 

1) Magento2 should be installed on publically accessible URL
2) Cypress should be installed

## Steps 

<<<<<<< HEAD
1) Install dependencies `npm i`

2) Update "cypress/fixtures/config.json" 

3) Execute `./node_modules/.bin/cypress run` in the terminal to run all the tests
=======
1) First we need to install required packages using `npm i`
Next, to set the your URL and Credentials please proceed by following mentioned steps:

2) Search for the file "cypress/fixtures/config.json" and Change the parameters below according to your environment and save the file.

{
  "url": "http://sampledomain.com",
  "email": "demo@mail.com",
  "pass": "password",
  "adminURL":"http://sampledomain.com/admin",
  "adminUsername":"admin_user",
  "adminPass":"admin_password"
}

3) Run `npm run cypress_run` to run all the tests
>>>>>>> 6a70a4a3dbe73e6db09c5c2d123094b63d0bdb60
