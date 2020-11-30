# SYSTOPIA Donation Receipts Extension

This is a CiviCRM extension designed for "heavy duty" donation receipt generation. Find the user documentation at http://wiki.civicrm.org/confluence/display/CRM/DonationReceipts+Extension

Features:
* Single or batch donation receipts
* Choose from various output formats (CSV, PDF, sorted PDF, ...)
* Status control: ``draft``, ``receipted``, ``withdrawn``
* Modification control: certain attributes of recipted contributions can not be changed any more (unless receipt withdrawn)
* Comes with ``rebook`` feature to rebook contributions to another contact in a traceable way
* Allows generation receipts in big numbers by asynchronous generator
* Snapshot approach prevents modification or duplicates while generating donation receipts
* Compliant with German tax requirements

Restrictions (ask us for a fix):
* **Drupal or Joomla CMS**. WordPress users currently report errors.
* Currently hardcoded for EUR
* Shipped template German and optimised for ``wkhtm2pdf``
* "Amount in words" currently German only
* Client side (JavaScript) only thoroughly tested on Firefox
* Doesn't process partially deductible contributions yet (see [#23](https://github.com/systopia/de.systopia.donrec/issues/23))

If you want to support the development of this CiviCRM extension, we would be
happy to receive your donation.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=schuttenberg%40systopia.de&currency_code=EUR)
