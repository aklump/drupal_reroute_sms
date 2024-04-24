<!--
id: readme
tags: ''
-->

# Reroute SMS

This is a copy of [Reroute Email](https://www.drupal.org/project/reroute_email) that has been modified to behave in the same fashion for SMS messages, instead of emails. It will work with the [SMS Framework module](https://www.drupal.org/project/smsframework). If you are familiar with Reroute Email, this should be very straightforward.

{{ composer.install|raw }}

* composer require {{ composer.require }}
* {{ package.name }}
* {{ package.version }}
* {{ package.url }}

## TIPS AND TRICKS

1.  Reroute SMS provides configuration variables that can be directly overridden in the settings.php file of a site. This is particularly useful for moving sites from live to test and vice versa.

2.  An example of setup would be to enable rerouting on a test environment, while making sure it is disabled in production.
```
     Add the following line in the settings.php file for the test environment:
       $config['reroute_sms.settings']['enable'] = TRUE;
       $config['reroute_sms.settings']['phone_number'] = '+13605551212';

     And for the live site, set it as follows:
       $config['reroute_sms.settings']['enable'] = FALSE;
```
