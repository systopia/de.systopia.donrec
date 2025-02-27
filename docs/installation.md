# Installation

In the following, the setup of the extension is described as given by the
author.

## Requirements

- PHP settings `max_execution_time` and `memory_limit` should be set to higher
  values if you encounter problems during generation of PDF files
- Uses CiviCRM's built-in pdf creator "dompdf" and a 
- Optional: For grouping PDF files according to the number of pages, "pdfinfo"
  version 0.18.4 or higher has to be installed (globally) on the server.
- Optional: For merging PDF files, "pdfunite" has to be installed (globally) on
  the server.
- Optional: For encrypting PDF files, "pdftk" has to be installed (globally) on
  the server.

## Installation

- Install as any other CiviCRM extension
- Assign the rights to user roles, also for admins
- Configure the extension on "Donation Receipts Settings" in the administration
  console in "CiviContribute"
- Apply necessary changes to the default profile or create your own profile,
  especially make sure that the receipt template matches your needs
- If you want to use the shipped template add a new ``Print Page (PDF) Format`` of ``ISO A4`` with a 10 mm margin on each side and choose that in the according profile.
