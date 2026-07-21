# Reroute SMS

This is a copy of [Reroute Email](https://www.drupal.org/project/reroute_email) that has been modified to behave in the same fashion for SMS messages, instead of emails. It will work with the [SMS Framework module](https://www.drupal.org/project/smsframework). If you are familiar with Reroute Email, this should be very straightforward.

## Install with Composer

Because this is an unpublished, custom Drupal module, the way you install and depend on it is a little different than published, contributed modules.

* Add the following to the **root-level** _composer.json_ in the `repositories` array:
    ```json
    {
     "type": "github",
     "url": "https://github.com/aklump/drupal_reroute_sms"
    }
    ```
* Add the installed directory to **root-level** _.gitignore_
  
   ```php
   /web/modules/custom/reroute_sms/
   ```
* Proceed to either A or B, but not both.
---
### A. Install Standalone
* Require _reroute_sms_ at the **root-level**.
    ```
    composer require aklump_drupal/reroute_sms:^0.0
    ```
---
### B. Depend on This Module

(_Replace `my_module` below with your module (or theme's) real name._)

* Add the following to _my_module/composer.json_ in the `repositories` array. (_Yes, this is done both here and at the root-level._)
    ```json
    {
     "type": "github",
     "url": "https://github.com/aklump/drupal_reroute_sms"
    }
    ```
* From the depending module (or theme) directory run:
    ```
    composer require aklump_drupal/reroute_sms:^0.0 --no-update
    ```

* Add the following to _my_module.info.yml_ in the `dependencies` array:
    ```yaml
    aklump_drupal:reroute_sms
    ```
* Back at the **root-level** run `composer update vendor/my_module`


---
### Enable This Module

* Re-build Drupal caches, if necessary.
* Enable this module, e.g.,
  ```shell
  drush pm-enable reroute_sms
  ```

## TIPS AND TRICKS

1. Reroute SMS provides configuration variables that can be directly overridden in the settings.php file of a site. This is particularly useful for moving sites from live to test and vice versa.

2. An example of setup would be to enable rerouting on a test environment, while making sure it is disabled in production.

_Test Environement > settings.php_

```php
$config['reroute_sms.settings']['enable'] = TRUE;
$config['reroute_sms.settings']['phone_number'] = '+13605551212';
```

_Live Environement > settings.php_

```php
$config['reroute_sms.settings']['enable'] = FALSE;
```
