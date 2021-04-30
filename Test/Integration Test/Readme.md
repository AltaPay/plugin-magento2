# How to run cypress test successfully in your environment 

In order to run the test cases successfully, you need to follow the mentioned guidlines.

## Prerequisites: 

1) Magento2 should be installed on publically accessible URL
2) Cypress should be installed

## Steps 

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
