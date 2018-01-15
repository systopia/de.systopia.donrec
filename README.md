# SYSTOPIA Donation Receipts Extension

**This fork is mainteined by [iXiam Global Solutions](https://github.com/ixiam)**

This is a CiviCRM extension designed for "heavy duty" donation receipt generation. Find the user documentation at http://wiki.civicrm.org/confluence/display/CRM/DonationReceipts+Extension

Features:
* Single or batch donation receipts
* Choose from various output formats (CSV, PDF, sorted PDF, ...)
* Status control: ``draft``, ``receipted``, ``withdrawn``
* Modification control: certain attributes of recipted contributions can not be changed any more (unless receipt withdrawn)
* Comes with ``rebook`` feature to rebook contributions to another contact in a traceable way
* Allows generation receipts in big numbers by asynchronous generator
* Snapshot approach prevents modification or duplicates while generating donation receipts
* Compliant with German adn Spanish tax requirements
* Multicurrency
* "Amount in words" currently in German, Spanish and English and ready to add new language classes

Restrictions (ask us for a fix):
* Shipped template German and optimised for ``wkhtm2pdf``
* Client side (JavaScript) only thoroughly tested on Firefox
* Doesn't process partially deductible contributions yet (see [#23](https://github.com/systopia/de.systopia.donrec/issues/23))
