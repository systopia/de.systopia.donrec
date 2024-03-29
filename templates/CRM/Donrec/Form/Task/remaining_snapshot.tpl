{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: Thomas Leichtfuss (leichtfuss -at- systopia.de)|
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{if $remaining_snapshot}
<div id="remaining_snapshot" class="messages warning no-popup">
  <span class="msg-title">{ts domain="de.systopia.donrec"}Unfinished run detected{/ts}</span>
  <div class="msg-text">
    {if $statistic}
    {if !$statistic.status}
    <p>{ts domain="de.systopia.donrec"}You have an unfinished run, do you want to continue with it?{/ts}<br />
        {ts domain="de.systopia.donrec"}Otherwise it <b>will be deleted</b>!{/ts}</p>
    {elseif $statistic.status == 'TEST'}
    <p>{ts domain="de.systopia.donrec"}You have an unfinished run, do you want to continue with it?{/ts}<br />
        {ts domain="de.systopia.donrec"}Otherwise it <b>will be deleted</b>!{/ts}</p>
    {elseif $statistic.status == 'DONE'}
    <p>{ts domain="de.systopia.donrec"}An interrupted run has been detected. Do you want to continue with it?{/ts}<br />
        {ts domain="de.systopia.donrec"}Otherwise the selection <b>will be deleted</b>!{/ts}</p>
    <p>{ts domain="de.systopia.donrec"}<b>Caution!</b> Some receipts have already been created. If you do not continue with this run, you should probably delete them manually.{/ts}</p>
    {/if}
      <table id="statistic">
        <caption>{ts domain="de.systopia.donrec"}Statistics{/ts}</caption>
        <tr><td class="statskey">{ts domain="de.systopia.donrec"}contact count{/ts}</td><td class="statsvalue">{$statistic.contact_count}</td></tr>
        <tr><td class="statskey">{ts domain="de.systopia.donrec"}contribution count{/ts}</td><td class="statsvalue">{$statistic.contribution_count}</td></tr>
        <tr><td class="statskey">{ts domain="de.systopia.donrec"}total amount{/ts}</td><td class="statsvalue">{$statistic.total_amount}</td></tr>
        <tr><td class="statskey">{ts domain="de.systopia.donrec"}created on{/ts}</td><td class="statsvalue">{$statistic.creation_date|crmDate:$config->dateformatFull}</td></tr>
      </table>
    {/if}


    <div class="crm-submit-buttons">
      <button class="crm-form-submit default validate crm-button crm-button-type-next crm-button_qf_Create_next" type="submit"
             name="use_remaining_snapshot" value="use_remaining_snapshot"><i aria-hidden="true"  class="crm-i fa-recycle"></i> {ts domain="de.systopia.donrec"}Use remaining snapshot{/ts}</button>
    </div>
  </div>
</div>
{/if}
