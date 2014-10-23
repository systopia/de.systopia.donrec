{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

{include file="CRM/Donrec/Form/Task/remaining_snapshot.tpl"}

<table>
	<tr>
		<td class="label">
      {ts}Select time span{/ts}:
		</td>
		<td>
		  {$form.time_period.html}
		</td>
	</tr>
	<tr>
	  <td>
	  </td>
		<td>
		  <span id="custom_period">
			  {$form.donrec_contribution_horizon_from.label} {include file="CRM/common/jcalendar.tpl" elementName='donrec_contribution_horizon_from'} {$form.donrec_contribution_horizon_to.label} {include file="CRM/common/jcalendar.tpl" elementName='donrec_contribution_horizon_to'}
			</span>
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
  (function(cj) {
    var time_period = cj('#time_period');
    var custom_period = cj('#custom_period');
    var current_year = (new Date).getFullYear();
    var from = cj('#donrec_contribution_horizon_from');
    var to = cj('#donrec_contribution_horizon_to');
    var from_display = cj('#donrec_contribution_horizon_from_display');
    var to_display = cj('#donrec_contribution_horizon_to_display');

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
        custom_period.hide();
        from.val(new Date(current_year, 1, 1));
        to.val(new Date(current_year, 12, 31));
        to_display.val("");
        break;
      case "last_year":
        custom_period.hide();
        from.val(new Date(current_year - 1, 1, 1));
        to.val(new Date(current_year - 1, 12, 31));
        break;
      }
    }

    //evaluate initial value
    set_period();

    //on change
    time_period.change(set_period);
  })(cj);
</script>
{/literal}
