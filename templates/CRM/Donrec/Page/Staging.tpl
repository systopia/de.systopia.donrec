{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  <p>
    {ts domain="de.systopia.donrec"}A number of contributions without (valid) receipt have been found for the selected time span. These will be marked as receipted once you hit the "Issue donation receipt(s)" button below.{/ts}
    <br/>
    {ts domain="de.systopia.donrec"}You can choose from a variety of result formats, depending on your needs. You can also choose "Don't generate files" if you only want to mark them as receipted.{/ts}
    <br/>
    {ts domain="de.systopia.donrec"}The "Test run" button will do all the same things except no contributions will be marked as receipted. We strongly recommend testing the creation of any larger batch of donation receipts.{/ts}
  </p>
  <p>
    {ts domain="de.systopia.donrec"}Please navigate this page using the buttons below and do not leave the page via the browser's back button.{/ts}
  </p>
</div>

{if $statistic.exporters}
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>
  <p>
    {ts domain="de.systopia.donrec"}<b>Caution:</b> this is a resumed non-test run. If you press the abort button, the run will be deleted for good.{/ts}
  </p>
</div>
{/if}

{if $statistic}
  <br/>
  <div style="max-width:320px;">
        <h2>{ts domain="de.systopia.donrec"}Statistics{/ts}</h2>

        {if $statistic.requested_contacts}
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}selected contacts{/ts}</div>
          <div class="crm-content">{$statistic.requested_contacts}</div>
        </div>
        {/if}
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}contact count{/ts}</div>
          <div class="crm-content">{$statistic.contact_count}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}contribution count{/ts}</div>
          <div class="crm-content">{$statistic.contribution_count}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}total amount{/ts}</div>
          <div class="crm-content">{$statistic.total_amount|crmMoney:$statistic.currency}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}Period from{/ts}</div>
          <div class="crm-content">{$statistic.date_from|crmDate:$config->dateformatFull}</div>
        </div>
        <div class="crm-summary-row">
          <div class="crm-label">{ts domain="de.systopia.donrec"}Period to{/ts}</div>
          <div class="crm-content">{$statistic.date_to|crmDate:$config->dateformatFull}</div>
        </div>
  </div>
{/if}
<br/>
<form id="stagingform" action="{$formAction}" method="post">
{if $error}
<br/>
<div id="error-block" style="background-color: #FF6B6B; padding: 0px 5px 0px 5px;">
  <p style="color: #ffffff;">{ts domain="de.systopia.donrec"}Error{/ts}: {$error}</p>
</div>
<div id='donrec_buttons' class="crm-submit-buttons">
  <a class="button" onClick="openURL('{$url_back}');" href="{$url_back}">
    <span align="right"><div class="icon back-icon ui-icon-arrowreturnthick-1-w"></div>{ts domain="de.systopia.donrec"}Back{/ts}</span>
  </a>
</div>
{elseif $conflict_error}
<h3>Error</h3>
<div id="error-block" style="color: #ffffff; background-color: #FF6B6B; padding: 0px 5px 0px 5px;">
  <p>{ts domain="de.systopia.donrec"}Sorry, but at least one of the selected contributions is already being processed for a donation receipt:{/ts}</p>
  <p>{ts domain="de.systopia.donrec"}The conflicting other donation receipt process was created by{/ts} <b>{$conflict_error[1]}</b></p>
  <p>{ts domain="de.systopia.donrec"}It will automatically expire on{/ts} <b>{$conflict_error[2]}</b></p>
</div>
<div id='donrec_buttons' class="crm-submit-buttons form-item">
  <a class="button" onClick="openURL('{$url_back}');" href="{$url_back}">
    <span align="right"><div class="icon back-icon ui-icon-arrowreturnthick-1-w"></div>{ts domain="de.systopia.donrec"}Back{/ts}</span>
  </a>
</div>
  {if $is_admin}
  <div class="form-item">
    <input name="donrec_abort_by_admin" value="{ts domain="de.systopia.donrec"}Delete other process and restart{/ts}" class="form-submit" type="submit">
    <input type="hidden" name="return_to" value="{$return_to}">
  </div>
  {/if}
{else}
<div class="form-item">
<h2>{ts domain="de.systopia.donrec"}Settings{/ts}</h2>
<!-- result format radioboxes-->
<table style="max-width:960px;">
  <tr>
    <td class="label">{ts domain="de.systopia.donrec"}Donation receipt type{/ts}:</td>
    <td>
      {if $from_test}
        <input {if $statistic.singleOrBulk == 'single'}checked="checked" {/if}value="1" type="radio" id="donrec_type_single" name="donrec_type" class="form-radio"/>
        <label for="donrec_type_single">{ts domain="de.systopia.donrec"}single receipts{/ts}</label>
        &nbsp;
        <input {if $statistic.singleOrBulk == 'bulk'}checked="checked" {/if}value="2" type="radio" id="donrec_type_bulk" name="donrec_type" class="form-radio" />
        <label for="donrec_type_bulk">{ts domain="de.systopia.donrec"}bulk receipts{/ts}</label>
      {else}
        <input {if $statistic.singleOrBulk == 'bulk'}disabled {elseif $statistic.singleOrBulk == 'single' || !$statistic.singleOrBulk}checked="checked" {/if}value="1" type="radio" id="donrec_type_single" name="donrec_type" class="form-radio"/>
        <label for="donrec_type_single">{ts domain="de.systopia.donrec"}single receipts{/ts}</label>
        &nbsp;
        <input {if $statistic.singleOrBulk == 'single'}disabled {elseif $statistic.singleOrBulk == 'bulk'}checked="checked" {/if}value="2" type="radio" id="donrec_type_bulk" name="donrec_type" class="form-radio" />
        <label for="donrec_type_bulk">{ts domain="de.systopia.donrec"}bulk receipts{/ts}</label>
      {/if}
    </td>
  </tr>
  <tr>
    <td class="label">{ts domain="de.systopia.donrec"}Result formats{/ts}:</td>
    <td>
      {foreach from=$exporters item=item name=exporters}
        {if $selected_exporter}
            <input value="{$item[0]}" type="radio" id="result_type_{$item[0]}" name="result_type" {if $selected_exporter == $item[0]}checked="checked"{/if} class="form-radio" {if !$item[3]}disabled{/if}/>
        {elseif $statistic.exporters}
            <input value="{$item[0]}" type="radio" id="result_type_{$item[0]}" name="result_type" {if $item[0] == $statistic.exporters[0]}checked="checked"{else}disabled{/if} class="form-radio" {if !$item[3]}disabled{/if}/>
        {else}
            <input value="{$item[0]}" type="radio" id="result_type_{$item[0]}" name="result_type" {if $smarty.foreach.exporters.first}checked="checked"{/if} class="form-radio" {if !$item[3]}disabled{/if}/>
        {/if}
        <label for="result_type_{$item[0]}">{$item[1]}</label>
        {if !$item[3]} <span style="color:#ff0000;">({$item[4]})</span>{else}{$item[2]} {if $item[5]}<span style="color:#32cd32;">({$item[5]})</span>{/if}{/if}&nbsp;<br />
      {/foreach}
    </td>
  </tr>
</table>
</div>
<!-- the buttons -->
<div class="form-item">
  {if $statistic.status != 'DONE'}
    <input name="donrec_testrun" value="{ts domain="de.systopia.donrec"}Test run{/ts}" class="form-submit" type="submit">
  {/if}
  <input name="donrec_run" value="{ts domain="de.systopia.donrec"}Issue donation receipt(s){/ts}" class="form-submit" type="submit">
  <input name="donrec_abort" value="{ts domain="de.systopia.donrec"}Abort{/ts}" class="form-submit" type="submit">
</div>
{/if}
</form>

<script type="text/javascript">
var dontleave = "{ts domain="de.systopia.donrec"}PLEASE DO NOT CLOSE OR REFRESH THIS PAGE!{/ts}";

{literal}
// add a "don't leave" message if the user wants to close the page
window.onbeforeunload = function(e) {
  return dontleave;
};

// and provide a function to go around it
function openURL(url) {
  window.onbeforeunload = null;
  var view_url = cj("<div/>").html(url).text();
  location.href = view_url;
}

// if you use the submit buttons, of course you may leave the page
cj("input.form-submit").on('click', function(e){
  window.onbeforeunload = null;
});


{/literal}
</script>
