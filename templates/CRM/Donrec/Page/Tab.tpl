<div class="action-link">
  <a accesskey="N" href="{crmURL p='civicrm/donrec/create' q="cid=$cid" h=0}" class="button"><span><div class="icon add-icon"></div>{ts}Create new donation receipt{/ts}</span></a>
</div>
<div class="donrec-stats-block">
  <table>
    {foreach from=$display_receipts key=receipt_id item=receipt name=receipt_items}
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
      <td><a id="copy_receipt_{$receipt_id}" class="button"><span><div class="icon add-icon"></div>{ts}Create copy{/ts}</span></a><a id="delete_receipt_{$receipt_id}" class="button"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a><a id="withdraw_receipt_{$receipt_id}" class="button"><span><div class="icon back-icon"></div>{ts}Withdraw{/ts}</span></a>
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
  var re = /^(copy|withdraw|delete)_receipt_([0-9]+)/;
  cj(function() {
    // called for every withdraw-button
    cj('.donrec-stats-block a[id^="withdraw_receipt_"]').click(function() {
        // calculate receipt id
        var rid = re.exec(this.id);
        if (rid != null) {
          rid = rid[2];
          // withdraw this donation receipt
          CRM.api('DonationReceipt', 'withdraw', {'q': 'civicrm/ajax/rest', 'sequential': 1, 'rid': rid},
            {success: function(data) {
                if (data['is_error'] == 0) {
                  CRM.alert("{/literal}{ts}The donation receipt has been successfully withdrawn{/ts}", "{ts}Success{/ts}{literal}", "success");
                   cj('#Donation_receipts').load(CRM.url('civicrm/donrec/tab', {'reset': 1, 'snippet': 1, 'force': 1, 'cid':{/literal}{$cid}{literal}}));
                }else{
                  CRM.alert("{/literal}" + data['error_message'], "{ts}Error{/ts}{literal}", "error");
                }
              }
            }
          );
        }
      
    });
    // called for every copy-button
    cj('.donrec-stats-block a[id^="copy_receipt_"]').click(function() {
        // calculate receipt id
        var rid = re.exec(this.id);
        if (rid != null) {
          rid = rid[2];
          // copy this donation receipt
          CRM.api('DonationReceipt', 'copy', {'q': 'civicrm/ajax/rest', 'sequential': 1, 'rid': rid},
            {success: function(data) {
                if (data['is_error'] == 0) {
                  CRM.alert("{/literal}{ts}The donation receipt has been successfully copied{/ts}", "{ts}Success{/ts}{literal}", "success");
                  cj('#Donation_receipts').load(CRM.url('civicrm/donrec/tab', {'reset': 1, 'snippet': 1, 'force': 1, 'cid':{/literal}{$cid}{literal}}));
                }else{
                  CRM.alert("{/literal}" + data['error_message'], "{ts}Error{/ts}{literal}", "error");
                }
              }
            }
          );
        }
      
    });
    // called for every delete-button
    cj('.donrec-stats-block a[id^="delete_receipt_"]').click(function() {
        // calculate receipt id
        var rid = re.exec(this.id);
        if (rid != null) {
          rid = rid[2];
          // delete this donation receipt
          CRM.confirm(function() {
            CRM.api('DonationReceipt', 'delete', {'q': 'civicrm/ajax/rest', 'sequential': 1, 'rid': rid, 'id': 0},
            {success: function(data) {
                if (data['is_error'] == 0) {
                  CRM.alert("{/literal}{ts}The donation receipt has been successfully deleted{/ts}", "{ts}Success{/ts}{literal}", "success");
                  cj('#Donation_receipts').load(CRM.url('civicrm/donrec/tab', {'reset': 1, 'snippet': 1, 'force': 1, 'cid':{/literal}{$cid}{literal}}));
                }else{
                  CRM.alert("{/literal}" + data['error_message'], "{ts}Error{/ts}{literal}", "error");
                }
              }
            }
          );
          },
          {
            message: {/literal}"{ts}Are you sure you want to delete this donation receipt?{/ts}"{literal}
          });
        }
    });
  });
</script>
{/literal}