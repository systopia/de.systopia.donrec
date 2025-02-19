<!--{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Default Template                                       |
| Copyright (C) 2025 SYSTOPIA                            |
| Author: S. Frank (frank -at- systopia.de)              |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------+

*}--><?xml version="1.0" encoding="UTF-8"?>
<html>
<style>
{literal}
/** neue struktur */
/* fonts  */
body {
  font-family: Helvetica, Arial, sans-serif;
  font-size: 9pt;
  line-height: 1;
}
.font-x-small {
  font-size: 7pt;
}
.absenderblock_rechts, .adresse-fenster, .font-small {
  font-size: 8.2pt;
}
.font-hl {
  font-size: 12pt;
  font-weight: bold;
}

/* positions */
body {
  padding: 0 5mm 0 12mm;
}
.absenderblock_rechts {
  height: 48mm;
}
.absenderblock_rechts > div {
  margin-left: 130mm;
}
.adresse-fenster {
  height: 36mm;
  width: 75mm;
}
.sender {
  height: 5mm;
}
.footer {
  position: absolute;
  bottom: 0;
}
/* elements */
table {
  width: 100%;
  border-collapse: collapse;
}
th {
  text-align: left;
}
table.merged th {
  vertical-align: bottom;
}
/* pages */
.firstpage {
  position: relative;
  height: 273mm;
}
.newpage {
  page-break-before: always;
}
{/literal}
</style>
<body>
  <div class="firstpage">
    <div class="absenderblock_rechts">
      <div>
        {$organisation.display_name}<br/> 
        {$organisation.street_address}<br/>
        {if $organisation.supplemental_address_1}{$organisation.supplemental_address_1}<br/>{/if}
        {if $organisation.supplemental_address_2}{$organisation.supplemental_address_2}<br/>{/if}
        {$organisation.postal_code} {$organisation.city}<br/>
      </div>
    </div>
    <div class="adresse-fenster">
      <div class="sender font-x-small">
        <u>{$organisation.display_name}, {$organisation.street_address}, {$organisation.postal_code} {$organisation.city}</u>
      </div>

      <div>
        {$addressee.display_name}<br />
        {$addressee.street_address}<br />
        {if $addressee.supplemental_address_1}{$addressee.supplemental_address_1}<br/>{/if}
        {if $addressee.supplemental_address_2}{$addressee.supplemental_address_2}<br/>{/if}
        {$addressee.postal_code} {$addressee.city}<br/>
        {$addressee.country}<br/>
      </div>
    </div>
    <div class="main">
      <div class='font-hl'><P>{if $items}Sammelbestätigung{else}Bestätigung{/if} über Geldzuwendungen/Mitgliedsbeitrag [{$receipt_id}]</P></div>
      <p>Über Zuwendungen im Sinne des § 10 b des Einkommensteuergesetztes an eine der in § 5 Abs. 1 Nr. 9 des
      Körperschaftsteuergesetzes bezeichneten Körperschaften, Personenvereinigungen und Vermögensmassen</p>

      <p class="font-small">Name und Anschrift des Zuwendenden:<br />
        {$contributor.display_name}<br />
        {$contributor.street_address}<br />
        {$contributor.postal_code} {$contributor.city}<br />
        {if $contributor.supplemental_address_1}{$contributor.supplemental_address_1}<br/>{/if}
        {if $contributor.supplemental_address_2}{$contributor.supplemental_address_2}<br/>{/if}
        {$contributor.country}<br />
      </p>

      <table class='merged'>
        <tr class="font-x-small">
          <th>{if $items}Gesamtbetrag{else}Betrag{/if}&nbsp;der&nbsp;Zuwendung{if $items}{/if}<br>&nbsp;-&nbsp;in Ziffern&nbsp;-</th>
          <th>- in Buchstaben -</th>
          <th>{if $items}Zeitraum der Sammelbestätigung{else}Tag der Zuwendung{/if}:</th>
        </tr>
        <tr>
          <td id='total'>**{$total|crmMoney:EUR}</td>
          <td>{$totaltext}</td>
          <td>
            {if $items}
            {$date_from|crmDate:'%d.%m.%Y'} - {$date_to|crmDate:'%d.%m.%Y'}
            {else}
            {* absolutly not elegant *}
            {foreach from=$lines item=item}
            {$item.receive_date|crmDate:'%d.%m.%Y'}
            {/foreach}
            {/if}
          </td>
        </tr>
      </table>


      {if !$items}
      <div>
        <p><strong>Es handelt sich nicht um den Verzicht auf Erstattung von Aufwendungen.</strong></p>
      </div>
      {/if}

      <p>
        Wir sind wegen [Grund] nach dem letzten uns zugegangenen Freistellungsbescheids des Finanzamts
        [Ort], Aktenzeichen [Aktenzeichen], vom [Datum] nach § 5 Abs. 1 Nr. 9 des Körperschaftsteuergesetzes von der
        Körperschaftsteuer befreit.<br />
        <br />
        Es wird bestätigt, dass die Zuwendung nur für den Zweck zur [Grund] verwendet wird.<br />
        Es wird bestätigt, dass über die in der Gesamtsumme enthaltenen Zuwendungen keine weiteren Bestätigungen, weder formelle Zuwendungsbestätigungen noch Beitragsquittungen oder ähnliches ausgestellt wurden und werden.<br />

        {if $items}
        <br />
        Ob es sich um den Verzicht auf Erstattung von Aufwendungen handelt, ist der Anlage zur Sammelbestätigung zu entnehmen.
        {/if}
      </p>

      <div class="signature font-small">
        {$organisation.city}, den {$issued_on|crmDate:$config->dateformatFull}
        <p>
          [Unterschrift]<br />

          <br />Maximilian Mustermann,<br />-Geschäftsführer-<br /><b>{$organisation.organization_name}</b>
        </p>
      </div>


    </div>

    <div class="footer font-x-small">
      <p>
        <strong>Hinweis:</strong><br/>
        Wer vorsätzlich oder grob fahrlässig eine unrichtige Zuwendungsbestätigung
        erstellt oder veranlasst, dass Zuwendungen nicht zu den in der
        Zuwendungsbestätigung angegebenen steuerbegünstigten Zwecken verwendet
        werden, haftet für die entgangene Steuer (§&nbsp;10b&nbsp;Abs.&nbsp;4&nbsp;EStG,
        §9&nbsp;Abs.&nbsp;3&nbsp;KStG, §&nbsp;9&nbsp;Nr.&nbsp;5&nbsp;GewStG). Diese
        Bestätigung wird nicht als Nachweis für die steuerliche Berücksichtigung der
        Zuwendung anerkannt, wenn das Datum des Freistellungsbescheides länger als 5
        Jahre bzw. das Datum der Feststellung der Einhaltung der satzungsmäßigen
        Voraussetzungen nach §&nbsp;60a&nbsp;Abs.&nbsp;1&nbsp;AO länger als 3 Jahre
        seit Ausstellung des Bescheides zurückliegt (§&nbsp;63&nbsp;Abs.&nbsp;5&nbsp;AO).
      </p>
    </div>
  </div>

  {if $items}
  <div class="newpage">
    <div><p><strong>Anlage zur Sammelbestätigung vom {$issued_on|crmDate:'%d.%m.%Y'} für {$contributor.display_name}</strong></p></div>
    <table>
      <tr><th>Datum der Zuwendung</th><th>Art der Zuwendung</th><th>Verzicht auf die Erstattung von Aufwendungen</th><th>Betrag</th></tr>
      {foreach from=$items item=item}
      <tr><td>{$item.receive_date|date_format:"%d.%m.%Y"}</td><td>{$item.financial_type}</td><td>Nein</td><td class='amount'>{$item.total_amount|crmMoney:EUR}</td></tr>
      {/foreach}
      <tr id='totals'><th colspan='3'>Gesamtsumme</th><td class='amount'><span class='value'><b>**{$total|crmMoney:EUR}</b></span></span></td></tr>
    </table>
  </div>
  {/if}
</body>
</html>