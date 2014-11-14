{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

{if $error}
<p>Error: {$error}</p>
{else}
<p id='donrec_instructions'>{ts}creation in progress, please wait...{/ts}  {ts}PLEASE DO NOT CLOSE OR REFRESH THIS PAGE!{/ts}</p>
<div id="progressbar"></div>

<!-- the buttons -->
<div id='donrec_buttons' class="crm-submit-buttons" hidden>
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
</div>

<!-- the log messages -->
<p/>
<div class="crm-accordion-wrapper.collapsed crm-donrec-process-log">
<div class="crm-accordion-header active">{ts}Progress Log{/ts}</div>
<div class="crm-accordion-body">
<table>
  <thead>
    <tr>
      <td><strong>{ts}Date{/ts}</strong></td>
      <td><strong>{ts}Type{/ts}</strong></td>
      <td><strong>{ts}Message{/ts}</strong></td>
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
var download_caption = "{ts}Download{/ts}:&nbsp;";
var exporters = "{$exporters}";
var instructions_done = "{ts}The donation receipts have been generated. You can now download the results.{/ts}";
var instructions_error = "{ts}There was a problem. Please check the log below for more information.{/ts}";
var dontleave = "{ts}PLEASE DO NOT CLOSE OR REFRESH THIS PAGE!{/ts}";

var progress = 0;
{literal}
cj("#progressbar").progressbar({value:1});
cj(".crm-donrec-process-log").crmAccordionToggle();
cj(function() {
   cj().crmAccordions();
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
          <td>" + log_entry.type + "</td>       \
          <td>" + log_entry.message + "</td>    \
        </tr>");
    };
  }

  // visualize progress
  progress = reply.values.progress;
  cj("#progressbar").progressbar({value:progress});

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
  //cj("#progressbar").progressbar("disable");
  cj("#progressbar").remove();
  window.onbeforeunload = null;  // remove the "dont't leave" message

  // add download buttons for all files
  for (var exporter in reply.values.files) {
    var download = reply.values.files[exporter];
    cj('#donrec_buttons').append("                                                      \
      <a class='button' href='" + download[1] + "' download='" + download[0] + "'>      \
        <span align='right'>                                                            \
          <div class='icon check-icon'></div>" + download_caption + exporter + "                    \
        </span>                                                                         \
      </a>");
  }  
}

// kick off process
runNextChunk();

</script>
{/literal}
{/if}
