
{if $error}
<p>Error: {$error}</p>
{else}
<p id='donrec_instructions'>{ts}creation in progress, please wait...{/ts}  {ts}PLEASE DO NOT CLOSE THIS PAGE!{/ts}</p>
<div id="progressbar"></div>

<!-- the buttons -->
<div id='donrec_buttons' class="crm-submit-buttons" hidden>
  <a class="button" href="{$url_back}">
    <span align="right"><div class="icon back-icon"></div>{ts}Back{/ts}</span>
  </a>
  <a class="button" href="{$url_file}" download="results.zip">
    <span align="right"><div class="icon file-icon"></div>{ts}Download{/ts}</span>
  </a>
</div>

<!-- the log messages -->
<p/>
<div class="crm-accordion-wrapper.collapsed crm-donrec-process-log">
<div class="crm-accordion-header active">{ts}Progress Log{/ts}</div>
<div class="crm-accordion-body">
<p>Log messages</p>
</div>
</div>


<script>
var sid = {$sid};
var instructions_done = "{ts}The donation receipts have been generated. You can now download the results.{/ts}";
var instructions_error = "{ts}There was a problem. Please check the log below for more information.{/ts}";

var progress = 0;
{literal}
cj("#progressbar").progressbar({value:0});
cj(".crm-donrec-process-log").crmAccordionToggle();
cj(function() {
   cj().crmAccordions();
});

function runNextChunk() {
  CRM.api('DonationReceiptEngine', 'next', {'q': 'civicrm/ajax/rest', 'sid': sid},
    { success: function(data) {
        // TODO: implement
        console.log("YO");
        progress += 10;
        cj("#progressbar").progressbar({value:progress});
        if (progress < 100) {
          runNextChunk();
        } else {
          processDone();
        }
      },
      error: function(data) {
        // TODO: implement
        console.log("DAMN");
      }
    }
  );
}

function processDone(successfully) {
  cj('#donrec_buttons').show();
  cj('#donrec_instructions').text(instructions_done);
}

// kick off process
runNextChunk();

</script>
{/literal}
{/if}