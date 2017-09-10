<?php

// Valori aggiuntivi per la sostituzione
$replaces = [
    'docroot' => DOCROOT,
    'rootdir' => ROOTDIR,
    'footer' => !empty($footer) ? $footer : '',
    'dicitura_fissa_fattura' => get_var('Dicitura fissa fattura'),
    'pagination' => '
<table style="color:#aaa; font-size:10px;">
<tr>
    <td align="left" style="width:97mm;">
        '.tr('Stampato con OpenSTAManager').'
    </td>

    <td align="right" style="width:97mm;">
        '.tr('Pagina _PAGE_ di _TOTAL_', [
            '_PAGE_' => '{PAGENO}',
            '_TOTAL_' => '{nb}',
        ]).'
    </td>
</tr>
</table>',
];

// Retrocompatibilità
$id_cliente = $id_cliente ?: $idcliente;

// Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
if (empty($id_sede) || $id_sede == '-1') {
    $queryc = 'SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare($id_cliente);
} else {
    $queryc = 'SELECT an_anagrafiche.*, an_sedi.* FROM an_sedi JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_sedi.idanagrafica WHERE an_sedi.idanagrafica='.prepare($id_cliente).' AND an_sedi.id='.prepare($id_sede);
}
$rsc = $dbo->fetchArray($queryc);

// Lettura dati aziendali
$rsf = $dbo->fetchArray("SELECT * FROM an_anagrafiche WHERE idanagrafica = (SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')");
$id_azienda = $rsd[0]['id'];

$replace = [
    'c_' => $rsc[0],
    'f_' => $rsf[0],
];

$rename = [
    'capitale_sociale' => 'capsoc',
    'ragione_sociale' => 'ragionesociale',
    'codice_fiscale' => 'codicefiscale',
];

$keys = [];

foreach ($replace as $prefix => $values) {
    $values = (array) $values;
    if ($prefix == 'c_') {
        $keys = array_keys($values);
    }

    // Azienda predefinita non impostata
    if (empty($values) && $prefix == 'f_') {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = '';
        }
    }

    foreach ($rename as $key => $value) {
        $values[$value] = $values[$key];
        unset($values[$key]);
    }

    foreach ($values as $key => $value) {
        ${$prefix.$key} = $value;
    }

    $citta = '';

    if (!empty($values['cap'])) {
        $citta .= $values['cap'];
    }
    if (!empty($values['citta'])) {
        $citta .= ' '.$values['citta'];
    }
    if (!empty($values['provincia'])) {
        $citta .= ' ('.$values['provincia'].')';
    }

    $values['citta_full'] = $citta;

    if ($key == 'c_') {
        $keys = array_unique(array_merge($keys, array_keys($values)));
    }

    foreach ($values as $key => $value) {
        $replaces[$prefix.$key] = $value;
    }
}
