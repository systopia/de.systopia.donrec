{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-block crm-content-block crm-donrec-content-block">

  <div class="crm-submit-buttons">
    <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=create"}" title="{ts domain="de.systopia.donrec"}New profile{/ts}" class="button">
      <span><i class="crm-i fa-plus-circle"></i> {ts domain="de.systopia.donrec"}New profile{/ts}</span>
    </a>
  </div>

  <div class="help">
    <p>{ts domain="de.systopia.donrec"}Profiles allow you to have different settings for different scenarios. But if you only have one, just work with the default profile.{/ts}</p>
  </div>

  {if !empty($profiles)}
    <table>
      <thead>
      <tr>
        <th>{ts domain="de.systopia.donrec"}Profile name{/ts}</th>
        <th>{ts domain="de.systopia.donrec"}Default profile{/ts}</th>
        <th>{ts domain="de.systopia.donrec"}Active{/ts}</th>
        <th>{ts domain="de.systopia.donrec"}Locked{/ts}</th>
        <th>{ts domain="de.systopia.donrec"}Operations{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$profiles item=profile}
        {assign var="profile_id" value=$profile.id}
        <tr{if !$profile.is_active} class="disabled"{/if}>
          <td>{$profile.name}</td>
          <td>{if $profile.is_default}{ts domain="de.systopia.donrec"}Yes{/ts}{else}{ts domain="de.systopia.donrec"}No{/ts}{/if}</td>
          <td>{if $profile.is_active}{ts domain="de.systopia.donrec"}Yes{/ts}{else}{ts domain="de.systopia.donrec"}No{/ts}{/if}</td>
          <td>{if $profile.is_locked}{ts domain="de.systopia.donrec"}Yes{/ts}{else}{ts domain="de.systopia.donrec"}No{/ts}{/if}</td>
          <td>
            <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=edit&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Edit profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Edit{/ts}</a>
            <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=copy&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Copy profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Copy{/ts}</a>
            {if !$profile.is_active}
              <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=activate&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Activate profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Activate{/ts}</a>
            {else}
              <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=deactivate&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Deactivate profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Deactivate{/ts}</a>
            {/if}
            {if !$profile.is_default}
              <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=default&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Set profile %1 as default{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Set default{/ts}</a>
            {/if}
            <a href="{crmURL p="civicrm/admin/setting/donrec/profile" q="op=delete&id=$profile_id"}" title="{ts domain="de.systopia.donrec" 1=$profile.name}Delete profile %1{/ts}" class="action-item crm-hover-button">{ts domain="de.systopia.donrec"}Delete{/ts}</a>
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  {/if}

</div>
