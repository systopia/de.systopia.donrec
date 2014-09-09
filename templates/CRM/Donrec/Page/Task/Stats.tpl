<form action="{$formAction}" method="post">
{if $error}
<p style="color: #ff0000;">Error: {$error}</p>
<div id='donrec_buttons' class="crm-submit-buttons">
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
</div>
{else}
<div class="form-item">
<!-- result format radioboxes-->
<table>
	<tr>
		<th style="width: 250px"></th>
		<th></th>
	</tr>
	<tr>
		<td class="label">{ts}Donation receipt type{/ts}:</td>
		<td>
			<input value="1" type="radio" id="donrec_type" name="donrec_type" checked="checked" class="form-radio"/><label for="donrec_type">single</label>&nbsp;<input value="2" type="radio" id="donrec_type" name="donrec_type" class="form-radio" /><label for="donrec_type">multi</label>
		</td>
	</tr>
	<tr>
		<td class="label">{ts}Result formats{/ts}:</td>
		<td>
			<input value="1" type="radio" id="result_type" name="result_type" checked="checked" class="form-radio"/><label for="result_type">DUMMY #1</label>&nbsp;<input value="2" type="radio" id="result_type" name="result_type" class="form-radio" /><label for="result_type">DUMMY #2</label>
		</td>
	</tr>
</table>
</div>
<!-- the buttons -->
<div class="form-item">
  <input name="donrec_testrun" value="{ts}Test run{/ts}" class="form-submit" type="submit">
  <input name="donrec_run" value="{ts}Issue donation receipts{/ts}" class="form-submit" type="submit">
  <input name="donrec_abort" value="{ts}Abort{/ts}" class="form-submit" type="submit">
</div>
{/if}
</form>