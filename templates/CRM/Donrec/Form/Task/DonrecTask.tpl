{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{include file="CRM/Donrec/Form/Task/remaining_snapshot.tpl"}

<table>
  <tr>
    <h3>{ts domain="de.systopia.donrec"}Filter contributions by receive date{/ts}</h3>
  </tr>
  <tr>
    <td class="label">
      {ts domain="de.systopia.donrec"}Select time span{/ts}:
    </td>
    <td>
      {$form.time_period.html}
    </td>
  </tr>
  <tr>
    <td>
    </td>
    <td>
      <div id="custom_period">
        {$form.donrec_contribution_horizon_from.label}
        {include file="CRM/common/jcalendar.tpl" elementName=donrec_contribution_horizon_from}
        <br/>
        {$form.donrec_contribution_horizon_to.label}
        {include file="CRM/common/jcalendar.tpl" elementName=donrec_contribution_horizon_to}
      </div>
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

{literal}
<script type="text/javascript">
  cj(document).ready(function() {
    var time_period = cj('#time_period');
    var custom_period = cj('#custom_period');
    var current_year = (new Date).getFullYear();
    var current_month = (new Date).getMonth();
    var current_day = (new Date).getDate();
    var from = cj('#donrec_contribution_horizon_from');
    var to = cj('#donrec_contribution_horizon_to');
    var from_display = cj("[id^='donrec_contribution_horizon_from_display']");
    var to_display = cj("[id^='donrec_contribution_horizon_to_display']");

    var set_period = function () {
      switch (time_period.val()) {
      case "customized_period":
        from.val("");
        from_display.val("");
        to.val("");
        to_display.val("");
        custom_period.show();
        break;
      case "current_year":
        console.log('this_year');
        from_display.datepicker('setDate', new Date(current_year, 0, 1));
        to_display.datepicker('setDate', new Date(current_year, current_month, current_day));
        custom_period.hide();
        break;
      case "last_year":
        console.log('last_year');
        from_display.datepicker('setDate', new Date(current_year-1, 0, 1));
        to_display.datepicker('setDate', new Date(current_year-1, 11, 31));
        custom_period.hide();
        break;
      }
    }

    //evaluate initial value
    set_period();

    //on change
    time_period.change(set_period);
  });
</script>
{/literal}
