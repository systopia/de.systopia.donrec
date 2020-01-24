{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}


<table class="form-layout crm-donrec-profile-form-block-variables-table">
  <thead>
  <tr>
    <th>{ts domain="de.systopia.donrec"}Variable name{/ts}</th>
    <th>{ts domain="de.systopia.donrec"}Variable value{/ts}</th>
  </tr>
  </thead>
  <tbody>
  {foreach from=$variable_elements item=variable_element key=variable_count}
      {capture assign="variable_name_field"}{$variable_element}--name{/capture}
      {capture assign="variable_value_field"}{$variable_element}--value{/capture}
    <tr class="crm-donrec-profile-form-block-variable variable-{$variable_count}">
      <td>{$form.$variable_name_field.html}</td>
      <td>{$form.$variable_value_field.html}</td>
    </tr>
  {/foreach}
  </tbody>
</table>
