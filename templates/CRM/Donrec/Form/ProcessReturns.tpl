{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019      SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<h3>{ts domain="de.systopia.donrec"}Process Email Returns{/ts}</h3>

<div class="messages help">
  {ts domain="de.systopia.donrec"}This tool allows you to process bounced or returned (emailed) donation receipts into activities.{/ts}
  {ts domain="de.systopia.donrec"}You should create a new, exclusive email account, and set the return path to that account while sending.{/ts}
  {ts domain="de.systopia.donrec"}To uniquely identify the contact the receipt had been sent to, you can add a code containing the contact ID to the the email template, and provide a pattern below to recognise it.{/ts}
</div>

<div class="crm-section">
  <div class="label">{$form.returns_activity_type_id.label}</div>
  <div class="content">{$form.returns_activity_type_id.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.returns_pattern.label}</div>
  <div class="content">{$form.returns_pattern.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.returns_limit.label}</div>
  <div class="content">{$form.returns_limit.html}</div>
  <div class="clear"></div>
</div>



<h3>{ts domain="de.systopia.donrec"}Account Access{/ts}</h3>

<div class="crm-section">
  <div class="label">{$form.returns_server.label}</div>
  <div class="content">{$form.returns_server.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.returns_user.label}</div>
  <div class="content">{$form.returns_user.html}</div>
  <div class="clear"></div>
</div>

<div class="crm-section">
  <div class="label">{$form.returns_pass.label}</div>
  <div class="content">{$form.returns_pass.html}</div>
  <div class="clear"></div>
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
