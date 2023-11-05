# PropaganDee #

A PHP website to handle poster requests and display the digital archive of the [PropaganDee](https://www.svcover.nl/commissies.php?commissie=propagandee) of study association [Cover](https://www.svcover.nl/). It displays all files and folders in the digital archive, except those with a name starting with `_`. 

It requires PHP (>5.6) with ImageMagick and GhostScript (To render thumbnails of PDF-files) and a working SMTP configuration.
Set the right folders in `include/config.php` and you're good to go!

NB: it is advised not to index the `include` folder.

For questions regarding setup or expansion of this project, please contact Martijn Luinstra.