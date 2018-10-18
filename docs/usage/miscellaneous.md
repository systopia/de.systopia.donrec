# Miscellaneous

Some details should be considered seriously, for they affect security and
integrity of the data. With the extension, further functions such as rebooking,
are provided and therefore a new in CiviCRM. Please note again that due
diligence was applied to the legal terms of the matter, but does and can only
refer to german legislation. The responsibility for compliance rests with the
user.

## Receipted donations

In the donation data, no manipulations or changes will be allowed after
receipting. The most significant fields of these are:

- status
- financial type
- amount

It is these fields that are also blocked against changes when an active snapshot
has been taken. This assures consistency between donation data details and the
(valid) receipts. When dealing with a huge amount of contacts or when using
CiviCRM with multiple users, that is of essence.

## Access control

In the Drupal access control, the following permissions may be granted to one or
various roles:

- View receipts and make copies thereof
- Create and reset receipts, editing 
- Delete receipts

## Template

The template used for generating receipts as pdf-files is done in HTML. It will
be converted to PDF via the program selected in the CiviCRM settings (either
dompdf or wkhtmltopdf - the latter one is recommended). Within the template, you
can implement program logic using "Smarty".

## Tokens

Tokens are made use of, still every organisation will have to do some editing in
the template before use. It is highly recommended to draw and store a copy of
the template before applying changes. Please note that the process "HTML to pdf"
ist not straightforward, setting up a new or modified template will take try and
error.

The following tokens are supported within the extension: 

*Remark: What is referred here as tokens, in fact are are Smarty variables.
Therefore, they cannot be selected from the Token Menu of the editor, but have
to be entered as descripted below.*

| Token                                                 | Type                                      | Comment                                                                  |
|-------------------------------------------------------|-------------------------------------------|--------------------------------------------------------------------------|
| <nobr>`{$id}`</nobr>                                  | Integer                                   | ID of the donation receipt                                               |
| <nobr>`{$status}`</nobr>                              | ORIGINAL, COPY, WITHDRAWN, WITHDRAWN_COPY | Status of donation receipt                                               |
| <nobr>`{$type}`</nobr>                                | {BULK, SINGLE}                            | Buld or single donation receipt                                          |
| <nobr>`{$issued_by}`</nobr>                           | Integer                                   | Contakt-ID of the admin user who issued the receipt                      |
| <nobr>`{$issued_by_display_name}`</nobr>              | String                                    | Name of the issuer                                                       |
| <nobr>`{$issued_on}`</nobr>                           | ISO-Datum                                 | Date of issueing the receipt                                             |
| <nobr>`{$total_amount}`</nobr>                        | Dezimalzahl                               | Total amount                                                             |
| <nobr>`{$total}`</nobr>                               | Dezimalzahl                               | equal to <nobr>`{$total_amount}`</nobr>                                  |
| <nobr>`{$totaltext}`</nobr>                           | String                                    | <nobr>`{$total}`</nobr> als german text string                           |
| <nobr>`{$totalmoney}`</nobr>                          | String                                    | <nobr>`{$total}`</nobr> in CiviCRM's currency format                     |
| <nobr>`{$today}`</nobr>                               | ISO-Datum                                 | egual to <nobr>`{$issued_on}`</nobr>                                     |
| <nobr>`{$non_deductible_amount}`</nobr>               | Dezimalzahl                               | Total of non-deductible amount                                           |
| <nobr>`{$currency}`</nobr>                            | drei Buchstaben                           | Currently, always 'EUR'                                                  |
| <nobr>`{$date_from}`</nobr>                           | ISO-Datum                                 | Start of the period for the receipts                                     |
| <nobr>`{$date_to}`</nobr>                             | ISO-Datum                                 | End of the period for the receipts                                       |
| <nobr>`{$original_file}`</nobr>                       | ID der Originaldatei                      | Set only, if there is an original donation receipt, and option is active |
| <nobr>`{$lines}`</nobr>                               | Array mit Zuwendungen                     | see below                                                                |
| <nobr>`{$items}`</nobr>                               | Array mit Zuwendungen                     | as<nobr>`{$lines}`</nobr>, only available for bulk receipts              |
| <nobr>`{$contributor.id}`</nobr>                      | Integer                                   | Contakt ID of contributor                                                |
| <nobr>`{$contributor.contact_type}`</nobr>            | String                                    | Contact type of contributor                                              |
| <nobr>`{$contributor.display_name}`</nobr>            | String                                    | Display name of contributor                                              |
| <nobr>`{$contributor.addressee_display}`</nobr>       | String                                    | Adressee of contributor                                                  |
| <nobr>`{$contributor.street_address}`</nobr>          | String                                    | Street address of contributor                                            |
| <nobr>`{$contributor.postal_greeting_display}`</nobr> | String                                    | Postal greeting of contributor                                           |
| <nobr>`{$contributor.email_greeting_display}`</nobr>  | String                                    | Email greeting of contributor                                            |
| <nobr>`{$contributor.gender}`</nobr>                  | String                                    | Gender of contributor                                                    |
| <nobr>`{$contributor.prefix}`</nobr>                  | String                                    | Prefix of contributor                                                    |
| <nobr>`{$contributor.supplemental_address_1}`</nobr>  | String                                    | Supplemental address 1 of contributor                                    |
| <nobr>`{$contributor.supplemental_address_2}`</nobr>  | String                                    | Supplemental address 1 of contributor                                    |
| <nobr>`{$contributor.postal_code}`</nobr>             | String                                    | Postals code of contributor                                              |
| <nobr>`{$contributor.city}`</nobr>                    | String                                    | City of contributor                                                      |
| <nobr>`{$contributor.country}`</nobr>                 | String                                    | Country of contributor                                                   |
| <nobr>`{$addressee.display_name}`</nobr>              | String                                    | Name of the person, who receives the donation receipt ("addressee")      |
| <nobr>`{$addressee.addressee_display}`</nobr>         | String                                    | see above                                                                |
| <nobr>`{$addressee.street_address}`</nobr>            | String                                    | see above                                                                |
| <nobr>`{$addressee.supplemental_address_1}`</nobr>    | String                                    | see above                                                                |
| <nobr>`{$addressee.supplemental_address_2}`</nobr>    | String                                    | see above                                                                |
| <nobr>`{$addressee.postal_code}`</nobr>               | String                                    | see above                                                                |
| <nobr>`{$addressee.city}`</nobr>                      | String                                    | see above                                                                |
| <nobr>`{$addressee.country}`</nobr>                   | String                                    | see above                                                                |
| <nobr>`{$organisation.display_name}`</nobr>           | String                                    | Name of default organisation                                             |
| <nobr>`{$organisation.addressee_display}`</nobr>      | String                                    | Addresse of default organisation                                         |
| <nobr>`{$organisation.street_address}`</nobr>         | String                                    | see above                                                                |
| <nobr>`{$organisation.supplemental_address_1}`</nobr> | String                                    | see above                                                                |
| <nobr>`{$organisation.supplemental_address_2}`</nobr> | String                                    | see above                                                                |
| <nobr>`{$organisation.postal_code}`</nobr>            | String                                    | see above                                                                |
| <nobr>`{$organisation.city}`</nobr>                   | String                                    | see above                                                                |
| <nobr>`{$organisation.country}`</nobr>                | String                                    | see above                                                                |

To create a list of the contributions within a bulk donation receipt, use the
following code example:

```SMARTY
{foreach from=$items item=item}
  <p>{$item.receive_date|date_format:"%d.%m.%Y"} | {$item.financial_type} | {$item.total_amount|crmMoney:EUR}</p>
{/foreach}
```

Within this loop, the follwing tokens are available:

| Token                                        | Type        | Comment                                   |
|----------------------------------------------|-------------|-------------------------------------------|
| <nobr>`{$item.receive_date}`</nobr>          | ISO Datum   | Receive date of contribution              |
| <nobr>`{$item.contribution_id}`</nobr>       | Integer     | ID of contribution                        |
| <nobr>`{$item.total_amount}`</nobr>          | Dezimalzahl | Total amount of contribution              |
| <nobr>`{$item.non_deductible_amount}`</nobr> | Dezimalzahl | Non deductible amount of the contribution |
| <nobr>`{$item.financial_type_id}`</nobr>     | Integer     | Financial type ID                         |
| <nobr>`{$item.financial_type}`</nobr>        | String      | Name of financial type                    |
