# IdRef Plugin for Lodel 1

This plugin lets you search for and save IdRef identifiers for persons.

## Features

- Add a widget on entities edition form

  - The widget allows a search for an IdRef in the IdRef database for all persons linked to the document.
  - If only one IdRef is found, it is retrieved into the IdRef field.
  - If several IdRefs are found, user can search the IdRef interface for all the people found and retrieve the correct IdRef using the "Lier la notice" button.
  - If no IdRef matches the author, the plugin sends data to IdRef to pre-fill the cataloging form for creating the new notice (IdRef account required).

### Optional feature

- The "Report by email" 

  - This feature add a button "Report missing IdRef"/"Signaler un IdRef manquant" to the widget. 
  - An alert for a missing IdRef is sent by email to a configurable destination. Use case: a documentalist with the rights to create a missing IdRef in the IdRef database.

## Requirements

- Lodel 1 
- PHP >= 7.4 (tested with PHP 7.4, PHP 8.2)

## Installation

- git clone repository in `LODEL_ROOT_DIRECTORY/share/plugins/custom/idref` (:warning: The plugin directory MUST be named `idref`)
- To activate the "Report by email" feature, you need to add in `lodelconfig.php` a from and a destination email for reports:

```php
$cfg['idref_report_to_email'] = "from@example.com";
$cfg['idref_report_from_email'] = "destination@example.com";
```

## Plugin activation

When the plugin is activated:

- the `idref` field is created in the Lodel site database (table `entities_auteurs`) if it doesn't already exist.
- the translations (English, French) are defined in the Lodel administration translations (if not already defined).

## Credits

Authors: Jean-François Rivière, Émilie Cornillaux, Olivier Crouzet

This plugin uses parts of :
 - https://github.com/oliviercrouzet/idplus
 - https://documentation.abes.fr/aideidrefdeveloppeur/index.html#installation

(thanks to the contributors)

This plugin was created as part of the [Quameo project](https://www.ouvrirlascience.fr/quameo/), funded by the [National Fund for Open Science](https://www.ouvrirlascience.fr/).

