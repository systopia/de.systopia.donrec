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
      <p>{ts domain="de.systopia.donrec"}Number of selected contributions:{/ts} {$totalSelectedContributions}</p>
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
CRM.$(function($) {
  $('#contactId').autocomplete({
    source: function(request, response) {
      CRM.api4('Contact', 'get', {
        select: ['id', 'display_name', 'email_primary.email'],
        where: [['sort_name', 'LIKE', '%' + request.term + '%'], ['is_deleted', '=', false]],
        limit: 15
      }).then(function(contacts) {
        response(contacts.map(function(c) {
          var label = c.display_name + (c['email_primary.email'] ? ' (' + c['email_primary.email'] + ')' : '') + ' [id: ' + c.id + ']';
          return {
            label: label,
            value: c.id
          };
        }));
      });
    },
    minLength: 1,
    delay: 300,
    select: function(event, ui) {
      cj('#contactId').val(ui.item.value);
      return false;
    }
  });
});
</script>
{/literal}
