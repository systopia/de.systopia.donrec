{* create a temporary table with the data *}
<table id="receipted_data" hidden="1">
  {foreach from=$rows item=row}
  <tr id="rowid{$row.contribution_id}" class="{cycle values="odd-row,even-row"}{if $row.cancel_date} cancelled{/if} crm-contribution_{$row.contribution_id}">
    <td class="crm-contribution-is_receipted">{$row.is_receipted}</td>
  </tr>
  {/foreach}
</table>

{* then move the column from the temporary table into the original one *}
{literal}
<script type="text/javascript">
  // get the penultimate column index
  var columnNr = cj('.selector:first thead tr:first th:last').prev('th').index();

  // iterate over all items
  cj('#receipted_data tr td').each(function (rowIndex) {
    cj(this).insertAfter(cj('.selector:first tbody tr:nth-child(' + (rowIndex+1) + ') td:nth-child(' + columnNr + ')'));
  });

  // finally delete the temp table
  cj('#receipted_data').remove();
</script>
{/literal}
