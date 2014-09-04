<table>
	<tr>
		<td class="label">{$form.donrec_type.label}:</td>
		<td>
			{$form.donrec_type.html}
		</td>
	</tr>
	<tr>
		<td class="label">{ts}Contribution horizon{/ts}:</td>
		<td>
			{$form.donrec_contribution_horizon_from.label} {include file="CRM/common/jcalendar.tpl" elementName='donrec_contribution_horizon_from'} {$form.donrec_contribution_horizon_to.label} {include file="CRM/common/jcalendar.tpl" elementName='donrec_contribution_horizon_to'}
		</td>
	</tr>
	<tr>
		<td class="label">{$form.donrec_contribution_amount_low.label}:</td>
		<td>
			{$form.donrec_contribution_amount_low.html} EUR
		</td>
	</tr>
	<tr>
		<td class="label">{$form.result_format.label}:</td>
		<td>
			{$form.result_format.html}
		</td>
	</tr>
	<tr>
		<td></td>
		<td>
			{$form.is_test.html} {$form.is_test.label}
		</td>
	</tr>
</table>