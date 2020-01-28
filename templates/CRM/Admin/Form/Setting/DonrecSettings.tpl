{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}
<div class="crm-block">

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

  <fieldset class="crm-form-block">

    <h3>{ts domain="de.systopia.donrec"}General Settings{/ts}</h3>

    <table class="form-layout-compressed">

      <tr class="crm-donrec-profile-form-block-packet_size">
        <td class="label">{$form.packet_size.label} <a
                  onclick='CRM.help("{ts domain="de.systopia.donrec"}Generator Packet Size{/ts}", {literal}{"id":"id-packet-size","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;'
                  href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}"
                  class="helpicon">&nbsp;</a></td>
        <td>{$form.packet_size.html}</td>
      </tr>

      <tr class="crm-donrec-profile-form-block-pdfinfo_path">
        <td class="label">{$form.pdfinfo_path.label} <a
                  onclick='CRM.help("{ts domain="de.systopia.donrec"}The <code>pdfinfo</code> Tool{/ts}", {literal}{"id":"id-pdfinfo-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;'
                  href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}"
                  class="helpicon">&nbsp;</a></td>
        <td>{$form.pdfinfo_path.html}</td>
      </tr>

      <tr class="crm-donrec-profile-form-block-pdfunite_path">
        <td class="label">{$form.pdfunite_path.label} <a
                  onclick='CRM.help("{ts domain="de.systopia.donrec"}The <code>pdfunite</code> Tool{/ts}", {literal}{"id":"id-pdfunite-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;'
                  href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}"
                  class="helpicon">&nbsp;</a></td>
        <td>{$form.pdfunite_path.html}</td>
      </tr>

    </table>

  </fieldset>

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>
