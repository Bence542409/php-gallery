# php-gallery
<p>Responsive PHP Gallery with directory listing, EXIF info and slideshow for photographers.</p>
<br />
<p>Built by me and ChatGPT.</p>
<p>The files are in Hungarian, but you can change it as you like.</p>
<p>If PHP is not yet installed on your web server: <a href="https://www.php.net/downloads.php" target="_blank">https://www.php.net/downloads.php</a></p>
<br />
<p>index.php --></p>
<ul>
  <li>lists .jpg and .mp4 files from your directory with EXIF data (if available)</li>
  <li>reads the name of the parent directory</li>
  <li>search option (including search parameters: 'file:' & 'date:' # 'creator:' & 'camera:' & 'subject:' & 'gps:'</li>
  <li>download button to make a .zip archive of your directory</li>
  <li>slideshow button</li>
</ul>
<p>slideshow.php --></p>
<ul>
  <li>makes a slideshow from your .jpg files in your directory</li>
  <li>reads the name of the parent directory</li>
  <li>press arrow keys or the buttons to change image</li>
  <li>press 'L' or the button to download the current image</li>
  <li>press 'G' or the button to reveal the gallery</li>
  <li>press 'I' or the image to reveal the EXIF info</li>
</ul>
<p>download.php --></p>
<ul>
  <li>converts the .jpg and .mp4 files from your directory to a .zip archive and downloads it</li>
</ul>
