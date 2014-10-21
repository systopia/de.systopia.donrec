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
<p><b>{ts}You have an unfinished run, do you want to continue with it?{/ts}</br>
    {ts}Otherwise it will be deleted!{/ts}</b></p></br>
{elseif $statistic.status == 'TEST'}
<p><b>{ts}You have an unfinished run, do you want to continue with it?{/ts}</br>
    {ts}Otherwise it will be deleted!{/ts}</b></p></br>
{elseif $statistic.status == 'DONE'}
<p><b>{ts}Interrupted run detected, do you want to continue with it?{/ts}</br>
    {ts}Otherwise the selection will be deleted!{/ts}</br>
    {ts}<b>Caution!</b> Some receipts have already been created. If you do not continue with this run, you should probably delete them manually.{/ts}</b></p></br>
{/if}
  <table id="statistic">
    <caption>{ts}Statistics{/ts}</caption>
    <tr><td class="statskey">{ts}contact count{/ts}</td><td class="statsvalue">{$statistic.contact_count}</td></tr>
    <tr><td class="statskey">{ts}contribution count{/ts}</td><td class="statsvalue">{$statistic.contribution_count}</td></tr>
    <tr><td class="statskey">{ts}total amount{/ts}</td><td class="statsvalue">{$statistic.total_amount}</td></tr>
    <tr><td class="statskey">{ts}created on{/ts}</td><td class="statsvalue">{$statistic.creation_date}</td></tr>
  </table>
{/if}

<input class="form-submit" type="submit" name='use_remaining_snapshot' value='Use remaining snapshot'/>

</div>
{/if}
