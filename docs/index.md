This is a CiviCRM extension designed for "heavy duty" donation receipt
generation.

## Features

* Single or batch donation receipts
* Choose from various output formats (CSV, PDF, sorted PDF, ...)
* Status control: ``draft``, ``receipted``, ``withdrawn``
* Modification control: certain attributes of receipted contributions can not be
  changed any more (unless receipt withdrawn)
* Comes with ``rebook`` feature to rebook contributions to another contact in a
  traceable way
* Allows generation of receipts in big numbers by asynchronous generator
* Snapshot approach prevents modification or duplicates while generating
  donation receipts
* Compliant with German tax requirements

## Restrictions:

* Shipped template in German and optimised for ``dompdf`` and for a ``Print Page (PDF) Format`` of ``ISO A4`` with a 10 mm margin on each side.
* Doesn't process partially deductible contributions yet, 
  see [#23](https://github.com/systopia/de.systopia.donrec/issues/23)
