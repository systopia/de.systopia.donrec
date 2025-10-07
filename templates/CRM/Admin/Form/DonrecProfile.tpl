{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2020 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

{if $ajax_action}
    {if $ajax_action == 'add_variable'}
        {include file='CRM/Admin/Form/DonrecProfile/Variables.tpl'}
    {/if}
{else}

  <div class="crm-block">
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
      {if $op == 'delete'}

        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts domain="de.systopia.donrec"}WARNING: Deleting this option will result in the loss of Donation Receipts profile data.{/ts} {ts domain="de.systopia.donrec"}Do you want to continue?{/ts}
        </div>

          {if $is_default}
            <div class="crm-error no-popup">
              <div class="icon inform-icon"></div>
                {ts domain="de.systopia.donrec"}You are about to delete the default profile. Please select which of the other profiles should be set as default instead.{/ts}
            </div>

            <fieldset class="crm-form-block">

              <table class="form-layout">

                <tr class="crm-donrec-profile-form-block-new_default_profile">
                  <td class="label">{$form.new_default_profile.label}</td>
                  <td>{$form.new_default_profile.html}</td>
                </tr>

              </table>
            </fieldset>
          {/if}

      {elseif $op == 'default'}

        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts domain="de.systopia.donrec" 1="$profile_name"}This will set the profile <em>%1</em> as default.{/ts} {ts domain="de.systopia.donrec"}Do you want to continue?{/ts}
        </div>

      {elseif $op == 'activate'}

        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts domain="de.systopia.donrec" 1="$profile_name"}This will activate the profile <em>%1</em>.{/ts} {ts domain="de.systopia.donrec"}Do you want to continue?{/ts}
        </div>

      {elseif $op == 'deactivate'}

        <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts domain="de.systopia.donrec" 1="$profile_name"}This will deactivate the profile <em>%1</em>.{/ts} {ts domain="de.systopia.donrec"}Do you want to continue?{/ts}
        </div>

          {if $is_default}
            <div class="messages warning no-popup">
              <div class="icon inform-icon"></div>
                {ts domain="de.systopia.donrec" 1="$profile_name"}You are about to deactive the default profile, causing it not be selectable anymore for issueing donation receipts. Is this what you really intend to do?{/ts}
            </div>
          {/if}

      {else}

          {if $is_locked}
            <div class="crm-error">
                {ts domain="de.systopia.donrec"}This profile has already been used for issueing receipts and is locked. You may still edit the profile, but be aware that this will cause copies of already issued receipts not be identical to their original anymore, which may be considered fraudulent behavior by tax authorities.{/ts}
            </div>
          {/if}

        <fieldset class="crm-form-block">

          <h3>{ts domain="de.systopia.donrec"}General Settings{/ts}</h3>

          <table class="form-layout">

            <tr class="crm-donrec-profile-form-block-name">
              <td class="label">{$form.name.label}</td>
              <td>{$form.name.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-language">
              <td class="label">{$form.language.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Languages{/ts}", {literal}{"id":"id-language","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.language.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-enable_encryption">
              <td class="label">{$form.enable_encryption.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Enable encryption{/ts}", {literal}{"id":"id-enable_encryption","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.enable_encryption.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-financial_types">
              <td class="label">{$form.financial_types.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Contribution Types{/ts}", {literal}{"id":"id-contribution-types","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.financial_types.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-id_pattern">
              <td class="label">{$form.id_pattern.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Receipt ID{/ts}", {literal}{"id":"id-id_pattern","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.id_pattern.html}</td>
            </tr>

            <tr><td><h4>{ts domain="de.systopia.donrec"}Address Types{/ts}</h4></td></tr>

            <tr class="crm-donrec-profile-form-block-legal_address">
              <td class="label">{$form.legal_address.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Legal Address{/ts}", {literal}{"id":"id-address-legal","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.legal_address.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-legal_address_fallback">
              <td class="label">{$form.legal_address_fallback.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Legal Address Fallback{/ts}", {literal}{"id":"id-address-fallback","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.legal_address_fallback.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-postal_address">
              <td class="label">{$form.postal_address.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Postal Address{/ts}", {literal}{"id":"id-address-shipping","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.postal_address.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-postal_address_fallback">
              <td class="label">{$form.postal_address_fallback.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Postal Address Fallback{/ts}", {literal}{"id":"id-address-fallback","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.postal_address_fallback.html}</td>
            </tr>

          </table>

        </fieldset>

        <fieldset class="crm-form-block">

          <h3>{ts domain="de.systopia.donrec"}Receipts{/ts}</h3>

          <table class="form-layout">

            <tr class="crm-donrec-profile-form-block-template">
              <td class="label"><label for="template">{$form.template.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Template{/ts}", {literal}{"id":"id-template","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
              <td>{$form.template.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-template_pdf_format_id">
              <td class="label"><label for="template_pdf_format_id">{$form.template_pdf_format_id.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}PDF format{/ts}", {literal}{"id":"id-template_pdf_format_id","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
              <td>{$form.template_pdf_format_id.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-variables">
              <td class="label"><label>{ts domain="de.systopia.donrec"}Variables{/ts}</label> <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Variables{/ts}", {literal}{"id":"id-variables","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>
                <div id="crm-donrec-profile-form-block-variables-wrapper">
                  {include file='CRM/Admin/Form/DonrecProfile/Variables.tpl'}
                  {$form.variables_more.html}
                </div>
              </td>
            </tr>

            <tr class="crm-donrec-profile-form-block-store_original_pdf">
              <td class="label">{$form.store_original_pdf.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Store original PDF{/ts}", {literal}{"id":"id-store_original_pdf","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.store_original_pdf.html}</td>
            </tr>

            <tr>
              <td><h4>{ts domain="de.systopia.donrec"}Watermarks{/ts}</h4></td>
            </tr>

            <tr class="crm-donrec-profile-form-block-draft_text">
              <td class="label">{$form.draft_text.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Draft Text{/ts}", {literal}{"id":"id-draft-text","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.draft_text.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-copy_text">
              <td class="label">{$form.copy_text.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Copy Text{/ts}", {literal}{"id":"id-copy-text","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.copy_text.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-watermark_preset">
              <td class="label">{$form.watermark_preset.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Watermark preset{/ts}", {literal}{"id":"id-watermark-preset","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.watermark_preset.html}</td>
            </tr>

          </table>

        </fieldset>

        <fieldset class="crm-form-block">

          <h3>{ts domain="de.systopia.donrec"}E-Mails{/ts}</h3>

          <div class="help">
              {capture assign=processor_link}{crmURL p="civicrm/donrec/returns" q="reset=1"}{/capture}
              {ts domain="de.systopia.donrec"}Sending donation receipts by email can be tricky, make sure you know how you want to detect and handle bounces or returns.{/ts}
              {ts 1=$processor_link domain="de.systopia.donrec"}You should also have a look at the <a href="%1">return processing feature</a>.{/ts}
          </div>

          <table class="form-layout">

            <tr class="crm-donrec-profile-form-block-email_template">
              <td class="label">{$form.email_template.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Email-Template{/ts}", {literal}{"id":"id-email-template","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.email_template.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-from_email">
              <td class="label">{$form.from_email.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}From Email Address{/ts}", {literal}{"id":"id-from-email","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.from_email.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-bcc_email">
              <td class="label">{$form.bcc_email.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}BCC Email Address{/ts}", {literal}{"id":"id-bcc-email","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.bcc_email.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-return_path_email">
              <td class="label">{$form.return_path_email.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Return Path Email Address{/ts}", {literal}{"id":"id-return-path-email","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.return_path_email.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_handling">
              <td class="label">{$form.special_mail_handling.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Custom Mail handling{/ts}", {literal}{"id":"id-special-mail-handling","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_handling.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_header">
              <td class="label">{$form.special_mail_header.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Custom Mail Header{/ts}", {literal}{"id":"id-special-mail-header","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_header.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_header">
              <td class="label">{$form.special_mail_activity_id.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Custom Mail Header{/ts}", {literal}{"id":"id-special-mail-activity-id","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_activity_id.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_header">
              <td class="label">{$form.special_mail_activity_subject.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Custom Mail Header{/ts}", {literal}{"id":"id-special-mail-activity-subject","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_activity_subject.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_header">
              <td class="label">{$form.special_mail_activity_contact_id.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Activity Contact Id{/ts}", {literal}{"id":"id-special-mail-activity-contact-id","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_activity_contact_id.html}</td>
            </tr>

            <tr class="crm-donrec-profile-form-block-special_mail_header">
              <td class="label">{$form.special_mail_withdraw_receipt.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Withdraw Receipt{/ts}", {literal}{"id":"id-special-mail-withdraw-receipt","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>{$form.special_mail_withdraw_receipt.html}</td>
            </tr>
          </table>

        </fieldset>

        <fieldset class="crm-form-block">

          <h3>{ts domain="de.systopia.donrec"}Contributions{/ts}</h3>

          <table class="form-layout">

            <tr class="crm-donrec-profile-form-block-contribution_unlock_mode">
              <td class="label">{$form.contribution_unlock_mode.label} <a onclick='CRM.help("{ts escape='htmlattribute' domain="de.systopia.donrec"}Unlock receipted contributions{/ts}", {literal}{"id":"id-contribution_unlock_mode","file":"CRM\/Admin\/Form\/DonrecProfile"}{/literal}); return false;' href="#" title="{ts escape='htmlattribute' domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
              <td>
                  {$form.contribution_unlock_mode.html}
                <fieldset id="contribution_unlock_fields">
                    {foreach from=$contribution_unlock_fields item='contribution_unlock_field' key='contribution_unlock_key'}
                        {capture assign='contribution_unlock_field_form'}contribution_unlock_field_{$contribution_unlock_key}{/capture}
                      <div class="{$contribution_unlock_key}">
                          {$form.$contribution_unlock_field_form.html}
                          {$form.$contribution_unlock_field_form.label}
                      </div>
                    {/foreach}
                </fieldset>
              </td>
            </tr>

          </table>

        </fieldset>

      {/if}

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

  </div>
{literal}
  <script type="text/javascript">
    cj('#contribution_unlock_mode').on('change', function() {
      donrec_setContributionUnlockFields()
    });

    function donrec_setContributionUnlockFields() {
      if (cj('#contribution_unlock_mode').val() === 'unlock_selected') {
        cj('#contribution_unlock_fields').show();
      }
      else {
        cj('#contribution_unlock_fields').hide();
      }
    }

    // Initialize.
    cj(function() {
      donrec_setContributionUnlockFields()
    });
  </script>
{/literal}

{/if}
