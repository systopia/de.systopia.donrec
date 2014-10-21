{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: Thomas Leichtfuss (leichtfuss -at- systopia.de)|
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

{if $remaining_snapshot}
<div id="remaining_snapshot" style="background-color:red">
{if $statistic}
{if !$statistic.status}
<p><b>{ts}You have still an unprocessed selection. Do you want to use it?{/ts}</br>
  {ts}Otherwise it will be removed!{/ts}</b></p></br>
{elseif $statistic.status == 'TEST'}
<p><b>{ts}You have still an unfinished test-run for receipt-creation. Do you want to continue?{/ts}</br>
  {ts}Otherwise the selection will be removed!{/ts}</b></p></br>
{elseif $statistic.status == 'DONE'}
<p><b>{ts}You have still an unfinished run for receipts-creation. Do you want to continue?{/ts}</br>
  {ts}Otherwise the selection will be removed!{/ts}</br>
  {ts}Mind that receipts were already created. It's up to you to delete them so.{/ts}</b></p></br>
{/if}
  <table id="statistic">
    <caption>{ts}Statistic{/ts}</caption>
    <tr><td class="statskey">{ts}count of contacts{/ts}</td><td class="statsvalue">{$statistic.contact_count}</td></tr>
    <tr><td class="statskey">{ts}count of contributions{/ts}</td><td class="statsvalue">{$statistic.contribution_count}</td></tr>
    <tr><td class="statskey">{ts}total amount{/ts}</td><td class="statsvalue">{$statistic.total_amount}</td></tr>
    <tr><td class="statskey">{ts}created at{/ts}</td><td class="statsvalue">{$statistic.creation_date}</td></tr>
  </table>
{/if}

<input class="form-submit" type="submit" name='use_remaining_snapshot' value='Use remaining snapshot'/>

</div>
{/if}
