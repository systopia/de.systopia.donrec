{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

<div class="crm-block crm-form-block">
	<div>
		<h3>{ts}Contribution types{/ts}</h3>
		<input value="all" type="checkbox" id="financial_types_all" name="financial_types_all" {if $financialTypes[0]}checked="checked{/if}" {if count($financialTypes) eq 0}disabled="disabled"{/if} class="form-checkbox"/><label for="financial_types"> {ts}All deductible contribution types{/ts} <a onclick='CRM.help("{ts}Draft Text{/ts}", {literal}{"id":"id-contribution-types","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></label>&nbsp;
		<div id="advContribTypeList" {if $financialTypes[0] eq 1}hidden{/if}>
  		{foreach from=$financialTypes item=item name=fitems}
  			{if !$smarty.foreach.fitems.first}
			<input value="{$item[0]}" type="checkbox" id="financial_types{$smarty.foreach.fitems.iteration}" name="financial_types{$smarty.foreach.fitems.iteration}" {if $item[3]}checked="checked"{/if} class="form-checkbox"/><label for="financial_types"> {$item[1]} {if $item[2]}({ts}deductible{/ts}){/if}</label>&nbsp;<br/>
			{/if}
		{/foreach}
		</div>
  </div>
  <div>
  	<h3>Text</h3>
  	<div>
  		<table>
  			<tr>
  				<td class="label">{$form.draft_text.label} <a onclick='CRM.help("{ts}Draft Text{/ts}", {literal}{"id":"id-draft-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></td>
  				<td>{$form.draft_text.html}</td>
  			</tr>
  			<tr>
  				<td class="label">{$form.copy_text.label} <a onclick='CRM.help("{ts}Copy Text{/ts}", {literal}{"id":"id-copy-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></td>
  				<td>{$form.copy_text.html}</td>
  			</tr>
  		</table>
  	</div>
  </div>
  <div>
  	<h3>PDF</h3>
  	<div>
  		<div>
					<table>
						<tr>
							<td class="label">{$form.pdfinfo_path.label} <a onclick='CRM.help("{ts}pdfinfo binary path{/ts}", {literal}{"id":"id-draft-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></td>
							<td>{$form.pdfinfo_path.html}</td>
						</tr>
					</table>
				</div>
		</div>
  </div>
  <div>
  	<h3>Donation receipts</h3>
  	<div>
  		<table>
  			<tr>
  				<td>{$form.packet_size.label} <a onclick='CRM.help("{ts}Draft Text{/ts}", {literal}{"id":"id-packet-size","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts}Help{/ts}" class="helpicon">&nbsp;</a></td>
  				<td>{$form.packet_size.html}</td>
  			</tr>
  		</table>
  	</div>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{literal}
<script type="text/javascript">
	function syncContribTypeList() {
		if(cj("#financial_types_all").is(':checked')) {
			cj('#advContribTypeList').hide();
			//cj('#advContribTypeList :checkbox').attr('checked', false);
		}else{
			cj('#advContribTypeList').show();
		}
	}

	cj(function() {
		cj("#financial_types_all").on("change", syncContribTypeList);
		syncContribTypeList();
	});
</script>
{/literal}
