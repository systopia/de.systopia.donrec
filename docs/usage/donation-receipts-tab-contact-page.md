# "Donation Receipts" tab on the contact page

On the tab symbol, a counter indicates the number of valid receipts in the tab.
The counter ist updated when reloading the page. Within the tab, a download
button for the receipt an the details of the receipt, respectively, are shown.
These may be viewed without downloading. Given the access rights, the buttons
for copying, resetting and deleting of receipts are shown as well. The receipts
will be ordered according to the date of receipting.

In the details, you will find the following information:

- Name & postal adress (may differ from name & residence adress)
- Name & residence adress of donor/ contributor
- Listing of the receipted donations with sum and date
- Type of donation

Further, a button for the creation of new receipts is provided. The donations to
be processed are selected with the setting of date/ time period.

*Delete*: (normally only visible for admins) This button is used for deleting a
receipt. As mentioned above, check on the legal terms before using the button.

*Copy*: This button is used to create a copy of an existing receipt. The copy
comes with a watermark. It will be identical to the original, for the underlying
data is not fetched anew from the database. The date of the copy, of course, is
the actual date of creating the copy. Corresponding download-buttons and details
will be shown for the copies.

*Reset*: (normally only visible for admins) This button is used to reset a
receipt. The receipt remains in place and gets a watermark (the text, again, can
be set in the administration console). The corresponding donations will again be
tagged non-receipted when resetting the receipt. Again, check on the legal terms
before using the button!

Even after receipting a donation, changes or corrections to the donation may
have to be performed. In most of the cases, this is linked to duplicate
contacts, errors in the spelling of the name or in the assignment of contacts to
bank accounts. First, then, the receipt has to be physically withdrawn in order
to meet legal regulations. After having received the receipt, the document may
be reset in CiviCRM. With that action, the tag of the donation reverses from
"receipted" to "complete" and data can be changed.

*Rebook*: The extension comes with the possibility to rebook a donation, that
is, assign the donation to another donor. Donations can only be rebooked as log
as they have not been receipted, as mentioned before. Evidence should be checked
properly before rebooking.

*Search for donation receipts*: Receipts, of course, can be subjected to
(advanced) searches in CiviCRM, data is to be found among the custom fields.
