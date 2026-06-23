<?php
header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

// Exemple d'appel à l'API Rukovoditel (A ajuster avec tes vrais paramètres)
$rukovoditel_url = "http://localhost/rukovoditel/api/v1/users?search=" . urlencode($search);
$api_key = "TON_API_KEY_RUKOVODITEL";

// Pour le test/POC, si tu n'as pas encore branché l'API, décommente les lignes ci-dessous :
/*
echo json_encode([
    ["id" => 102, "name" => "Jean Dupont"],
    ["id" => 105, "name" => "Marie Client"]
]);
exit;
*/

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $rukovoditel_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: " . $api_key
]);
$response = curl_exec($ch);
curl_close($ch);

// On suppose que Rukovoditel renvoie un tableau d'objets avec id et name
echo $response;