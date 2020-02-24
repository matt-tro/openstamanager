<?php

echo '
<button type="button" class="btn btn-primary tip" '.(!empty($anagrafica) ? '' : 'disabled').' onclick="compile(this)" title="'.tr('Tenta la compilazione automatica delle informazioni delle fattura elettronica sulla base delle precedenti fatture del Fornitore').'.">
    <i class="fa fa-address-book"></i> '.tr('Compila automaticamente').'
</button>

<script>
function compile(btn) {
    var restore = buttonLoading(btn);

    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "GET",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            id_record: "'.$id_record.'",
            op: "compile",
        },
        success: function(response) {
            var data = JSON.parse(response);
            console.log(data);

            $("#id_tipo").selectSet(data.id_tipo);
            $("#pagamento").selectSetNew(data.pagamento.id, data.pagamento.descrizione);

            $("select[name^=iva]").each(function(){
                var aliquota = $(this).closest("tr").find("[id^=aliquota]").text();
                $(this).selectSet(data.iva[aliquota].id);
            });

            $("select[name^=conto]").each(function(){
                $(this).selectSetNew(data.conto.id, data.conto.descrizione);
            });

            buttonRestore(btn, restore);
        },
        error: function(data) {
            swal("'.tr('Errore').'", "'.tr('La compilazione automatica dei campi non è andata a buon fine').'.", "error");

            buttonRestore(btn, restore);
        }
    });
}
</script>';
