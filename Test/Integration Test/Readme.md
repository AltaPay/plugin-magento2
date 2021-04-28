In order to run the test cases successfully, you need to follow the mentioned guidlines.

Prerequisites:

1) Magento2 should be installed on publically accessible URL
2) User credentials for both admin and store front

Next, to set the your URL and Credentials please proceed by following mentioned steps:

1) Search for the config.json file under "cypress/fixtures/config.json"

2) Change the parameters below according to your environment and save the file.

{
  "url": "http://34.253.195.24/magento3",
  "email": "saadidrees57@gmail.com",
  "pass": "admin@123",
  "adminURL":"http://34.253.195.24/magento3/admin",
  "adminUsername":"admin",
  "adminPass":"admin@1234"
}

3) Finally, in Cypress test case list, click the "payment.js" to run the integration tests.