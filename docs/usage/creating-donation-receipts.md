# Creating donation receipts

The generation is done on the results of a search. One may use the searches
already provided by CiviCRM, under the condition that it is a search leading to
contacts as a result, for the receipts will be assigned to the donating contact
in the process. Custom and advanced searches as well as aggregate tools allow
the user to preprocess the contacts in any convenient way. Therefore one will
not find further searches or selectors built into the extension. On the results
of the performed search, a further selection ist done by the software in order
to distinguish between deductable and non-deductable amounts according to the
following:

- Only contacts with unreceipted donations (in the chosen period) will be
  considered
- For receipting, donations will have to have the status "completed" and to be
  of a financial type where the "deductible" option is set to "yes"

The extension can securely handle large data sets (i.e. Contacts). The processed
data will be transferred into a snapshot and secured against modification in the
course of the receipting action. When receipting a donation, it is automatically
assigned a "receipted"-status. With that status, a donation must not and cannot
be altered or receipted again, meaning that it is protected against changes in
order to assure consistency of data. With the extension, one can also reset,
delete and copy receipts as well as transfer donations to another contact. For
these procedures, a deliberate set of user permissions is provided.

## Parameters

For receipting a donation, the following parameters can be chosen and set:

- *Type of donation receipt*: bulk receipt / single receipt<br />  
  The generation of receipts is done using a message template, stored in the
  corresponding templates location of CiviCRM, which is a HTML template
  optimised for pdf generation. Please note that the existing templates are
  user-specific and designed according to german tax requirements, they will
  have to be modified for use with other organisations and in other countries.
  <br />
  When chosing the type of receipt, one has to bear in mind that when
  "single receipt" is chosen, only single receipts are rendered, no matter how
  many donations exist in the current snapshot. In contrast, when "bulk receipt"
  is chosen, the template switches to single receipt when there is only one
  deductable donation to be receipted. This all is done with one single
  template. The template can be modified and is found in
  administration > communication > message templates > system workflow message.
  Some expertise in HTML is required.

- *Time period of the receipt*: this year / last year / custom range of dates  
    - "this year" renders receipts for the actual year, starting with 01.
      January and including the actual day of receipting.
  
    - "last year" renders receipts for the whole of the calendary year that is
      the last before the actual year.

    - "custom range of dates" behaves according to other searches using the
      parameter date/ range of dates. As it is a custom search, the user may
      choose a range of dates appropriate for the task at hand. On the other
      hand, care has to be taken in order to produce meaningful results. Bulk
      receipts may be rendered over a period of several years. Whether this may
      be acceptable under your tax laws ist to be assured by the user.

  **Caution**: There is no link between the date of an official permit to
  receipt donations and the date of the donation. You may receipt donations that
  have been received before the date of the permit to do so. It is under the
  responsibility of the user to assure compliance to local law and rules!

- *Minimum total (currency) necessary for rendering a receipt*:<br />
  There is no such threshold to be set within the extension. All selections of
  data may be performed by the search tools already provided by CiviCRM. For
  setting am minimum total, the search tool "contributors by aggregate totals"
  may be used.

- *Format to be rendered*: individual pdf file(s) / csv file(s) /
  pdf files grouped according to number of pages<br />
  The receipts may be rendered as pdf files. For handling a vast number of
  files, archives are generated. Under Linux, the suitable tools are provided
  with the most of distributions. Under MS Windows, the use of "7zip" is
  recommended. You may render a pdf, that will be stored with the donating
  contact, or you may regenerate the receipt for single use in order to save
  disk space. Please note that the rendering process can only make use of the
  actual template. When not storing receipts as pdf files, they will change
  according to the template in use.

  For printing in a lettershop, you may use the csv format. Having pdfinfo
  installed on the server, grouping according to the number of pages becomes
  possible.

  In all cases, a receipted donation cannot be altered or receipted again as
  long as the receipt is active.

  *Please note*: In the process of setting the rendering options, you may choose
  "don't generate files". This setting is overruled by the global setting on the
  administration console "store original *.pdf files". Check there to see
  whether settings are consistent. When choosing the options
  "don't generate files" and "store original *.pdf files", the pdf is rendered
  and saved with the contact but not displayed automatically.

  The pdf files may be grouped according to the number of pages when pdfinfo is
  installed on the server. This is a useful option when printing on front and
  back of the pages, especially when printing merged pdf's. Merging pdf files is
  not included in the described extension. Free software such as pdfsam for
  Linux is available for batching and merging pdf files.

  When rendering a csv file for use in a lettershop, the default coding will be
  UTF-8.

## Generating donation receipts

The procedure to be followed for the generation of donation receipts is
described in the following:

In the first place, one has to choose from the basic settings in the
administration console what is suitable for the task at hand and save these
settings. In the Drupal access control one has to grant to certain roles (user
groups) the appropriate rights i. e. create, view, reset or delete donation
receipts. This, of course, has only to be done once when setting up the
extension.

Second, a search is performed. In most of the cases, this will be a custom
search or aggregate totals search. Of cause, also smart groups can be used. The
receipting of donations always starts with a contact or a group of contacts as a
search result. On the search result, the contacts for whom a receipt has to be
created are chosen (for a suitable search, the option is "all") and the action
"create donation receipt(s)" is triggered. If the option does not display, check
the Drupal access control. From CiviCRM 4.6.9 upwards there is no "start"
button, and the highlighted action starts immediately. As described before, time
period and type of receipt have to be set. A message will be shown, describing
the action to follow on the search results, i.e. number of chosen results,
number of valid results and number of donations to be receipted. Now, a testing
action and a receipting action are offered. In order to avoid the breakdown of
the proper receipting when doing so on a great number of contacts, the testing
is highly recommended. The procedure is the same, only will it not tag donations
as receipted and produces receipts with a watermark, the text for which is set
in the administration console. If the testing does not break down, perform the
proper receipting action. Do NOT close the active window or reload it, for the
receipting is triggered by the workstation, not by the server! Normally, of
course, the action can be restarted after it has been interrupted, as long as
the snapshot is not deleted. Still, making sure that everything remains
consistent is difficult and should thus be avoided.

When receipting, always bear in mind the following:

- The process of receipting, as already mentioned, ist triggered by the active
window on the workstation. Shutting down the workstation, closing or reloading
of the window will result in a disruption of the process. Do NOT do that. The
process should run at a rate of about 2000 PDF files per hour.

- A snapshot of the data is used for receipting. The snapshot is valid for 24
hours. As long as the snapshot is valid, an interrupted process can be
continued. The snapshot belongs to the user that created it. With a different
login, it will not be possible to restart an interrupted process with that
snapshot!

*Test run*: Tapping this button will result in the receipting process, except
that no donation will be tagged as receipted and the resulting pdf will have a
watermark and ist NOT subsequently stored in the contact information. The
testing is in place as a possibility to check whether all fields have the proper
structure and content. If the process runs smoothly, then check on some of the
pdf whether the template in use displays all the correct fields an sums, then
acknowledge with the appropriate button. The window from which the run was
started is displayed again.

*Issue donation receipt(s)*: Tapping this button will result in the receipting
of the chosen contacts and their valid donations, respectively. The processed
donations will be tagged as receipted and secured against modifications. Should
there arise necessity to change amount or date of the donation, rebook or cancel
it, you will first have to delete the receipt (the tag "receipted" is
automatically removed from the donation, then). Great care has to be taken when
doing so with respect to tax laws, document integrity and other judicial issues.
It is strongly recommended to consult a lawyer and to reserve the right to reset
and delete receipts for admins. As a software in international use, CiviCRM or
its extensions cannot be strictly consistent with regulations. What can be done
might not be legal to do, so check first.

Note: The tag "receipted" can not be removed from a donation by direct manual
action.
