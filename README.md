# SYSTOPIA Donation Receipts Extension

This is a CiviCRM extension designed for "heavy duty" donation receipt generation. Find the user documentation at http://wiki.civicrm.org/confluence/display/CRM/DonationReceipts+Extension

Features:
* Single or batch donation receipts
* Compliant with German tax requirements
* Status control: ``draft``, ``receipted``, ``withdrawn``
* Modification control: certain attributes of recipted contributions can not be changed any more (unless receipt withdrawn)
* Allows generation receipts in big numbers by asynchronous generator
* Snapshot approach prevents modification or duplicates while generating donation receipts
* Choose from various output formats
