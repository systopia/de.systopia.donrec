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

      <tr class="crm-donrec-profile-form-block-enable_line_item">
        <td class="label">{$form.enable_line_item.label} <a
            onclick='CRM.help("{ts domain="de.systopia.donrec"}Enable line item{/ts}", {literal}{"id":"id-enable-line-item","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;'
            href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}"
            class="helpicon">&nbsp;</a></td>
        <td>{$form.enable_line_item.html}</td>
      </tr>

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

  {if $civioffice_enabled}
      <fieldset class="crm-form-block">

        <h3>{ts domain="de.systopia.donrec"}CiviOffice Integration{/ts}</h3>

        <table class="form-layout-compressed">

          <tr class="crm-donrec-settings-form-block-civioffice_document_uri">
            <td class="label">{$form.civioffice_document_uri.label}</td>
            <td>{$form.civioffice_document_uri.html}</td>
          </tr>

          <tr class="crm-donrec-settings-form-block-civioffice_document_renderer_uri">
            <td class="label">{$form.civioffice_document_renderer_uri.label}</td>
            <td>{$form.civioffice_document_renderer_uri.html}</td>
          </tr>

        </table>

      </fieldset>
  {/if}

  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>
