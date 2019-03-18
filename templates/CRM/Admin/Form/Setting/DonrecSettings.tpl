{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-block crm-form-block">
  <div>
    <h3>{ts domain="de.systopia.donrec"}Profile{/ts} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Profile{/ts}", {literal}{"id":"id-profile","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></h3>
    {$form.profile_data.html}
    {$form.selected_profile.html}
    <table>
      <tr>
        <td style="vertical-align:middle">
          {$form.profile.html}
        </td>
        <td style="vertical-align:middle">
          <input type="button" id="delete_button" value="{ts domain="de.systopia.donrec"}Delete{/ts}" title="{ts domain="de.systopia.donrec"}Remove the current configuration{/ts}"/>
        </td>
        <td style="vertical-align:middle">
          <label for="clone_name">{ts domain="de.systopia.donrec"}Create copy:{/ts}</label>
          <input type="text" size="20" id="clone_name" />
          <input type="button" id="clone_button" value="{ts domain="de.systopia.donrec"}Copy{/ts}" title="{ts domain="de.systopia.donrec"}Create a clone of the current configuration{/ts}"/>
        </td>
      </tr>
    </table>
  </div>

  <br/>
  <div>
    <h3>{ts domain="de.systopia.donrec"}Contribution Types{/ts}</h3>
    <div>
      <table>
        <tr>
          <td class="label">{$form.financial_types.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Contribution Types{/ts}", {literal}{"id":"id-contribution-types","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.financial_types.html}</td>
        </tr>
      </table>
    </div>
  </div>

  <div>
    <h3>{ts domain="de.systopia.donrec"}Receipts{/ts}</h3>
    <div>
      <div>
        <table>
          <tr>
            <td class="label"><label for="id_pattern">{$form.id_pattern.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Receipt ID{/ts}", {literal}{"id":"id-pattern","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
            <td>{$form.id_pattern.html}</td>
          </tr>
          <tr>
            <td class="label"><label for="template">{$form.template.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Template{/ts}", {literal}{"id":"id-template","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
            <td>{$form.template.html}</td>
          </tr>
          <tr>
            <td class="label"><label for="store_original_pdf"> {ts domain="de.systopia.donrec"}Store original *.pdf files{/ts} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Store original PDF{/ts}", {literal}{"id":"id-store-pdf","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
            <td><input value="1" type="checkbox" id="store_original_pdf" name="store_original_pdf" {if $store_original_pdf}checked="checked"{/if} class="form-checkbox"/></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div>
    <h3>{ts domain="de.systopia.donrec"}Watermarks{/ts}</h3>
    <div>
      <table>
        <tr>
          <td class="label">{$form.draft_text.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Draft Text{/ts}", {literal}{"id":"id-draft-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.draft_text.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.copy_text.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Copy Text{/ts}", {literal}{"id":"id-copy-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.copy_text.html}</td>
        </tr>
      </table>
    </div>
  </div>

  <div>
    <h3>{ts domain="de.systopia.donrec"}Address Types{/ts}</h3>
    <div>
      <table>
        <tr>
          <td class="label">{$form.legal_address.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Legal Address{/ts}", {literal}{"id":"id-address-legal","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.legal_address.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.legal_address_fallback.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Legal Address Fallback{/ts}", {literal}{"id":"id-address-fallback","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.legal_address_fallback.html}</td>
        </tr>
      </table>
      <table>
        <tr>
          <td class="label">{$form.postal_address.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Postal Address{/ts}", {literal}{"id":"id-address-shipping","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.postal_address.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.postal_address_fallback.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Postal Address Fallback{/ts}", {literal}{"id":"id-address-fallback","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.postal_address_fallback.html}</td>
        </tr>
      </table>
    </div>
  </div>
</div>

<br/>
<div class="crm-block crm-form-block">
  <div>
    <h3>{ts domain="de.systopia.donrec"}Email Settings{/ts}</h3>
    <div class="messages help">
      {capture assign=processor_link}{crmURL p="civicrm/donrec/returns" q="reset=1"}{/capture}
      {ts domain="de.systopia.donrec"}Sending donation receipts by email can be tricky, make sure you know how you want to detect and handle bounces or returns.{/ts}
      {ts 1=$processor_link domain="de.systopia.donrec"}You should also have a look at the <a href="%1">return processing feature</a>.{/ts}
    </div>
    <div>
      <table>
        <tr>
          <td class="label">{$form.donrec_email_template.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Email-Template{/ts}", {literal}{"id":"id-email-template","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_email_template.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.donrec_from_email.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}From Email Address{/ts}", {literal}{"id":"id-from-email","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_from_email.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.donrec_bcc_email.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}BCC Email Address{/ts}", {literal}{"id":"id-bcc-email","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_bcc_email.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.donrec_return_path_email.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Return Path Email Address{/ts}", {literal}{"id":"id-return-path-email","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_return_path_email.html}</td>
        </tr>
      </table>
    </div>
  </div>
</div>
<br/>
<div class="crm-block crm-form-block">
  <div>
    <h3>{ts domain="de.systopia.donrec"}General Settings{/ts}</h3>
    <div>
      <table>
        <tr>
          <td class="label">{$form.donrec_watermark_preset.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Watermark preset{/ts}", {literal}{"id":"id-donrec-watermark-preset","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_watermark_preset.html}</td>
          <td class="label">{$form.donrec_language.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Languages{/ts}", {literal}{"id":"id-language","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.donrec_language.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.packet_size.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Generator Packet Size{/ts}", {literal}{"id":"id-packet-size","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.packet_size.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.pdfinfo_path.label} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}The <code>pdfinfo</code> Tool{/ts}", {literal}{"id":"id-pdfinfo-text","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></td>
          <td>{$form.pdfinfo_path.html}</td>
        </tr>
      </table>
    </div>
  </div>
</div>

<br/>
<div class="crm-block">
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>



{literal}
<script type="text/javascript">
  //table-style
  cj('td.label').width(300);

  // defaults
  var donrec_value_defaults = {
    'id_pattern': '{issue_year}-{serial}',
    'template': 0,
    'financial_types': [],
    'store_original_pdf': false,
    'draft_text': "DRAFT",
    'copy_text': "COPY",
    'legal_address': ["0"],
    'postal_address': ["0"],
    'legal_address_fallback': ["0"],
    'postal_address_fallback': ["0"],
    'donrec_from_email': null
  };

  /**
   * change event handler for the profile method
   */
  cj("#profile").change(function(changeData) {
    var oldProfile = changeData.removed.id;
    var newProfile = changeData.added.id;
    donrec_updateProfile(oldProfile);
    donrec_setProfileValues();
  });

  /**
   * CLONE button click handler
   */
  cj("#clone_button").click(function() {

    var current_name = cj("#profile").val();
    var new_name = cj("#clone_name").val();
    if (new_name == null || new_name.length == 0) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}You have to name your new profile!{/ts}", "{ts}Error{/ts}{literal}", "error");
      return;
    }

    // before cloning, update model
    donrec_updateProfile(current_name);

    // get profile data
    var profile_data = donrec_getProfileData();
    if (new_name in profile_data) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}There already is a profile with that name.{/ts}", "{ts}Error{/ts}{literal}", "error");
      return;
    }

    // create new profile by cloning the old
    profile_data[new_name] = jQuery.extend(true, {}, profile_data[current_name]);
    donrec_setProfileData(profile_data);

    // finally: add and select the new profile
    cj("#clone_name").val('');
    cj("[name=profile]").append(new Option(new_name, new_name, true, true));
    cj("[name=profile]").val(new_name);
    cj("[name=profile]").select2('val', new_name);
    donrec_setProfileValues();

    // inform the user
    {/literal}
    var title = "{ts domain="de.systopia.donrec"}Profile profile_name created.{/ts}";
    title = title.replace('profile_name', new_name);
    title = title + "<br/>{ts domain="de.systopia.donrec"}Remeber to save to settings page for all profile changes to take effect.{/ts}";
    CRM.alert(title, "{ts domain="de.systopia.donrec"}Profile{/ts}", "info");
    {literal}
  });

  /**
   * DELETE button click handler
   */
  cj("#delete_button").click(function() {
    // get profile data
    var profile_data = donrec_getProfileData();
    if (Object.keys(profile_data).length <= 1) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}This is the only profile, you cannot delete this.{/ts}", "{ts domain="de.systopia.donrec"}Error{/ts}{literal}", "error");
      return;
    }

    CRM.confirm(function() {
      // delete from model
      var profile_data = donrec_getProfileData();
      var current_name = cj("#profile").val();
      delete profile_data[current_name];
      donrec_setProfileData(profile_data);

      // delete from select
      cj("[name=profile] option[value='" + current_name + "']").remove();

      for (new_name in profile_data) {
        cj("[name=profile]").val(new_name);
        cj("[name=profile]").select2('val', new_name);
        donrec_setProfileValues();
        break;
      }

      // inform the user
      {/literal}
      var title = "{ts domain="de.systopia.donrec"}Profile profile_name deleted{/ts}";
      title = title.replace('profile_name', current_name);
      title = title + "<br/>{ts domain="de.systopia.donrec"}Remeber to save to settings page for all profile changes to take effect.{/ts}";
      CRM.alert(title, "{ts}Profile{/ts}", "info");
      {literal}    
    },{
      title: {/literal}"{ts domain="de.systopia.donrec"}Are you sure?{/ts}"{literal},
      message: {/literal}"{ts domain="de.systopia.donrec"}Do you really want to delete this profile?<br/>If you have already created donation receipts with this profile, the profile-specific information will be lost (e.g. the template used).{/ts}"{literal}
    });
  });

  /**
   * copies the current values into the profile
   */
  function donrec_updateProfile(profileName) {
    var profile_data = donrec_getProfileData();
    for (field in donrec_value_defaults) {
      if (field == 'store_original_pdf') {
        profile_data[profileName][field] = cj('#' + field).prop('checked');
      } else {
        profile_data[profileName][field] = cj('#' + field).val();
      }
    }
    donrec_setProfileData(profile_data);
  }

  
  /** 
   * get the profile data map
   */
  function donrec_getProfileData() {
    var profile_data = jQuery.parseJSON(cj("input[name=profile_data]").val());
    if (profile_data == null) {
      CRM.alert("Your profiles are invalid. Reset.", "Internal Error", "error");
      profile_data = {'Default': jQuery.extend(true, {}, donrec_value_defaults)};
    }
    return profile_data;
  }

  /** 
   * set the profile data map
   */
  function donrec_setProfileData(profile_data) {
    cj("input[name=profile_data]").val(JSON.stringify(profile_data));
  }

  /**
   * copies the values of the selected profile to the respective fields
   */
  function donrec_setProfileValues() {
    var profiles = donrec_getProfileData();
    var current_name = cj("#profile").val();
    var profile = profiles[current_name];

    // set selected profile
    cj("[name=selected_profile]").val(current_name);

    // first: set all values
    for (field in donrec_value_defaults) {
      if (field == 'store_original_pdf') {
        cj('#' + field).prop('checked', profile[field]);
      } else if (field == 'financial_types' || field == 'template' || field == 'donrec_from_email') {
        cj('#' + field).select2('val', profile[field]);
      } else {
        cj('#' + field).val(profile[field]);
      }
    }

    // verify values, set sensible defaults if nothing is there (shouldn't happen)
    for (var field in donrec_value_defaults) {
      var current_value = cj('#' + field).val();
      if (current_value == null || current_value.length == 0) {
        cj('#' + field).val(donrec_value_defaults[field]); // "0" should be "primary"
      }
    }
  }

  // verify that draft/copy texts are valid
  function donrec_validate_text(e) {
    var current_value = cj(e.currentTarget).val();

    // make sure that there is a value
    if (current_value == null || current_value.length == 0) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}This value cannot be empty!{/ts}", "{ts}Error{/ts}{literal}", "error");
    } else if (!current_value.match("^[A-Za-zäöüÄÖÜß]+$")) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}This can only contain letters{/ts}", "{ts}Error{/ts}{literal}", "error");
    } else {
      return; // everything is fine...
    }

    // if we get here, we'll reset to default
    if ('copy_text' == cj(e.currentTarget).attr('id')) {
      cj(e.currentTarget).val("{/literal}{ts domain="de.systopia.donrec"}COPY{/ts}{literal}");
    } else {
      cj(e.currentTarget).val("{/literal}{ts domain="de.systopia.donrec"}DRAFT{/ts}{literal}");
    }
  }
  cj("#draft_text").change(donrec_validate_text);
  cj("#copy_text").change(donrec_validate_text);


  // verify ID entry upon change
  cj("#id_pattern").change(function() {
    var current_value = cj("#id_pattern").val();

    if (current_value == null || current_value.length == 0) {
      // You have to have a value...
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}This value cannot be empty!{/ts}", "{ts}Error{/ts}{literal}", "error");
      cj("#id_pattern").val("{issue_year}-{serial}");
    } else if (!current_value.match("[{]serial[}]")) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}You need to include <code>&#123;serial&#125;</code>{/ts}", "{ts}Error{/ts}{literal}", "error");
      cj("#id_pattern").val("{issue_year}-{serial}");
    } else if (current_value.match("[{]serial[}].*[{]serial[}]")) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}You can include <code>&#123;serial&#125;</code> only once!{/ts}", "{ts}Error{/ts}{literal}", "error");
      cj("#id_pattern").val("{issue_year}-{serial}");
    } else if (current_value.length > 64) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}This cannot contain more than 64 characters!{/ts}", "{ts}Error{/ts}{literal}", "error");
      cj("#id_pattern").val(current_value.substr(0,64));
    }
  });


  // initialisation
  cj(function() {
    // finally, set all values of the chosen profile
    donrec_setProfileValues();
  });
</script>
{/literal}
