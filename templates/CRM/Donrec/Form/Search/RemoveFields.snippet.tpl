{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<script type="text/javascript">
var ids = "{$field_ids_to_remove}".split(",");
{literal}

function removeFiedsToRemove() {
  for (var i=0; i<ids.length; i++) {
    try {
      cj("#custom_" + ids[i]).parent().parent().hide();
    } catch(e) {
      console.log(e);
    }
  }  
}

cj("#custom").ajaxComplete(removeFiedsToRemove);
removeFiedsToRemove();

{/literal}
</script>
