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
		<td class="label">{$form.result_format.label}:</td>
		<td>
			{$form.result_format.html}
		</td>
	</tr>
</table>
<!-- the buttons -->
<div id='donrec_buttons' class="crm-submit-buttons">
  <a class="button" href="{$url_testrun}">
    <span align="right"><div class="icon search-icon"></div>{ts}Test Run{/ts}</span>
  </a>
  <a class="button" href="{$url_run}">
    <span align="right"><div class="icon play-icon"></div>{ts}Issue Donation Receipts{/ts}</span>
  </a>
  <a class="button" href="{$url_abort}">
    <span align="right"><div class="icon back-icon"></div>{ts}Abort{/ts}</span>
  </a>
</div>
{/if}