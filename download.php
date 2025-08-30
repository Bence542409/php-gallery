<?php
if(isset($_GET['files'])){
    $filesParam = $_GET['files']; // pl. file1.jpg,file2.mp4
    $files = array_map('urldecode', explode(',', $filesParam));
    // csak ellenőrzött fájlok, hogy ne legyen kódbefecskendezés
    $files = array_filter($files, fn($f) => is_file($f) && preg_match('/\.(jpg|mp4)$/i', $f));
} else {
    $files = []; // ha nincs fájl
}

if(empty($files)){
    exit("Nincsenek letölthető fájlok.");
}
sort($files, SORT_NATURAL | SORT_FLAG_CASE);


// ZIP letöltés kérése
if(isset($_GET['download'])){
    $zipName = basename(dirname(__DIR__)) . '.zip'; // aktuális mappa szülője neve ZIP névnek
    $zip = new ZipArchive();
    if($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE){
        exit("Nem sikerült létrehozni a ZIP fájlt.");
    }
    foreach($files as $file){
        $zip->addFile($file, basename($file));
    }
    $zip->close();

    // Fejlécek
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="'.basename($zipName).'"');
    header('Content-Length: ' . filesize($zipName));
    header('Cache-Control: no-cache');
    header('Pragma: public');

    // Streamelve küldés
    $chunkSize = 1024 * 1024; // 1 MB
    $handle = fopen($zipName, "rb");
    if($handle){
        while(!feof($handle)){
            echo fread($handle, $chunkSize);
            flush();
            ob_flush();
        }
        fclose($handle);
    }

    unlink($zipName);
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Letöltés folyamatban...</title>
<style>
body{font-family:Arial,sans-serif;padding:20px;text-align:center;}
button{
    margin-top:20px;
    padding:10px 20px;
    font-size:1em;
    background:#007BFF;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
button:hover{background:#0056b3;}
</style>
</head>
<body>
<h1>Letöltés folyamatban...</h1>
<p>Kérlek ne zárd be a böngészőt, míg a fájl le nem töltődött.</p>

<button onclick="window.close()">Bezárás</button>

<script>
// Letöltés indítása
window.onload = () => {
    const params = new URLSearchParams(window.location.search);
    params.set('download', '1');
    window.location.href = 'download.php?' + params.toString();
};
</script>
</body>
</html>
