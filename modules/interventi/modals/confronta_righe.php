<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

use Carbon\Carbon;
use Modules\Interventi\Intervento;

$intervento = Intervento::find($id_record);

$id_anagrafica = $intervento->idanagrafica;
$direzione = $intervento->direzione;
$righe = $_GET['righe'];

$righe = $dbo->fetchArray(
    'SELECT mg_articoli.descrizione, mg_articoli.codice, in_righe_interventi.*
    FROM in_righe_interventi
    JOIN mg_articoli ON mg_articoli.id = in_righe_interventi.idarticolo
    WHERE in_righe_interventi.id IN ('.$righe.')'
);
?>
<form action="" method="post" id="add-form">
    <table class="table table-striped table-hover table-condensed table-bordered m-3">
        <thead>
            <tr>
                <th width="35" class="text-center" ><?php echo tr('Codice'); ?></th>
                <th><?php echo tr('Descrizione'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Prezzo corrente'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Ultimo preventivo'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Ultima fattura'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($righe as $riga) { ?>
                <?php
                    $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

                    $ultimo_prezzo_preventivo = $dbo->fetchArray(
                        'SELECT
                            in_righe_interventi.idarticolo,
                            co_righe_preventivi.prezzo_unitario,
                            DATE(co_righe_preventivi.updated_at) AS updated_at
                        FROM
                            co_preventivi
                        INNER JOIN co_righe_preventivi ON co_righe_preventivi.idpreventivo = co_preventivi.id
                        INNER JOIN mg_articoli ON mg_articoli.id = co_righe_preventivi.idarticolo
                        INNER JOIN in_righe_interventi ON in_righe_interventi.idarticolo = mg_articoli.id
                        WHERE
                            co_preventivi.idanagrafica ='.prepare($id_anagrafica).' AND in_righe_interventi.idarticolo ='.prepare($riga['idarticolo']).' AND co_preventivi.idstato NOT IN (SELECT id FROM co_statipreventivi WHERE descrizione = "Bozza" OR descrizione = "In attesa di conferma" OR descrizione = "Rifiutato")
                        GROUP BY 
                            mg_articoli.id, co_righe_preventivi.id
                        ORDER BY
                            updated_at DESC'
                    )[0];

                    $ultimo_prezzo_vendita = $dbo->fetchArray(
                        'SELECT
                            in_righe_interventi.idarticolo,
                            co_righe_documenti.prezzo_unitario,
                            DATE(co_righe_documenti.updated_at) AS updated_at
                        FROM
                            co_documenti
                        INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento = co_documenti.id
                        INNER JOIN mg_articoli ON mg_articoli.id = co_righe_documenti.idarticolo
                        INNER JOIN in_righe_interventi ON in_righe_interventi.idarticolo = mg_articoli.id
                        WHERE
                            co_documenti.idanagrafica ='.prepare($id_anagrafica).' AND co_righe_documenti.idarticolo ='.prepare($riga['idarticolo']).' AND co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione = "Emessa" OR descrizione = "Pagato" OR descrizione = "Parzialmente pagato")
                        GROUP BY 
                            mg_articoli.id, co_righe_documenti.id
                        ORDER BY
                            updated_at DESC'
                    )[0];
                ?>

                <tr>
                    <td><?php echo $riga['codice']; ?></td>
                    <td><?php echo $riga['descrizione']; ?></td>
                    <td>
                        <div>
                            {[ "type": "number", "label": "", "data-id":"<?php echo $riga['id']; ?>","name": "nuovo_prezzo_unitario[]", "value": "<?php echo numberFormat($riga['prezzo_unitario'], 2); ?>"]}
                        </div>
                    </td>
                    <td class="text-center"><?php
                        if (isset($ultimo_prezzo_preventivo)) {
                            echo moneyFormat($ultimo_prezzo_preventivo['prezzo_unitario'], 2).(!empty($ultimo_prezzo_preventivo['updated_at']) ? ' <br><small class="help-block tip" title="'.dateFormat($ultimo_prezzo_preventivo['updated_at']).'">'.(new Carbon($ultimo_prezzo_preventivo['updated_at']))->diffForHumans().'</small>' : '');
                        } else {
                            echo 'n.d.';
                        }
                    ?></td>
                    <td class="text-center"><?php
                        if (isset($ultimo_prezzo_vendita)) {
                            echo moneyFormat($ultimo_prezzo_vendita['prezzo_unitario'], 2).(!empty($ultimo_prezzo_vendita['updated_at']) ? ' <br><small class="help-block tip" title="'.dateFormat($ultimo_prezzo_vendita['updated_at']).'">'.(new Carbon($ultimo_prezzo_vendita['updated_at']))->diffForHumans().'</small>' : '');
                        } else {
                            echo 'n.d.';
                        }
                    ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <a class="btn btn-primary btn-edit">
        <i class="fa fa-edit"></i> <?php echo tr('Modifica'); ?>
    </a>
</form>

<script>
    $(document).ready(function() {
        $('.btn-edit').on('click', function() {
            var id = [];
            $('input[name^="nuovo_prezzo_unitario"]').each(function() {
                id.push({
                    'id': $(this).data('id'),
                    'price': $(this).val(),
                });
            });

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: globals.id_module,
                    id_record: globals.id_record,
                    op: "edit-price",
                    backto: "record-edit",
                    righe: id,
                },
            success: function (response) {
                location.reload();
            },
            error: function() {
                location.reload();
            }
        });
        });
    });
</script>
