<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/catalog_store.php';

$metrics = catalog_metrics_snapshot();
if (($metrics['annonces_total'] ?? 0) === 0) {
    $items = [
        [
            'type' => 'vehicle',
            'title' => 'Peugeot 208 Allure',
            'subtitle' => '1.2 PureTech 100 - 2020 - 68500 km',
            'price' => '11990',
            'short_description' => 'Citadine soignee.',
            'description' => 'Vehicule controle, essai possible.',
            'specs' => 'Marque : Peugeot'
        ],
        [
            'type' => 'vehicle',
            'title' => 'Renault Clio V Business',
            'subtitle' => '1.5 Blue dCi 85 - 2019 - 94300 km',
            'price' => '10490',
            'short_description' => 'Vehicule propre.',
            'description' => 'Historique connu.',
            'specs' => 'Marque : Renault'
        ],
        [
            'type' => 'part',
            'title' => 'Optique avant gauche Peugeot 208',
            'subtitle' => 'Reference 9812345677',
            'price' => '180',
            'short_description' => 'Optique complet controle.',
            'description' => 'Piece compatible 208.',
            'specs' => 'Famille : Eclairage'
        ],
        [
            'type' => 'part',
            'title' => 'Jante aluminium Renault Clio',
            'subtitle' => '16 pouces',
            'price' => '95',
            'short_description' => 'Jante controlee.',
            'description' => 'Bon etat.',
            'specs' => 'Famille : Roue'
        ]
    ];

    foreach ($items as $payload) {
        catalog_upsert_item(
            $payload,
            ['name' => [], 'tmp_name' => [], 'error' => [], 'size' => [], 'type' => []],
            []
        );
    }
}

if (catalog_using_database()) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$connection->connect_error) {
        $date = date('Y-m-d', strtotime('+1 day'));
        $statement = $connection->prepare('INSERT INTO rendez_vous (nom, email, telephone, date, heure, service, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($statement) {
            $name = 'Jean Test';
            $email = 'jean.test@example.com';
            $phone = '0600000000';
            $time = '10:00';
            $service = 'Controle avant depart';
            $status = 'Confirme';
            $statement->bind_param('sssssss', $name, $email, $phone, $date, $time, $service, $status);
            $statement->execute();
            $statement->close();
        }
        $connection->close();
    }
}

var_export(catalog_metrics_snapshot());
echo PHP_EOL;
