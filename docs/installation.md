# Installation

In the following, the setup of the extension is described as given by the
author.

## Requirements
- PHP version 5.4 or 5.5
- (Browser Firefox for operating)
- JavaScript activated in browser
- PHP setting `max_execution_time` should at least be `300` seconds (5 minutes)
- PHP setting `memory_limit` should at least be `256M`, preferably `512M`
  (1/2 GB RAM)
- The built-in pdf creator "dompdf" should be exchanged with "wkpdf2html" 
- Optional: For grouping pdf's according to the number of pages, "pdfinfo"
  version 0.18.4 or higher has to be installed (globally) on the server.

## Installation

- Create a dump of your DB
- Deactivate extended logging
- German only: load translation file (civicrm.mo). It is found under
  `civicrm/l10n/de_DE` or `civicrm/l10n/de_DE/LC_MESSAGES`, where the civicrm.mo
  has to be copied to. For other languages, proceed accordingly. Perform a
  backup of the files before copying.
- Install the extension
- Activate extended logging
- Assign the rights to user roles, also for admins
- Configure the extension on "Donation Receipts Settings" in the administration
  console in "CiviContribute"
- Apply necessary changes to the template

## Update

- Create a dump of your DB
- Deactivate the extension
- Deactivate extended Logging
- Delete the former version of the extension
- Unzip the new version of the extension
- Activate the extension
- Activate extended logging
- Load updated translation files if necessary
- Default template is stored under
  de.systopia.donrec/templates/Export/default_template.tpl
