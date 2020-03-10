# Documentation export
This module provides an admin interface that display the config entities (like
node type, media_type, paragraphs type, vocabulary, users), and their fields
configuration.

This interface is used to help create technical documentation (with PDF export)
, or just quickly see all the entities types present on the website.

![Documentation export admin page](https://www.drupal.org/files/Capture_184.PNG)

## Dependencies
This module uses the library dompdf/dompdf, so it's required to install it with
 composer.

## Installation
```
composer require drupal/documentation_export
```
