<?php
$files = glob("*.jpg");
natcasesort($files);
$files = array_values($files);
$title = basename(dirname(__DIR__));

$images_json = json_encode(array_map(function($f){
    $exifData = @exif_read_data($f,'IFD0,EXIF,GPS');
    $exifArray = [];
    if($exifData){
        foreach($exifData as $key=>$value){
            if(is_scalar($value) || is_array($value)) $exifArray[$key] = $value;
        }
    }

    // IPTC címkék (alany neve)
    $personName = '—';
    $size = getimagesize($f, $info);
    if(isset($info['APP13'])){
        $iptc = iptcparse($info['APP13']);
        if(isset($iptc["2#025"]) && count($iptc["2#025"])>0){
            $personName = implode(', ', $iptc["2#025"]);
        }
    }

    return [
        'filename'=>$f,
        'size'=>filesize($f),
        'exif'=>$exifArray,
        'person'=>$personName // <-- ide kerül az alany neve
    ];
}, $files));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($title)?></title>
<style>
body { margin:0; padding:0; font-family:Arial,sans-serif; background:#111; color:#fff; }
h1 { text-align:center; margin:10px 0; font-size:22px; }
img#slideshow { max-width:90%; max-height:70vh; display:block; margin:20px auto 0; border-radius:12px; box-shadow:0 0 15px rgba(0,0,0,0.8); transition:opacity 0.5s ease-in-out; }
.controls, .download { text-align:center; margin-top:15px; }
button { background:#333; color:#fff; border:none; padding:10px 20px; margin:0 5px; border-radius:8px; cursor:pointer; font-size:20px; }
button:hover{background:#555;}
#image-info { text-align:center; margin-top:10px; font-size:16px; word-break:break-word; }
#gallery-container { opacity:0; max-height:0; overflow:hidden; transition:opacity 0.5s ease, max-height 0.5s ease; }
#gallery-container.show { opacity:1; max-height:none; }
.gallery { max-width:95%; margin:30px auto 0; display:flex; flex-wrap:wrap; gap:10px; justify-content:center; }
.gallery img { width:150px; height:100px; object-fit:cover; border-radius:8px; cursor:pointer; transition:transform 0.3s, filter 0.3s, opacity 0.3s; transform: translateZ(0); filter: blur(10px); opacity:0; }
.gallery img.loaded { filter: blur(0); opacity:1; }
.gallery img:hover { transform: scale(1.05); }
#load-more-container { height:120px; display:flex; justify-content:center; align-items:center; margin-bottom:30px; }
#load-more-container button { padding:10px 20px; font-size:18px; background:#333; color:#fff; border:none; border-radius:8px; cursor:pointer; }
#load-more-container button:hover {background:#555;}

/* Modal Info ablak – mobilbarát formázás */
#info-modal {
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.9);
    color:#fff;
    justify-content:center;
    align-items:center;
    padding:10px;
    box-sizing:border-box;
    overflow-y:auto;
}
#info-content {
    background:#222;
    padding:20px;
    border-radius:12px;
    width:95%;
    max-width:500px;
    box-sizing:border-box;
}
#info-content h2 {
    text-align:center;
    font-size:20px;
    margin-bottom:15px;
}
#info-content button {
    display:block;
    margin:15px auto 0;
    padding:10px 20px;
    font-size:16px;
    border-radius:8px;
    background:#333;
    border:none;
    color:#fff;
    cursor:pointer;
}
#info-content button:hover { background:#555; }

#info-details {
    display:flex;
    flex-direction:column;
    gap:8px;
    font-size:16px;
    line-height:1.4;
}
#info-details p {
    margin:0;
    word-break:break-word;
}


@media(max-width:600px){
    html, body { height:auto; overflow-y:auto; }
    body{padding:10px 0;}
    h1 { font-size:22px; }
    img#slideshow{ max-height:55vh; max-width:95%; margin:10px auto 0; }
    .controls, .download { margin-top:15px; }
    button{padding:8px 16px; margin:0 5px; font-size:18px;}
    #image-info{font-size:14px;}
    .gallery { display:flex; flex-wrap:wrap; justify-content:center; gap:30px; margin:20px auto; }
}
</style>
</head>
<body>
<h1><?=htmlspecialchars($title)?></h1>
<img id="slideshow" src="">
<div id="image-info"></div>

<div class="controls">
<button onclick="prevImage()">←</button>
<button onclick="nextImage()">→</button>
</div>

<div class="download">
<a id="download-btn" href="" download><button>Letöltés</button></a>
<button onclick="toggleGallery()">Galéria</button>
</div>

<div id="gallery-container">
    <div class="gallery" id="gallery"></div>
    <div id="load-more-container"></div>
</div>

<div id="info-modal">
    <div id="info-content">
        <h2>EXIF Info</h2>
        <div id="info-details"></div>
        <button onclick="closeInfo()">Bezárás</button>
    </div>
</div>

<script>
    
function getGps(coord, hemi){
    function gps2Num(coordPart){
        const parts = coordPart.split('/');
        if(parts.length <= 1) return parseFloat(parts[0]);
        return parseFloat(parts[0]) / parseFloat(parts[1]);
    }

    const degrees = coord.length > 0 ? gps2Num(coord[0]) : 0;
    const minutes = coord.length > 1 ? gps2Num(coord[1]) : 0;
    const seconds = coord.length > 2 ? gps2Num(coord[2]) : 0;
    let dec = degrees + (minutes/60) + (seconds/3600);
    return (hemi === 'S' || hemi === 'W') ? dec * -1 : dec;
}
    
const images = <?=$images_json?>;
const BATCH_SIZE = 50;
let index=0,
    slideshow=document.getElementById("slideshow"),
    imageInfo=document.getElementById("image-info"),
    downloadBtn=document.getElementById("download-btn"),
    gallery=document.getElementById("gallery"),
    galleryContainer=document.getElementById("gallery-container"),
    loadMoreContainer=document.getElementById("load-more-container"),
    galleryLoaded=false,
    currentBatch=0,
    currentLoader=null;

// slideshow előtöltés
function preloadImages(startIndex){
    for(let i=0;i<5;i++){
        const idx=(startIndex+i)%images.length;
        if(!images[idx].preloaded){
            const preload=new Image();
            preload.src=images[idx].filename;
            images[idx].preloaded=true;
        }
    }
}
function showImage(i){
    index=(i+images.length)%images.length;
    const img=images[index];
    slideshow.style.opacity=0;
    setTimeout(()=>{
        slideshow.src=img.filename;
        downloadBtn.href=img.filename;
        downloadBtn.download=img.filename;
        imageInfo.textContent=img.filename;
        slideshow.style.opacity=1;
        preloadImages(index);
    },200);
}
function nextImage(){showImage(index+1);}
function prevImage(){showImage(index-1);}
if(images.length>0) showImage(index); else document.body.innerHTML="<p style='color:white;'>Nincsenek képek.</p>";

// galéria toggle
function toggleGallery(){
    const isOpening = !galleryContainer.classList.contains("show");
    galleryContainer.classList.toggle("show");
    if(isOpening && !galleryLoaded){
        loadBatch(0);
        galleryLoaded=true;
    }
    window.scrollTo({top:0, behavior:'smooth'});
}

// batch betöltés
function loadBatch(batchIndex){
    currentBatch=batchIndex;
    if(currentLoader) currentLoader.cancelled=true;
    gallery.innerHTML='';

    const start=batchIndex*BATCH_SIZE;
    const end=Math.min(start+BATCH_SIZE, images.length);

    for(let i=start;i<end;i++){
        const thumb=document.createElement('img');
        thumb.dataset.src=images[i].filename;
        thumb.alt=images[i].filename;
        thumb.addEventListener('click',()=>{showImage(i); window.scrollTo({top:0, behavior:'smooth'});});
        gallery.appendChild(thumb);
    }

    let i=0;
    const thumbs=Array.from(gallery.querySelectorAll('img'));
    const loader={cancelled:false};
    currentLoader=loader;

    function loadNext(){
        if(loader.cancelled || i>=thumbs.length) return;
        const img=thumbs[i];
        const full=new Image();
        full.src=img.dataset.src;
        full.onload=()=>{
            if(loader.cancelled) return;
            img.src=full.src;
            img.classList.add('loaded');
            i++;
            loadNext();
        };
    }
    loadNext();

    loadMoreContainer.innerHTML='';
    if(batchIndex===0 && end<images.length){
        const btn=document.createElement('button');
        btn.textContent='További képek betöltése';
        btn.onclick=()=>loadBatch(batchIndex+1);
        loadMoreContainer.appendChild(btn);
    } else {
        if(batchIndex>0){
            const prevBtn=document.createElement('button');
            prevBtn.textContent='Előző';
            prevBtn.onclick=()=>loadBatch(batchIndex-1);
            loadMoreContainer.appendChild(prevBtn);
        }
        if(end<images.length){
            const nextBtn=document.createElement('button');
            nextBtn.textContent='Következő';
            nextBtn.onclick=()=>loadBatch(batchIndex+1);
            loadMoreContainer.appendChild(nextBtn);
        }
    }

    gallery.scrollIntoView({behavior:'smooth'});
}
    
slideshow.addEventListener('click', () => {
    if (images.length > 0) showInfo();
});


// Info modal
const infoModal = document.getElementById("info-modal");
const infoDetails = document.getElementById("info-details");

function showInfo(){
    const img = images[index];
    const parentDir = "<?= addslashes($title) ?>";

    // Dátum formázása YYYY-MM-DD HH:MM:SS
    let date = img.exif['DateTimeOriginal'] || img.exif['DateTime'] || '—';
    if(date !== '—'){
        const parts = date.split(' ');
        date = parts[0].replace(/:/g,'-') + ' ' + (parts[1] || '');
    }

    // Zársebesség
    let shutter = img.exif['ExposureTime'] ? img.exif['ExposureTime'] + "s" : '—';
    
    // Alany neve
    let person = img.person || '—';

    let html = `<p><strong>Album:</strong> ${parentDir}</p>`;
    html += `<p><strong>Fájlnév:</strong> ${img.filename}</p>`;
    html += `<p><strong>Készítés dátuma:</strong> ${date}</p>`;
    html += `<p><strong>Alkotó:</strong> ${img.exif['Artist'] || '—'}</p>`;
    html += `<p><strong>Alany:</strong> ${person}</p>`;
    html += `<p><strong>Fényképezőgép:</strong> ${img.exif['Model'] || '—'}</p>`;
    html += `<p><strong>Objektív:</strong> ${img.exif['UndefinedTag:0xA434'] || '—'}</p>`;
    html += `<p><strong>Felbontás:</strong> ${(img.exif['COMPUTED']?.Width && img.exif['COMPUTED']?.Height) ? img.exif['COMPUTED'].Width + "×" + img.exif['COMPUTED'].Height : '—'}</p>`;
    html += `<p><strong>ISO szám:</strong> ${img.exif['ISOSpeedRatings'] || '—'}</p>`;
    html += `<p><strong>Zársebesség:</strong> ${shutter}</p>`;
    html += `<p><strong>Rekeszérték:</strong> ${img.exif['COMPUTED']?.ApertureFNumber || '—'}</p>`;
    html += `<p><strong>Copyright:</strong> ${img.exif['Copyright'] || '—'}</p>`;
    html += `<p><strong>Fájlméret:</strong> ${(img.size/1024/1024).toFixed(2)} MB</p>`;
    html += `<p><strong>Software:</strong> ${img.exif['Software'] || '—'}</p>`;

    // GPS
    let gps = '—';
    if(img.exif['GPSLatitude'] && img.exif['GPSLongitude'] && img.exif['GPSLatitudeRef'] && img.exif['GPSLongitudeRef']){
        const lat = getGps(img.exif['GPSLatitude'], img.exif['GPSLatitudeRef']);
        const lon = getGps(img.exif['GPSLongitude'], img.exif['GPSLongitudeRef']);
        gps = `<a href="https://www.google.com/maps/search/?api=1&query=${lat},${lon}" target="_blank" style="color:dodgerblue;text-decoration:none;">${lat}, ${lon}</a>`;
    }
    html += `<p><strong>GPS:</strong> ${gps}</p>`;

    infoDetails.innerHTML = html;
    infoModal.style.display = "flex";
}

function closeInfo(){ infoModal.style.display="none"; }

// billentyűzet
document.addEventListener('keydown', e => {
    if (e.key === "ArrowLeft") prevImage();
    else if (e.key === "ArrowRight") nextImage();
    else if (e.key.toLowerCase() === "i") { // 'i' - EXIF modal toggle
        if (infoModal.style.display === "flex") closeInfo();
        else showInfo();
    }
    else if (e.key.toLowerCase() === "l") { // 'l' - letöltés
        downloadBtn.click();
    }
    else if (e.key.toLowerCase() === "g") { // 'g' - galéria
        toggleGallery();
    }
});


// mobil érintés
let startX=0;
slideshow.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;});
slideshow.addEventListener('touchend',e=>{
    const endX=e.changedTouches[0].clientX;
    if(endX-startX>50) prevImage();
    else if(startX-endX>50) nextImage();
});
</script>
</body>
</html>
