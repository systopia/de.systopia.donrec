<div class="action-link">
	<a accesskey="N" href="#" class="button"><span><div class="icon add-icon"></div>{ts}Create new donation receipt{/ts}</span></a>
</div>
<div class="donrec-stats-block">
	<table>
		{foreach from=$display_receipts item=receipt name=receipt_items}
		<tr class="{if $smarty.foreach.receipt_items.index % 2 == 0}even{else}odd{/if}">
			<td>
				<div class="donrec-stats">
					<ul>
						<li><u><b>{ts}{$receipt.type} receipt{/ts}</b></u></li>
						<li>{ts}Status{/ts}: <b>{$receipt.status}</b></li>
						<li>{ts}Creation date{/ts}: {$receipt.issued_on|date_format:"%d.%m.%Y"}</li>
						<li>{ts}Receipt horizon{/ts}: {$receipt.date_from|date_format:"%d.%m.%Y"} {if $receipt.date_to neq $receipt.date_from} - {$receipt.date_to|date_format:"%d.%m.%Y"}{/if}</li>
						<li>{ts}Total amount{/ts}: {$receipt.total_amount} {$receipt.currency}</li>
						<li><a href="#"><span><div class="icon details-icon"></div>{ts}Details{/ts}</span></a></li>
					</ul>
				</div>
			</td>
			<td><a href="#" class="button"><span><div class="icon add-icon"></div>{ts}Create copy{/ts}</span></a><a href="#" class="button"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a><a href="#" class="button"><span><div class="icon back-icon"></div>{ts}Revert{/ts}</span></a>
			</td>
		</tr>
		{/foreach}
	</table>
</div>

{literal}
<style type="text/css">
	.action-link .button {
		margin-bottom: 0;
	}
	.donrec-stats-block table {
		border-collapse: collapse;
	}
	.donrec-stats-block tr td, tr th {
		border: none;
	}
	.donrec-stats ul {
		list-style-type: none;
	}
</style>

<script type="text/javascript">
	
</script>
{/literal}