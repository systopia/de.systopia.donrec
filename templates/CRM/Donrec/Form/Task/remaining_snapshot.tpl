{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: Thomas Leichtfuss (leichtfuss -at- systopia.de)|
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{if $remaining_snapshot}
<div id="remaining_snapshot" class="messages warning no-popup">
<span class="msg-title">{ts domain="de.systopia.donrec"}Unfinished run detected{/ts}</span>
<span class="msg-text">
{if $statistic}
{if !$statistic.status}
<p>{ts domain="de.systopia.donrec"}You have an unfinished run, do you want to continue with it?{/ts}</br>
    {ts domain="de.systopia.donrec"}Otherwise it <b>will be deleted</b>!{/ts}</p></br>
{elseif $statistic.status == 'TEST'}
<p>{ts domain="de.systopia.donrec"}You have an unfinished run, do you want to continue with it?{/ts}</br>
    {ts domain="de.systopia.donrec"}Otherwise it <b>will be deleted</b>!{/ts}</p></br>
{else if $statistic.status == 'DONE'}
<p>{ts domain="de.systopia.donrec"}An interrupted run has been detected. Do you want to continue with it?{/ts}</br>
    {ts domain="de.systopia.donrec"}Otherwise the selection <b>will be deleted</b>!{/ts}</br></br>
    {ts domain="de.systopia.donrec"}<b>Caution!</b> Some receipts have already been created. If you do not continue with this run, you should probably delete them manually.{/ts}</p></br>
{/if}
  <table id="statistic">
    <caption>{ts domain="de.systopia.donrec"}Statistics{/ts}</caption>
    <tr><td class="statskey">{ts domain="de.systopia.donrec"}contact count{/ts}</td><td class="statsvalue">{$statistic.contact_count}</td></tr>
    <tr><td class="statskey">{ts domain="de.systopia.donrec"}contribution count{/ts}</td><td class="statsvalue">{$statistic.contribution_count}</td></tr>
    <tr><td class="statskey">{ts domain="de.systopia.donrec"}total amount{/ts}</td><td class="statsvalue">{$statistic.total_amount}</td></tr>
    <tr><td class="statskey">{ts domain="de.systopia.donrec"}created on{/ts}</td><td class="statsvalue">{$statistic.creation_date|crmDate:$config->dateformatFull}</td></tr>
  </table>
{/if}

<input class="form-submit" type="submit" name='use_remaining_snapshot' value='{ts domain="de.systopia.donrec"}Use remaining snapshot{/ts}'/>

</div>
{/if}
