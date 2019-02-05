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

