# SMSServiceBundle

Instalation

composer require amorebietakoudala/smsservice-bundle

# Configuration
Edit .env.local file and add or edit the following parameters.

SMS_USERNAME=sms_username
SMS_PASSWORD=sms_password
SMS_ACCOUNT=sms_account
SMS_TEST=true


If SMS_TEST is set to true, says that it test environment and it won't send message to the API. Set it to true in production environment.

Test the instalation with the following URL:

http://www.domain.com/smsBundle/

It will return the remaining credits of the configured account.
