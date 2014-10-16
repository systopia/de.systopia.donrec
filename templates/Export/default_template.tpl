{*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Default Template                                       |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------+

*}<?xml version="1.0" encoding="UTF-8"?>
<html>
<style>
{literal}
body {
  font-family: Helvetica,sans-serif !important;
  font-size: 8pt!important;
  margin: 0;
}

.box {
  padding: 0 0pt;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: inherit!important;    /* Not sure why this is necessary when testing in Firefox... dompdf seems to work pefectly fine without it for a change. */
}

td, th {
  border: none;
  text-align: left;
}

/* Tables with headers appearing merged into content cells. */

table.merged th {
  font-size: 7pt!important;
  vertical-align: bottom;
  text-align: left;
  border-bottom: none;
  padding-bottom: 0!important;
}

table.merged td {
  vertical-align: top;
  text-align: left;
  border-top: none;
}

/* Variable content inserted into template. */
.var {
  font-size: 10pt!important;
}

.party th {
  font-weight: normal;
  text-align: left;
}

.party td {
  padding-top: .5em;
}

h1 {
  font-size: 12pt!important;
  font-style: normal!important;
  text-align: left!important;
  margin: 10px 0 10px 0 !important;
}

#crm-container {
    margin: 0px!important;
    font-family: Helvetica,sans-serif !important;
}

h2 {
  font-size: 10pt!important;    /* Same size as main heading, like in the official forms. */
}

h3 {
  font-size: 10pt!important;    /* Same size as main heading, like in the official forms. */
  text-align: right;
  margin-top: 1.5em;
  font-weight: normal;
}

#amounts {
  margin-top: 2em;
}

#amounts table {
  table-layout: fixed;    /* Doesn't actually work in dompdf... Need to explicitly set widths for each column if it's important :-( */
}

#amounts th {
  font-weight: normal;
  text-align: left;
}

#amounts td {
  padding-top: 1em;
}

#amounts #total {
  text-align: left;
}

#checks {
  margin-top: 3em;
}

#exempt {
  margin-top: 1.5em;
}

#exempt img {
  float: left;
  margin-right: -16px;    /* Workaround for dompdf's failure to overlap the following block's margin with the float, unless the float has exactly 0 layout width... */
}

#exempt #text {
  margin-left: 0px;
  display: block;
}

.signature {
  position: absolute;
  top: 725px;
}

.absenderblock_rechts {
  margin-left: 46em;
}


.footer {
  position: absolute;
  top: 855px;
  font-size: 7pt!important;
}

.firstpage {
    padding-top:0px;
    height:883px;
}

.main {
  position: absolute;
  top: 75mm;
}

.sender {
    font-size: 7pt!important;
}

.notice {
    font-size: 90%!important;
}
.newpage {
  page-break-before: always;
}

#listing td.amount {
  text-align: left;
}

#listing #totals td, #listing #totals th {
  padding-top: 2em;
  border: none;
  text-align: left;
}

#listing #totals th {
  text-align: left;
}

#listing #totals .value {
  text-decoration: underline;
}

#listing .unit {
}

#listing #totals .unit {
  visibility: inherit;
}
{/literal}
</style>
<body>
<div class="firstpage">
<div class="absenderblock_rechts">{$organisation.organization_name}<br/> {$organisation.street_address}<br/>{$organisation.postal_code} {$organisation.city}<br/>Telefon: {$organisation.phone}<br/>{$organisation.email}</div>
<p class="sender">
<u>{$organisation.organization_name}, {$organisation.street_address}, {$organisation.postal_code} {$organisation.city}</u>
</p>

<p>
{$contributor.display_name}<br />
{$contributor.street_address}<br />
{$contributor.postal_code} {$contributor.city}
{if $contributor.country ne 'Germany'}<br />{$contributor.country}{/if}
</p>

<div class="main">
<h1>{if $items}Sammelbestätigung{else}Bestätigung{/if} über Geldzuwendungen/ Mitgliedsbeitrag</h1><br />
<p class="notice">Über Zuwendungen im Sinne des § 10 b des Einkommensteuergesetztes an eine der in § 5 Abs. 1 Nr. 9 des
Körperschaftsteuergesetzes bezeichneten Körperschaften, Personenvereinigungen und Vermögensmassen</p>

<p>Name und Anschrift des Zuwendenden:<br />
    {$contributor.display_name}<br />
    {$contributor.street_address}<br />
    {$contributor.postal_code} {$contributor.city}<br />
    {if $contributor.country ne 'Germany'}{$contributor.country}<br />{/if}
</p>

<table class='merged'>
  <tr>
    <th>{if $items}Gesamtbetrag{else}Betrag{/if} der Zuwendung - in Ziffern -</th>
    <th>- in Buchstaben -</th>
    <th>{if $items}Zeitraum der Sammelbestätigung{else}Tag der Zuwendung{/if}:</th>
  </tr>
  <tr class='var'>
    <td id='total'>**{$total}</td>
    <td>{$totaltext}</td>
    <td>{if $items}{$daterange}{else}{$date}{/if}</td>
  </tr>
</table>


{if !$items}
<h2>
Es handelt sich nicht um den Verzicht auf Erstattung von Aufwendungen.
</h2>
{/if}

<p class="notice">
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

</div>
</div>

<div class="signature">
  {$organisation.city}, den {$today}
    <p>
    [Unterschrift]<br />

<br />Maximilian Mustermann,<br />-Geschäftsführer-<br /><b>{$organisation.organization_name}</b></p>
</div>

<div class="footer">
    <p><strong>Hinweis:</strong><br />Hinweis: Wer vorsätzlich oder grob fahrlässig eine unrichtige Zuwendungsbestätigung erstellt oder wer veranlasst, dass Zuwendungen nicht
zu den in der Zuwendungsbestätigung angegebenen steuerbegünstigten Zwecken verwendet werden, haftet für die Steuer, die dem Fiskus
durch einen etwaigen Abzug der Zuwendungen beim Zuwendenden entgeht (§ 10b Abs. 4 EStG, § 9 Abs. 3 KStG, § 9 Nr. 5 GewStG).
Diese Bestätigung wird nicht als Nachweis für die steuerliche Berücksichtigung der Zuwendung anerkannt, wenn das Datum des
Freistellungsbescheides länger als 5 Jahre bzw. das Datum der vorläufigen Bescheinigung länger als 3 Jahre seit Ausstellung der Bestätigung
zurückliegt (BMF vom 15.12.1994 – BStBl I S. 884).</p>
</div>

{if $items}
<div class="newpage">
<h2 class='box'>Anlage zur Sammelbestätigung vom {$today} für {$contributor.display_name}</h2>
<table>
  <tr><th>Datum der Zuwendung</th><th>Art der Zuwendung</th><th>Verzicht auf die Erstattung von Aufwendungen</th><th>Betrag</th></tr>
  {foreach from=$items item=item}
    <tr><td>{$item.receive_date|date_format:"%d.%m.%Y"}</td><td>{$item.type}</td><td>Nein</td><td class='amount'>{$item.total_amount}&nbsp;<span class='unit'>&euro;</span></td></tr>
  {/foreach}
  <tr id='totals'><th colspan='3'>Gesamtsumme</th><td class='amount'><span class='value'>**{$total}</span>&nbsp;<span class='unit'>&euro;</span></td></tr>
</table>
{else}
  <p style="visibility:hidden">foobar</p>

</div>
{/if}
</body>
</html>
