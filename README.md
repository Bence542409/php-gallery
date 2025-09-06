# php-gallery
<p>Responsive PHP Gallery with directory listing and slideshow for photographers using Lightroom.</p>
<br />
<p>Built by me and ChatGPT.</p>
<p>The files are in Hungarian, but you can change it as you like.</p>
<p>If PHP is not yet installed on your web server: <a href="https://www.php.net/downloads.php" target="_blank">https://www.php.net/downloads.php</a></p>
<br />
<p>Search parameters:</p>
<ul>
  <li>file: - filter by filename</li>
  <li>date: - filter by date</li>
  <li>creator: - filter by creator name</li>
  <li>camera: - filter by camera model</li>
  <li>subject: - filter by subject name</li>
  <li>gps: - filter by GPS coordinates</li>
  <li>rating: - filter by picture ratings</li>
  <li>& - join several parameters</li>
  <li>"" - search for exact match</li>
  <li>* - wildcard</li>
  <li># - between (only in date and rating column)</li>
  <li>> - greater than (only in date and rating column)</li>
  <li>< - smaller than (only in date and rating column)</li>
</ul>
<br />
<p>index.php --></p>
<ul>
  <li>lists .jpg and .mp4 files from your directory with EXIF data (if available)</li>
  <li>reads the name of the parent directory</li>
  <li>search option (including search parameters)</li>
  <li>opens .jpg files in slideshow view</li>
  <li>press 'L' or the download button to make a .zip archive of your directory</li>
  <li>press 'K' or the slideshow button to view your pictues in a gallery</li>
  <li>press the "backspace" key to go to the previous page</li>
  <li>press the "enter" key to focus the search input</li>
  <li>press the "esc" key to defocus the search input</li>
  <li>press the "tab" key to reset the search input</li>
</ul>
<p>slideshow.php --></p>
<ul>
  <li>makes a slideshow from your .jpg files in your directory</li>
  <li>reads the name of the parent directory</li>
  <li>press arrow keys or the buttons to change image</li>
  <li>press 'L' or the button to download the current image</li>
  <li>press 'G' or the button to reveal the gallery</li>
  <li>press 'I' or the image to reveal the EXIF info</li>
  <li>press the "backspace" key to go to the previous page</li>
</ul>
<p>download.php --></p>
<ul>
  <li>converts the .jpg and .mp4 files from your directory to a .zip archive and downloads it (only files that are resulted in search)</li>
  <li>press the "backspace" key to close the page</li>
</ul>
