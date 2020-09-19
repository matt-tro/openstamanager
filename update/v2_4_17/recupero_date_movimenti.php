<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

// Rimozione delle limitazioni sull'esecuzione
set_time_limit(0);
ignore_user_abort(true);

$skip_permissions = true;
include_once __DIR__.'/../../core.php';

$last_backup = null; // Cartella di backup specifica
$file = null; // File di backup del database

// Ricerca dell'ultimo backup (idealmente versione 2.4.16)
if (empty($file) && empty($last_backup)) {
    $backups = Backup::getList();
    $last_backup = end($backups);
}

if (empty($file)) {
    // Individuazione del database nel backup
    if (ends_with($last_backup, '.zip')) {
        $zip = new ZipArchive();
        $zip->open($last_backup);

        $contents = $zip->getFromName('database.sql');

        // File temporaneo
        $file = DIRECTORY_SEPARATOR.
            trim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).
            DIRECTORY_SEPARATOR.
            ltrim('database.sql', DIRECTORY_SEPARATOR);

        file_put_contents($file, $contents);

        register_shutdown_function(function () use ($file) {
            unlink($file);
        });
    } else {
        $file = $last_backup.'/database.sql';
    }
}

// Lettura delle query
$queries = readSQLFile($file, ';');
$count = count($queries);

// Individuazione del dump di co_movimenti
$query = null;
for ($i = 0; $i < $count; ++$i) {
    if (starts_with($queries[$i], 'INSERT INTO `co_movimenti`')) {
        $query = $queries[$i];
    }
}

if (empty($query)) {
    echo 'Impossibile procedere';

    return;
}

// Lettura dei contenuti
$values = explode('VALUES', $query, 2)[1];
$values = explode('),', $values);

// Generazione delle query per il recupero delle date
$results = [];
foreach ($values as $row) {
    $row = substr(trim($row), 1);

    $campi = explode(',', $row);
    $id = $campi[0];
    $data = $campi[2];

    $results[] = 'UPDATE `co_movimenti` SET `data` = '.$data.' WHERE `id` = '.prepare($id).";";
}

echo implode("\n", $results);
