<?php
$parentDirName = basename(dirname(__DIR__));
$files = array_diff(scandir(__DIR__), [basename(__FILE__), '.', '..']);
$files = array_filter($files, fn($f) => is_file($f) && preg_match('/\.(jpg|mp4)$/i', $f));
sort($files, SORT_NATURAL | SORT_FLAG_CASE);
$exifAvailable = function_exists('exif_read_data');

$totalFiles = count($files);
$totalSizeBytes = array_sum(array_map('filesize', $files));
$totalSizeGB = round($totalSizeBytes / (1024**3), 2); // GB-ra konvertálva

function gps2Num($coordPart){
    $parts = explode('/', $coordPart);
    return count($parts)<=1 ? (float)$parts[0] : (float)$parts[0]/(float)$parts[1];
}

function getGps($exifCoord,$hemi){
    $degrees = count($exifCoord)>0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord)>1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord)>2 ? gps2Num($exifCoord[2]) : 0;
    $flip = ($hemi=='W'||$hemi=='S')?-1:1;
    return $flip*($degrees+($minutes/60)+($seconds/3600));
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($parentDirName) ?></title>
<style>
body{font-family:Arial,sans-serif;padding:20px;}
.controls{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;}
.controls a{padding:8px 15px;background:#007BFF;color:#fff;text-decoration:none;border-radius:4px;}
.controls a:hover{background:#0056b3;}
.controls input{flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;}

table{width:100%;border-collapse:collapse;}
th, td{text-align:left;padding:8px 10px;border-bottom:1px solid #ddd;}
th{background:#f2f2f2;}
a.file-link{color:#007BFF;text-decoration:none;}
a.file-link:hover{text-decoration:underline;}
tr.hidden{display:none;}
    
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap; /* mobilon törhet sorra, ha nincs hely */
}
.header-stats {
    font-size: 0.9em;
    color: #666;
    white-space: nowrap;
}

/* Mobil nézet – minden adat egymás alatt, fájlok közötti vonallal */
@media (max-width:768px){
    table, thead, tbody, th, td, tr{display:block;width:100%;}
    thead{display:none;}
    .file-row{
        background:none;
        border:none;
        margin:0 auto;
        padding:10px 0;
        max-width:98%;
        box-sizing:border-box;
        position: relative;
    }
    .file-row td{
        display:block;
        padding:2px 0;
        border:none;
    }
    .file-row td a.file-link{
        display:inline-block;
        margin-bottom:4px;
        font-weight:bold;
        word-break: normal;
        overflow-wrap: break-word;
        font-size:0.95em;
    }
    .file-row td::before{
        content: attr(data-label)": ";
        font-weight:bold;
    }
    .file-row::after{
        content: "";
        display: block;
        height: 1px;
        background-color: #ccc;
        margin: 12px 0;
        width: 100%;
    }
    .file-row:last-child::after{
        display:none;
    }
    .title {
        font-size: 27px;
    }
    .header-stats {
        display: none;
    }
}
</style>
</head>
<body>
<div class="header-container">
    <h1 class="title"><?= htmlspecialchars($parentDirName) ?></h1>
    <?php if($totalFiles > 0): ?>
    <div class="header-stats">
        Fájlok: <?= $totalFiles ?> | Méret: <?= $totalSizeGB ?> GB
    </div>
    <?php endif; ?>
</div>

<div class="controls">
    <a href="download.php" target="_blank">Letöltés</a>
    <a href="slideshow.php">Képnézegető</a>
    <input type="text" id="search" placeholder="Keresés...">
</div>

<table id="fileTable">
<thead>
<tr>
    <th>Fájl</th>
    <th>Dátum</th>
    <th>Alkotó</th>
    <th>Fényképezőgép</th>
    <th>Alany</th>
    <th>GPS</th>
</tr>
</thead>
<tbody>
<?php foreach($files as $file):
    $date=$artist=$camera=$lens=$gps='&mdash;';
    if($exifAvailable){
        $exif=@exif_read_data($file,'IFD0,EXIF,GPS');
        if(isset($exif['DateTimeOriginal'])){
            $dateObj = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
            $date = $dateObj ? $dateObj->format('Y-m-d H:i:s') : '&mdash;';
        } elseif(isset($exif['DateTime'])){
            $dateObj = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTime']);
            $date = $dateObj ? $dateObj->format('Y-m-d H:i:s') : '&mdash;';
        }
        $artist = $exif['Artist'] ?? '&mdash;';
        $camera = $exif['Model'] ?? '&mdash;';
        $tags = '&mdash;';
        $data = getimagesize($file, $info);
        if(isset($info['APP13'])){
            $iptc = iptcparse($info['APP13']);
            if(isset($iptc["2#025"])){ // 2#025 = Keywords
                $tags = implode(', ', $iptc["2#025"]);
            }
        }
        if(isset($exif['GPSLatitude'],$exif['GPSLongitude'],$exif['GPSLatitudeRef'],$exif['GPSLongitudeRef'])){
            $lat=getGps($exif['GPSLatitude'],$exif['GPSLatitudeRef']);
            $lon=getGps($exif['GPSLongitude'],$exif['GPSLongitudeRef']);
            $gps="{$lat}, {$lon}";
        }
    }
?>
<tr class="file-row">
    <td data-label="Fájl"><a href="<?= htmlspecialchars($file) ?>" target="_blank" class="file-link"><?= htmlspecialchars($file) ?></a></td>
    <td data-label="Dátum"><?= $date ?></td>
    <td data-label="Alkotó"><?= $artist ?></td>
    <td data-label="Fényképezőgép"><?= $camera ?></td>
    <td data-label="Alany"><?= $tags ?></td>
    <td data-label="GPS"><?= $gps ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
const searchInput=document.getElementById('search');
searchInput.addEventListener('input',()=>{
    const filter=searchInput.value.toLowerCase();
    document.querySelectorAll('.file-row').forEach(row=>{
        row.classList.toggle('hidden',!row.innerText.toLowerCase().includes(filter));
    });
});
</script>

<footer style="margin-top:40px; text-align:center; font-size:0.9em; color:#666;">
    Server is powered by: 
    <a href="https://nemeth-bence.com" target="_blank" style="color:dodgerblue; text-decoration:none;">
        Németh Bence
    </a>
</footer>

</body>
</html>
