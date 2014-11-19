{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*}

<script type="text/javascript">
var ids = "{$field_ids_to_remove}".split(",");
{literal}

function removeFiedsToRemove() {
  for (var i=0; i<ids.length; i++) {
    cj("#custom_" + ids[i]).parent().parent().remove();
  }  
}

cj("#custom").ajaxComplete(removeFiedsToRemove);
removeFiedsToRemove();

{/literal}
</script>
