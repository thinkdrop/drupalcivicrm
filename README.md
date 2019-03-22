# Composer template for Drupal projects with CiviCRM

This project template provides a starter kit for managing your Drupal 8 and
CiviCRM site with [Composer](https://getcomposer.org/).

It's based on
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project),
so please see that project for general information on managing Drupal via
composer.

The documentation here is going to be focused on CiviCRM!

## Usage

You need a couple of dependencies first:

- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).
- [Bower](https://bower.io/#install-bower)
- [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)

After that you can create your new project via:

```
composer create-project roundearth/drupal-civicrm-project:8.x-dev some-dir --no-interaction
```

Drupal will be installed under the `web` directory, with the `vendor` directory
outside of the webroot. This follows current best practices, but it means two
additional things:

- You'll need to point your webserver at the `web` directory, not the top-level
  directory like you might be used to with Drupal 7, or non-composer Drupal 8
  sites.
- The CiviCRM web assets are synced to the `web/libraries/civicrm` directory,
  so you'll need to configure CiviCRM's "Resource URL" to point to the URL that
  will reach that directory.

## Installing CiviCRM

After getting the code using the command-line above, and doing the normal
Drupal 8 installation, perform the following steps to install CiviCRM:

1. Ensure the `web/sites/default` directory is writeable. On the command-line:

    ```
    chmod +w web/sites/default
    ```

2. Enable the CiviCRM module. On the command-line:

    ```
    drush en -y civicrm
    ```

3. If you were already logged into the Drupal site, then logout and log back in
   again. This is to work around bug
   [CRM-19878](https://issues.civicrm.org/jira/browse/crm-19878).

4. Change the "Resource URL" to `[cms.root]/libraries/civicrm`. You can do this
   by visiting the path http://example.com/civicrm/admin/setting/url?reset=1
   (replacing example.com with your real site name).

5. Clearing caches.

## How does it work?

It's basically 'drupal-composer/drupal-project' with a special Composer plugin
added, which does all the additional steps for CiviCRM. So, if you want to
understand its behavior or contribute, see the Composer plugin:

[https://gitlab.com/roundearth/civicrm-composer-plugin](https://gitlab.com/roundearth/civicrm-composer-plugin)

Specifically, it's this file that does all the real work:

[https://gitlab.com/roundearth/civicrm-composer-plugin/blob/master/src/Handler.php](https://gitlab.com/roundearth/civicrm-composer-plugin/blob/master/src/Handler.php)

## How to add CiviCRM to an existing Drupal 8 site?

Assuming your Drupal 8 site is based on 'drupal-composer/drupal-project', you
can simply add the special Composer plugin for CiviCRM:

[https://gitlab.com/roundearth/civicrm-composer-plugin](https://gitlab.com/roundearth/civicrm-composer-plugin)

## References

- [https://www.mydropwizard.com/blog/better-way-install-civicrm-drupal-8](https://www.mydropwizard.com/blog/better-way-install-civicrm-drupal-8)

