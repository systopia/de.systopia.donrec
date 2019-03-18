{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="messages help">
    {ts domain="de.systopia.donrec"}This tool allows you to process bounced or returned (emailed) donation receipts into activities.{/ts}
    {ts domain="de.systopia.donrec"}You should create a new, exclusive email account, and set the return path to that account while sending.{/ts}
    {ts domain="de.systopia.donrec"}To uniquely identify the contact the receipt had been sent to, you can add a code containing the contact ID to the the email template, and provide a pattern below to recognise it.{/ts}
</div>

{$form.parameter_hash.html}

<div class="crm-section">
    <div class="label">{$form.from_date.label}</div>
    <div class="content">{$form.from_date.html}</div>
    <div class="clear"></div>
</div>

<div class="crm-section">
    <div class="label">{$form.to_date.label}</div>
    <div class="content">{$form.to_date.html}</div>
    <div class="clear"></div>
</div>

{if $duplicate_warning}
<div>{$duplicate_warning}</div>
{/if}

{* FOOTER *}
<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
