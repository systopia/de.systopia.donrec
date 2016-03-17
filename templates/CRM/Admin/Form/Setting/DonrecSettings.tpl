{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*}

<div class="crm-block crm-form-block">
  <div>
    <h3>{ts domain="de.systopia.donrec"}Profile{/ts} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Profile{/ts}", {literal}{"id":"id-profile","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></h3>
    {$form.profile_data.html}
    <table>
      <tr>
        <td>{$form.profile.html}</td>
        <td>
          <label for="clone_name">{ts domain="de.systopia.donrec"}Copy to:{/ts}</label>
          <input type="text" size="20" id="clone_name" />
          <input type="button" id="clone_button" value="{ts domain="de.systopia.donrec"}Clone{/ts}" title="{ts domain="de.systopia.donrec"}Create a copy of the current configuration{/ts}"/>
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
    <h3>{ts domain="de.systopia.donrec"}Text{/ts}</h3>
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
    <h3>{ts domain="de.systopia.donrec"}PDF{/ts}</h3>
    <div>
      <div>
        <table>
          <tr>
            <td class="label"><label for="store_pdf"> {ts domain="de.systopia.donrec"}Store original *.pdf files{/ts} <a onclick='CRM.help("{ts domain="de.systopia.donrec"}Store original PDF{/ts}", {literal}{"id":"id-store-pdf","file":"CRM\/Admin\/Form\/Setting\/DonrecSettings"}{/literal}); return false;' href="#" title="{ts domain="de.systopia.donrec"}Help{/ts}" class="helpicon">&nbsp;</a></label></td>
            <td><input value="1" type="checkbox" id="store_pdf" name="store_pdf" {if $store_pdf}checked="checked"{/if} class="form-checkbox"/></td>
          </tr>
        </table>
      </div>
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
    <h3>{ts domain="de.systopia.donrec"}General Settings{/ts}</h3>
    <div>
      <table>
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

  cj("#profile").change(function() {
    console.log("change");
  });

  /**
   * clone profile function
   */
  cj("#clone_button").click(function() {
    var current_name = cj("#profile").val();
    var new_name = cj("#clone_name").val();
    if (new_name == null || new_name.length == 0) {
      CRM.alert("{/literal}{ts domain="de.systopia.donrec"}You have to name your new profile!{/ts}", "{ts}Error{/ts}{literal}", "error");
      return;
    }

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
  });

  
  cj("#financial_types").change(function() {
    console.log(cj("#financial_types").val());
  })
  /** 
   * get the profile data map
   */
  function donrec_getProfileData() {
    var profile_data = jQuery.parseJSON(cj("input[name=profile_data]").val());
    console.log(profile_data);
    if (profile_data == null) {
      CRM.alert("Your profiles are invalid. Reset.", "Internal Error", "error");
      profile_data = {'Default': {}};
    }

    return profile_data;
  }

  /** 
   * set the profile data map
   */
  function donrec_setProfileData(profile_data) {
    console.log(JSON.stringify(profile_data));
    cj("input[name=profile_data]").val(JSON.stringify(profile_data));
    console.log(cj("input[name=profile_data]").val());
  }

  /**
   * copies the values of the selected profile to the respective fields
   */
  function donrec_setProfileValues() {
    var profiles = donrec_getProfileData();
    var current_name = cj("#profile").val();
    var profile = profiles[current_name];

    // first: set all values
    var fields = ['financial_types', 'draft_text', 'copy_text', 'legal_address', 'postal_address', 'legal_address_fallback', 'postal_address_fallback'];
    for (var i=0; i<fields.length; i++) {
      cj('#' + fields[i]).val(profile[fields[i]]);
    }

    // verify values, set sensible defaults
    var address_fields = ['legal_address', 'postal_address', 'legal_address_fallback', 'postal_address_fallback'];
    for (var i=0; i<address_fields.length; i++) {
      var current_value = cj('#' + address_fields[i]).val();
      if (current_value == null || current_value.length == 0) {
        cj('#' + address_fields[i]).val(["0"]); // "0" should be "primary"
      }
    }

    // TODO:

  }


  cj(donrec_setProfileValues);

</script>
{/literal}
