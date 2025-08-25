# php-gallery
Responsive PHP Gallery with directory listing, EXIF info and slideshow for photographers.


Built by me and Chat-GPT
The files are in Hungarian, but you can change it as you like.
If PHP is not yet installed on your web server: https://www.php.net/downloads.php


index.php -->
- lists .jpg and .mp4 files from your directory with EXIF data (if available)
- reads the name of the parent directory
- search option
- download button to make a .zip archive of your directory
- slideshow button

slideshow.php -->
- makes a slideshow from your .jpg files in your directory
- reads the name of the parent directory
- press arrow keys or the buttons to change image
- press 'L' or the button to download the current image
- press 'G' or the button to reveal the gallery
- press 'I" or the image to reveal the EXIF info

download.php -->
- converts the .jpg and .mp4 files from your directory to a .zip archive and downloads it
