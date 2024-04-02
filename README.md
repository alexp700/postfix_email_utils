# postfix_email_utils

Postfix mail log parsing functions - find bounces, deferred emails etc

Scripts:

mailscan.php - simple scan of all files /var/log/mail.log* - cannot handle gz

Edit the function processBounces() to handle the array differently, such as output to a text file.

You can also make it handle other status with a bit of simple hacking.

Hopefully will be useful to others...
