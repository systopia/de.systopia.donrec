{if $error}
<p style="color: #ff0000;">Error: {$error}</p>
<div id='donrec_buttons' class="crm-submit-buttons">
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
</div>
{else}
<!-- result format radioboxes-->
<table>
	<tr>
		<th style="width: 250px"></th>
		<th></th>
	</tr>
	<tr>
		<td class="label">{$form.donrec_type.label}:</td>
		<td>
			{$form.donrec_type.html}
		</td>
	</tr>
	<tr>
		<td class="label">{$form.result_format.label}:</td>
		<td>
			{$form.result_format.html}
		</td>
	</tr>
</table>
<!-- the buttons -->
<div class="crm-submit-buttons">{$form.donrec_testrun.html}{$form.donrec_run.html}{$form.donrec_abort.html}</div>
{/if}