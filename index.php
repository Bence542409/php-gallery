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
a.file-link{color:dodgerblue;text-decoration:none;}
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
    <th>Értékelés</th>
</tr>
</thead>
<tbody>
<?php foreach($files as $file):
    $date=$artist=$camera=$lens=$gps='&mdash;';
    $rating = '&mdash;'; // szám formátum
    $ratingStar = '&mdash;'; // csillagok

if ($exifAvailable){
    $exif=@exif_read_data($file,'IFD0,EXIF,GPS');

    // Dátum
    if(isset($exif['DateTimeOriginal'])){
        $dateObj = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
        $date = $dateObj ? $dateObj->format('Y-m-d H:i:s') : '&mdash;';
    } elseif(isset($exif['DateTime'])){
        $dateObj = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTime']);
        $date = $dateObj ? $dateObj->format('Y-m-d H:i:s') : '&mdash;';
    }

    // Alkotó
    $artist = $exif['Artist'] ?? '&mdash;';

    // Fényképezőgép (marad eredeti)
    $camera = $exif['Model'] ?? '&mdash;';

    // XMP Rating kiolvasása
        $xmpData = file_get_contents($file);
        if (preg_match('/<\?xpacket.*?x:xmpmeta.*?>(.*?)<\/x:xmpmeta>/si', $xmpData, $matches)) {
            $xmp = $matches[0];
            if (preg_match('/xmp:Rating="(\d+)"/', $xmp, $ratingMatch)) {
                $rating = (int)$ratingMatch[1];
                $ratingStar = str_repeat('★', $rating);
            }
        }
    
    if (isset($rating) && $rating !== '&mdash;') {
    $filled = (int)$rating;
    $empty = 5 - $filled;
    $ratingStar = str_repeat('★', $filled) . str_repeat('☆', $empty);
    } else {
        $ratingStar = '☆☆☆☆☆'; // ha nincs értékelés
    }


    // Alany (tags)
    $tags = '&mdash;';
    $data = getimagesize($file, $info);
    if(isset($info['APP13'])){
        $iptc = iptcparse($info['APP13']);
        if(isset($iptc["2#025"])){
            $tags = implode(', ', $iptc["2#025"]);
        }
    }

    // GPS
    if(isset($exif['GPSLatitude'],$exif['GPSLongitude'],$exif['GPSLatitudeRef'],$exif['GPSLongitudeRef'])){
        $lat=getGps($exif['GPSLatitude'],$exif['GPSLatitudeRef']);
        $lon=getGps($exif['GPSLongitude'],$exif['GPSLongitudeRef']);
        $gps = '<a href="https://www.google.com/maps/search/?api=1&query='
       . $lat . ',' . $lon . '" target="_blank" style="color:dodgerblue;text-decoration:none;">'
       . $lat . ', ' . $lon . '</a>';
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
    <td data-label="Értékelés" data-rating="<?= $rating ?>"><?= $ratingStar ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
const searchInput = document.getElementById('search');

searchInput.addEventListener('input', () => {
    const filter = searchInput.value.trim();
    const rows = document.querySelectorAll('.file-row');

    const conditions = filter.split('&').map(c => c.trim()).filter(c => c);

    rows.forEach(row => {
        let visible = true;

        for (let cond of conditions) {
            let field = null, value = cond;
            let exact = false;

            const match = cond.match(/^(\w+)\s*:\s*(.+)$/);
            if(match){
                field = match[1].toLowerCase();
                value = match[2].trim();
            }

            // Pontos egyezés / idézőjel kezelés
            if((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))){
                exact = true;
                value = value.slice(1, -1); // idézőjelek eltávolítása
            }

            let cell = null;
            if(field){
                switch(field){
                    case 'file': cell = row.querySelector('td[data-label="Fájl"]'); break;
                    case 'date': cell = row.querySelector('td[data-label="Dátum"]'); break;
                    case 'creator': cell = row.querySelector('td[data-label="Alkotó"]'); break;
                    case 'camera': cell = row.querySelector('td[data-label="Fényképezőgép"]'); break;
                    case 'subject': cell = row.querySelector('td[data-label="Alany"]'); break;
                    case 'gps': cell = row.querySelector('td[data-label="GPS"]'); break;
                    case 'rating': cell = row.querySelector('td[data-label="Értékelés"]'); break;
                }
            }

            let cellText = '';
            if(cell){
                cellText = (cell.dataset.rating !== undefined ? cell.dataset.rating : cell.innerText).toLowerCase();
            }

            const matchesValue = (txt) => {
                const val = value.toLowerCase();

                if(val.includes('*')){
                    // A * bármilyen karakterláncot helyettesít bárhol a szövegben
                    const regex = new RegExp('^' + val.split('*').map(s => s.replace(/[-\/\\^$+?.()|[\]{}]/g, '\\$&')).join('.*') + '$', 'i');
                    return regex.test(txt);
                } else {
                    return exact ? txt === val : txt.includes(val);
                }
            }

            // Rating és Date speciális kezelés
            if(field && (field === 'rating' || field === 'date')){
                const rangeMatch = value.match(/^(.+?)\#(.+)$/);
                if(rangeMatch){
                    let start = rangeMatch[1].trim();
                    let end = rangeMatch[2].trim();
                    if(field === 'rating'){
                        const cellVal = parseInt(cellText);
                        start = parseInt(start);
                        end = parseInt(end);
                        if(cellVal < start || cellVal > end){ visible=false; break; }
                    } else if(field === 'date'){
                        const cellDate = new Date(cell.innerText);
                        const startDate = new Date(start);
                        const endDate = new Date(end);
                        if(cellDate < startDate || cellDate > endDate){ visible=false; break; }
                    }
                } else if(value.startsWith('>') || value.startsWith('<')){
                    const operator = value[0];
                    const v = value.slice(1).trim();
                    if(field === 'rating'){
                        const cellVal = parseInt(cellText);
                        const cmpVal = parseInt(v);
                        if(operator === '>' && cellVal <= cmpVal){ visible=false; break; }
                        if(operator === '<' && cellVal >= cmpVal){ visible=false; break; }
                    } else if(field === 'date'){
                        const cellDate = new Date(cell.innerText);
                        const cmpDate = new Date(v);
                        if(operator === '>' && cellDate <= cmpDate){ visible=false; break; }
                        if(operator === '<' && cellDate >= cmpDate){ visible=false; break; }
                    }
                } else {
                    if(!matchesValue(cellText)){ visible=false; break; }
                }
            } else if(field){
                // Field alapú keresés
                if(!matchesValue(cellText)){ visible=false; break; }
            } else {
                // Paraméter nélküli keresés: minden cellában keresünk
                const allCells = Array.from(row.querySelectorAll('td'));
                visible = allCells.some(td => matchesValue(td.innerText.toLowerCase()));
                if(!visible) break;
            }
        }

        row.classList.toggle('hidden', !visible);
    });
});
    
// billentyűzet
document.addEventListener('keydown', e => {
    // ha éppen input vagy textarea van fókuszban → ne reagáljon
    if (["INPUT", "TEXTAREA"].includes(document.activeElement.tagName)) {
        return;
    }
    if (e.key.toLowerCase() === "l") { // 'l' - download.php
        window.location.href = "download.php";
    }
    else if (e.key.toLowerCase() === "k") { // 'k' - slideshow.php
        window.location.href = "slideshow.php";
    }
    else if (e.key === "Backspace") {
        e.preventDefault(); // ne töröljön szöveget
        history.back();     // menjen vissza az előző oldalra
    }
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
