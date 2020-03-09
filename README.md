# Documentation export
This module provide an admin interface that display the config entities (like
node type, media_type, paragraphs type and vocabulary), and their fields
configuration.

This interface is used to help create technical documentation (with PDF export)
, or just quickly see all the entities types present on the website.

![Documentation export admin page](https://www.drupal.org/files/Capture_184.PNG)

## Dependencies
This module uses the library dompdf/dompdf, so it's required to install it with
 composer.

## Installation
Add this to your composer.json file in the repository section :
```
"type": "package",
"package": {
    "name": "drupal-keenegan/documentation_export",
    "version": "0.0.1",
    "type": "drupal-module",
    "source": {
        "url": "https://git.drupalcode.org/sandbox/Albin_Guignabert-3113362",
        "type": "git",
        "reference": "0.0.1"
    }
}
```
And then use the command :
```
composer require drupal-keenegan/documentation_export
```
