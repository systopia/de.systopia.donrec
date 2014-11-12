{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

{if $statistic}
  <div style="max-width:960px;">
        <h2>{ts}Statistics{/ts}</h2>

        <div class="crm-summary-row">
          <div class="crm-label">{ts}selected contacts{/ts}</div>
          <div class="crm-content">{$statistic.requested_contacts}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts}contact count{/ts}</div>
          <div class="crm-content">{$statistic.contact_count}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts}contribution count{/ts}</div>
          <div class="crm-content">{$statistic.contribution_count}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts}total amount{/ts}</div>
          <div class="crm-content">{$statistic.total_amount|crmMoney:EUR}</div>
        </div>
  </div>
{/if}

<form action="{$formAction}" method="post">
{if $error}
<div id="error-block" style="background-color: #FF6B6B; padding: 0px 5px 0px 5px;">
  <p style="color: #ffffff;">{ts}Error{/ts}: {$error}</p>
</div>
<div id='donrec_buttons' class="crm-submit-buttons">
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
</div>
{elseif $conflict_error}
<h3>Error</h3>
<div id="error-block" style="color: #ffffff; background-color: #FF6B6B; padding: 0px 5px 0px 5px;">
  <p>{ts}Sorry, but at least one of the selected contributions is already being processed for a donation receipt:{/ts}</p>
  <p>{ts}The conflicting other donation receipt process was created by{/ts} <b>{$conflict_error[1]}</b></p>
  <p>{ts}It will automatically expire on{/ts} <b>{$conflict_error[2]}</b></p>
</div>
<div id='donrec_buttons' class="crm-submit-buttons form-item">
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
</div>
  {if $is_admin}
  <div class="form-item">
    <input name="donrec_abort_by_admin" value="{ts}Delete other process and restart{/ts}" class="form-submit" type="submit">
    <input type="hidden" name="return_to" value="{$return_to}">
  </div>
  {/if}
{else}
<div class="form-item">
<h2>{ts}Settings{/ts}</h2>
<!-- result format radioboxes-->
<table style="max-width:960px;">
  <tr>
    <td class="label">{ts}Donation receipt type{/ts}:</td>
    <td>
      <input {if $statistic.singleOrBulk == 'bulk'}disabled {elseif $statistic.singleOrBulk == 'single' || !$statistic.singleOrBulk}checked="checked" {/if}value="1" type="radio" id="donrec_type_single" name="donrec_type" class="form-radio"/>
      <label for="donrec_type_single">{ts}single receipts{/ts}</label>
      &nbsp;
      <input {if $statistic.singleOrBulk == 'single'}disabled {elseif $statistic.singleOrBulk == 'bulk'}checked="checked" {/if}value="2" type="radio" id="donrec_type_bulk" name="donrec_type" class="form-radio" />
      <label for="donrec_type_bulk">{ts}bulk receipts{/ts}</label>
    </td>
  </tr>
  <tr>
    <td class="label">{ts}Result formats{/ts}:</td>
    <td>
      {foreach from=$exporters item=item name=exporters}
        <input value="{$item[0]}" type="radio" id="result_type_{$item[0]}" name="result_type" {if $smarty.foreach.exporters.first}checked="checked"{/if} class="form-radio" {if !$item[3]}disabled{/if}/>
        <label for="result_type_{$item[0]}">{$item[1]}</label>
        {if !$item[3]} <span style="color:#ff0000;">({$item[4]})</span>{else}{$item[2]} {if $item[5]}<span style="color:#32cd32;">({$item[5]})</span>{/if}{/if}&nbsp;<br />
      {/foreach}
    </td>
  </tr>
</table>
</div>
<!-- the buttons -->
<div class="form-item">
  {if $statistic.status == 'TEST' || !$statistic.status}
    <input name="donrec_testrun" value="{ts}Test run{/ts}" class="form-submit" type="submit">
  {/if}
  {if $statistic.status == 'DONE' || !$statistic.status || $from_test}
    <input name="donrec_run" value="{ts}Issue donation receipt(s){/ts}" class="form-submit" type="submit">
  {/if}
  <input name="donrec_abort" value="{ts}Abort{/ts}" class="form-submit" type="submit">
</div>
{/if}
</form>
