{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N. Bochan / B. Endres (endres -at- systopia.de)|
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-form-block">
  <h3>{ts domain="de.systopia.donrec"}Filter contributions by receive date{/ts}</h3>
  <table class="form-layout">
    <tr>
      <td class="label">
          {$form.profile.label}
      </td>
      <td>
          {$form.profile.html}
      </td>
    </tr>
    <tr>
      <td class="label">
        {$form.donrec_contribution_horizon_relative.label}
      </td>
      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName="donrec_contribution_horizon" from="_from" to="_to" colspan=1 hideRelativeLabel=TRUE}
    </tr>
    <tr>
      <td class="label">
          {$form.donrec_contribution_currency.label}
      </td>
      <td>
          {$form.donrec_contribution_currency.html}
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
      </td>
    </tr>
  </table>
</div>
