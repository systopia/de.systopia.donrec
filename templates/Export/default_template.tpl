<!--
    Donation receipt template for CiviCRM donationreceipts extension.

    Created in 2011 by digitalcourage e.V. in Germany, based on the official
    forms published by the tax administration (last updated 2012); reworked in
    2012, 2013 by Software fuer Engagierte e.V. in Germany.

    To the extent possible under law, digitalcourage e.V. and Software fuer
    Engagierte e.V. have dedicated all copyright and related and neighboring
    rights to this template file to the public domain worldwide. This template
    is distributed without any warranty.

    You should have received a copy of the CC0 Public Domain Dedication along
    with these files. If not, see
    <http://creativecommons.org/publicdomain/zero/1.0/>.
-->

<style>
{literal}

body {
  font-family: Helvetica,sans-serif;
  font-size: 8pt;
  margin: 0;
}

.box {
  padding: 0 3pt;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: inherit;    /* Not sure why this is necessary when testing in Firefox... dompdf seems to work pefectly fine without it for a change. */
}

td, th {
  border: thin solid;
  padding: 3pt;
}

/* Tables with headers appearing merged into content cells. */

table.merged th {
  vertical-align: bottom;
  border-bottom: none;
  padding-bottom: 0 !important;
}

table.merged td {
  vertical-align: top;
  border-top: none;
}

/* Variable content inserted into template. */
.var {
  font-size: 10pt;
}


.party th {
  font-weight: normal;
  text-align: left;
}

.party td {
  padding-top: .5em;
}

#issuer .country-name {
  display: none;
}


h1 {
  font-size: 10pt;
}

#title h2 {
  /* Make it look pretty much like a normal paragraph... */
  /* A simple 'font: inherit' doesn't work in dompdf. */
  font-size: inherit;
  font-weight: inherit;
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
  text-align: right;
}


#checks {
  margin-top: 3em;
}

ul.radio {
  display: inline;
  padding: 1.5em;
  white-space: nowrap;
}

ul.radio li {
  display: inline;
  padding: 1.5em;
}


#exempt {
  margin-top: 1.5em;
}

#exempt img {
  float: left;
  margin-right: -16px;    /* Workaround for dompdf's failure to overlap the following block's margin with the float, unless the float has exactly 0 layout width... */
}

#exempt #text {
  margin-left: 24px;
  display: block;
}


#pledge {
  margin-top: 3em;
  border: thin solid;
  padding: 3pt;    /* padding also at top/bottom, unlike unbordered boxes */
}


#signature {
  margin-top: 5em;
}

#signature hr {
  color: black;
  margin: 0;
}


.footnote {
  position: absolute;
  bottom: 0;
}

.footnote p {
  margin-bottom: 0;
}


.newpage {
  page-break-before: always;
}

h2 {
  font-size: 10pt;    /* Same size as main heading, like in the official forms. */
}


#listing td.amount {
  text-align: right;
}

#listing #totals td, #listing #totals th {
  padding-top: 2em;
  border: none;
}

#listing #totals th {
  text-align: left;
}

#listing #totals .value {
  text-decoration: underline;
}

#listing .unit {
  visibility: hidden;
}

#listing #totals .unit {
  visibility: inherit;
}

{/literal}
</style>

<section id='page1'>

<section id='issuer' class='party'>
<table class='merged'>
  <tr><th>Aussteller</th></tr>
  <tr><td class='var'>
    {$organisation.name}<br />
    {$organisation.address}
  </td></tr>
</table>
</section>

<hgroup id='title' class='box'>
  <h1>{if $items}Sammelbestätigung{else}Bestätigung{/if} über Geldzuwendungen/{if $items}Mitgliedsbeiträge{else}Mitgliedsbeitrag{/if}</h1>
  <h2>im Sinne des § 10b des Einkommensteuergesetzes an eine der in § 5 Abs. 1 Nr. 9 des Körperschaftsteuergesetzes bezeichneten Körperschaften, Personenvereinigungen oder Vermögensmassen</h2>
</hgroup>

<section id='donor' class='party'>
<table class='merged'>
  <tr><th>Name und Anschrift des Zuwendenden:</th></tr>
  <tr><td class='var'>
    {$donor.name}<br />
    {$donor.street_address}<br />
    {$donor.postal_code} {$donor.city}
  </td></tr>
</table>
</section>

<section id='amounts'>
<table class='merged'>
  <tr>
    <th>{if $items}Gesamtbetrag{else}Betrag{/if} der Zuwendung - in Ziffern -</th>
    <th>- in Buchstaben -</th>
    <th>{if $items}Zeitraum der Sammelbestätigung{else}Tag der Zuwendung{/if}:</th>
  </tr>
  <tr class='var'>
    <td id='total'>{$total} Euro</td>
    <td>{$totaltext} Euro</td>
    <td>{if $items}{$daterange}{else}{$date}{/if}</td>
  </tr>
</table>
</section>

<div><div></div></div>    <!-- Workaround for dompdf being incapable of applying 'margin-top' on a <div> directly following </table>... -->

<section id='checks'>

{if !$items}
<section id='waiver' class='box'>
  Es handelt sich um den Verzicht auf Erstattung von Aufwendungen
  <ul class='radio'>
    <li>Ja&nbsp;<img alt='box' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAAXNSR0IArs4c6QAAAAZQTFRFAICPAAAAobuvMAAAAAF0Uk5TAEDm2GYAAAASSURBVAjXY/j/n6GBkVj0/z8ADKgLC9bAGB0AAAAASUVORK5CYII=' /></li>
    <li>Nein&nbsp;<img alt='box mit kreuz' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAAXNSR0IArs4c6QAAAAZQTFRFAOjHAAAAVlnjbgAAAAF0Uk5TAEDm2GYAAAArSURBVAjXY/j/n6GBEYRmSDLMsWToK2Rof8jQfBCEgAwgFygIlIKo+f8fAIrUEGMSLhXuAAAAAElFTkSuQmCC' /></li>
  </ul>
</section>
{/if}

<section id='exempt' class='box'>
  <img alt='box mit kreuz' src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAAXNSR0IArs4c6QAAAAZQTFRFAOjHAAAAVlnjbgAAAAF0Uk5TAEDm2GYAAAArSURBVAjXY/j/n6GBEYRmSDLMsWToK2Rof8jQfBCEgAwgFygIlIKo+f8fAIrUEGMSLhXuAAAAAElFTkSuQmCC' />
  <span id='text'>
    Wir sind wegen Förderung<br />
    <span class='var'>...Begründung...</span><br />
    nach dem letzten uns zugegangenen Freistellungsbescheid bzw. nach der Anlage zum Körperschaftsteuerbescheid des<br />
    Finanzamtes <span class='var'>...Ort...</span>,
    StNr <span class='var'>...Nummer...</span>,
    vom <span class='var'>...Datum...</span><br />
    nach § 5 Abs. 1 Nr. 9 des Körperschaftsteuergesetzes von der Körperschaftsteuer und nach § 3 Nr. 6 des Gewerbesteuergesetzes von der Gewerbesteuer befreit.
  </span>
</section>

</section>    <!-- checks -->

<section id='pledge' class='box'>
  Es wird bestätigt, dass die Zuwendung nur zur Förderung<br />
  <span class='var'>...Zweck...</span><br />
  verwendet wird.
</section>

{if $items}
  <section id='affirmation' class='box'>
    <p>Es wird bestätigt, dass über die in der Gesamtsumme enthaltenen Zuwendungen keine weiteren Bestätigungen, weder formelle Zuwendungsbestätigungen noch Beitragsquittungen oder ähnliches ausgestellt wurden und werden.</p>
    <p>Ob es sich um den Verzicht auf Erstattung von Aufwendungen handelt, ist der Anlage zur Sammelbestätigung zu entnehmen.</p>
  </section>
{/if}

<section id='signature' class='box'>
  <p class='var'>
    ...Ort...,
    den {$today}
  </p>
  <hr />
</section>

<section id='disclaimer' class='box footnote'>
  <p><strong>Hinweis:</strong><br />Wer vorsätzlich oder grob fahrlässig eine unrichtige Zuwendungsbestätigung erstellt oder wer veranlasst, dass Zuwendungen nicht zu den in der Zuwendungsbestätigung angegebenen steuerbegünstigten Zwecken verwendet werden, haftet für die entgangene Steuer (§ 10b Abs. 4 EStG, § 9 Abs. 3 KStG, § 9 Nr. 5 GewStG).</p>
  <p>Diese Bestätigung wird nicht als Nachweis für die steuerliche Berücksichtigung der Zuwendung anerkannt, wenn das Datum des Freistellungsbescheides länger als 5 Jahre bzw. das Datum der vorläufigen Bescheinigung länger als 3 Jahre seit Ausstellung der Bestätigung zurückliegt (BMF vom 15.12.1994 - BStBl I S. 884).</p>
</section>

</section>    <!-- page1 -->

<section id='page2' class='newpage'>
{if $items}
<h2 class='box'>Anlage zur Sammelbestätigung vom {$today}</h2>

<section id='listing'>
<table>
  <tr><th>Datum der Zuwendung</th><th>Art der Zuwendung</th><th>Verzicht auf die Erstattung von Aufwendungen</th><th>Betrag</th></tr>
  {foreach from=$items item=item}
    <tr><td>{$item.date}</td><td>{$item.art}</td><td>Nein</td><td class='amount'>{$item.amount}&nbsp;<span class='unit'>&euro;</span></td></tr>
  {/foreach}
  <tr id='totals'><th colspan='3'>Gesamtsumme</td><td class='amount'><span class='value'>{$total}</span>&nbsp;<span class='unit'>&euro;</span></td></tr>
</table>
</section>
{/if}
</section>    <!-- page2 -->
