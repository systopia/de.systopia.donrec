{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-block crm-form-block crm-donrec-profile-form-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {if $action eq 8}
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
          {ts domain="de.systopia.donrec"}WARNING: Deleting this option will result in the loss of Donation Receipts profile data.{/ts} {ts domain="de.systopia.donrec"}Do you want to continue?{/ts}
      </div>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
    {else}
      <table class="form-layout-compressed">

        <tr class="crm-donrec-profile-form-block-name">
          <td class="label">{$form.name.label}</td>
          <td>{$form.name.html}</td>
        </tr>
        <tr>
          <td class="label">&nbsp;</td>
          <td class="description">{ts domain="de.systopia.donrec"}Name of this profile.{/ts}</td>
        </tr>

      </table>
      <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    {/if}
</div>
