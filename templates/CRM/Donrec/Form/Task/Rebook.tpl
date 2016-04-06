{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-form-block crm-block crm-contact-task-pdf-form-block">
  {* HEADER *}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {if $totalSelectedContributions gt 1}
      <p>{ts domain="de.systopia.donrec"}Are you sure you want to rebook the selected contributions?{/ts}</p>
      <p>{ts domain="de.systopia.donrec"}Number of selected contributions:{/ts} {$totalSelectedContributions}</p><b/>
      {else}
      <p>{ts domain="de.systopia.donrec"}Are you sure you want to rebook the contribution?{/ts}</p>
      {/if}
  </div>
  
  <p><strong>{ts domain="de.systopia.donrec"}Please enter the target CiviCRM ID?{/ts}</strong></p>
  

  {$form.contactId.label}<br />
  {$form.contactId.html}
  {$form.contributionIds.html}
  <br />

  {* FOOTER *}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
  
{literal}
<script type="text/javascript">
cj(function( ) {

  var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=navigation' h=0 }"{literal};

  cj( '#contactId' ).autocomplete( contactUrl, {
      width: 200,
      selectFirst: true,
      minChars: 1,
      matchContains: true,
      delay: 400,
      max: 1,
      extraParams:{
        fieldName: 'contact_id',
        tableName: 'cc'
      }
  }).result(function(event, data, formatted) {
     cj( '#contactId' ).val(data[1]);
     return false;
  });
});

</script>
{/literal}  