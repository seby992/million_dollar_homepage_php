<?php
header('Content-Type: application/json');
$dataFile = 'pixels.json';
$uploadDir = 'uploads/';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo file_get_contents($dataFile);
    exit;
}

if ($method === 'POST') {
    $x = isset($_POST['x']) ? intval($_POST['x']) : 0;
    $y = isset($_POST['y']) ? intval($_POST['y']) : 0;
    $width = isset($_POST['width']) ? intval($_POST['width']) : 10;
    $height = isset($_POST['height']) ? intval($_POST['height']) : 10;
    $targetUrl = isset($_POST['targetUrl']) ? $_POST['targetUrl'] : '';
    $title = isset($_POST['title']) ? $_POST['title'] : '';

    // Leggi i pixel esistenti
    $currentData = json_decode(file_get_contents($dataFile), true);

    // --- CONTROLLO DI COLLISIONE (SOVRAPPOSIZIONE) ---
    foreach ($currentData as $p) {
        // Formula matematica per verificare se due rettangoli si sovrappongono
        $isOverlapping = (
            $x < ($p['x'] + $p['width']) &&
            ($x + $width) > $p['x'] &&
            $y < ($p['y'] + $p['height']) &&
            ($y + $height) > $p['y']
        );

        if ($isOverlapping) {
            echo json_encode(['success' => false, 'error' => 'Attenzione: parte dell\'area selezionata è già occupata da un altro banner!']);
            exit;
        }
    }
    // ------------------------------------------------

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Errore nel caricamento dell\'immagine']);
        exit;
    }

    $fileTmpPath = $_FILES['image']['tmp_name'];
    $fileName = $_FILES['image']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'error' => 'Formato immagine non consentito']);
        exit;
    }

    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $imageUrl = $destPath;

        $newPixel = [
            'id' => time(),
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'imageUrl' => $imageUrl,
            'targetUrl' => $targetUrl,
            'title' => $title
        ];

        $currentData[] = $newPixel;
        file_put_contents($dataFile, json_encode($currentData, JSON_PRETTY_PRINT));

        echo json_encode(['success' => true, 'pixel' => $newPixel]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Impossibile salvare il file sul server']);
    }
    exit;
}
