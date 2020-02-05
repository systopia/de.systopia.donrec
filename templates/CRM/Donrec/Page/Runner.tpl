{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{if $error}
<p>Error: {$error}</p>
{else}
<p id='donrec_instructions'>{ts domain="de.systopia.donrec"}creation in progress, please wait...{/ts}  {ts domain="de.systopia.donrec"}PLEASE DO NOT CLOSE OR REFRESH THIS PAGE!{/ts}</p>
<div id="progressbar"></div>

<!-- the buttons -->
<div id='donrec_buttons' class="crm-submit-buttons" hidden>
  <a class="button" onClick="openURL('{$url_back}');">
    <span align="right"><div class="icon back-icon ui-icon-arrowreturnthick-1-w"></div>{if $test}{ts domain="de.systopia.donrec"}Back{/ts}{else}{ts domain="de.systopia.donrec"}Done{/ts}{/if}</span>
  </a>
</div>

<!-- the log messages -->
<div class="crm-accordion-wrapper.collapsed crm-donrec-process-log">
<div class="crm-accordion-header active">{ts domain="de.systopia.donrec"}Progress Log{/ts}</div>
<div class="crm-accordion-body">
<table>
  <thead>
    <tr>
      <td><strong>{ts domain="de.systopia.donrec"}Date{/ts}</strong></td>
      <td><strong>{ts domain="de.systopia.donrec"}Type{/ts}</strong></td>
      <td><strong>{ts domain="de.systopia.donrec"}Message{/ts}</strong></td>
    </tr>
  </thead>
  <tbody id="donrec-logtable-body">
  </tbody>
</table>
</div>
</div>

<script type="text/javascript">
var sid = {$sid};
var bulk = "{$bulk}";
var test = "{$test}";
var download_caption = "{ts domain="de.systopia.donrec"}Download{/ts}:&nbsp;";
var exporters = "{$exporters}";
var instructions_done = "{ts domain="de.systopia.donrec"}The donation receipts have been generated. You can now download the results.{/ts}";
var instructions_error = "{ts domain="de.systopia.donrec"}There was a problem. Please check the log below for more information.{/ts}";
var dontleave = "{ts domain="de.systopia.donrec"}PLEASE DO NOT CLOSE OR REFRESH THIS PAGE!{/ts}";
var file_downloaded = false || (test=='1');

var progress = 0;
{literal}
cj("#progressbar").progressbar({value:1});
cj(".crm-donrec-process-log").crmAccordionToggle();
cj(function() {
  if (typeof cj().crmAccordions == 'function') { 
    cj().crmAccordions();
  }
});

// add a "don't leave" message if the user wants to close the page
window.onbeforeunload = function(e) {
  return dontleave;
};

function runNextChunk() {
  CRM.api('DonationReceiptEngine', 'next', {'q': 'civicrm/ajax/rest', 'sid': sid, 'bulk': bulk, 'test': test, 'exporters': exporters},
    { success: processReply,
      error: function(data) {
        // TODO: implement error handling
        console.log("Error detected.");
        console.log(data);
        CRM.alert("{/literal}" + data['error_message'], "{ts domain="de.systopia.donrec"}Error{/ts}{literal}", "error");
      }
    }
  );
}

function processReply(reply) {
  // add log entry
  if (reply.values.log) {
    for (var i = 0; i < reply.values.log.length; i++) {
      var log_entry = reply.values.log[i];
      cj("#donrec-logtable-body").append("      \
        <tr>                                    \
          <td>" + log_entry.timestamp + "</td>  \
          <td>" + log_entry.type +      "</td>  \
          <td>" + log_entry.message +   "</td>  \
        </tr>");
    };
  }

  // visualize progress
  progress = reply.values.progress;
  cj("#progressbar").progressbar({value:(progress+1)});

  // kick off the next batch
  if (progress < 100) {
    runNextChunk();
  } else {
    processDone(reply);
  }
}

function processDone(reply) {
  cj('#donrec_buttons').show();
  cj('#donrec_instructions').text(instructions_done);
  cj("#progressbar").remove();

  // add download buttons for all files
  if (reply.values.files.length == 0) {
    file_downloaded = true;
  } else {
    for (var exporter in reply.values.files) {
      var download = reply.values.files[exporter];
      cj('#donrec_buttons').append("                                                      \
        <a class='button' onClick='file_downloaded=true;' href='" + download[1] + "' download='" + download[0] + "'>      \
          <span align='right'>                                                            \
            <div class='icon check-icon ui-icon-arrow-1-se'></div>" + download_caption + exporter + "        \
          </span>                                                                         \
        </a>");
    }    
  }
}

// function to open link after disabling the navigation warning
function openURL(url) {
  if (!file_downloaded) {
    CRM.confirm(function() {
      window.onbeforeunload = null;
      var view_url = cj("<div>").html(url).text();
      location.href = view_url;
    },
    {
      message: {/literal}"{ts domain="de.systopia.donrec"}You haven't downloaded the resulting file yet. Are you sure you want to leave this page? The file would be lost.{/ts}"{literal}
    });

  } else {
    window.onbeforeunload = null;
    var view_url = cj("<div>").html(url).text();
    location.href = view_url;
  }
}

// kick off process
runNextChunk();

</script>
{/literal}
{/if}
