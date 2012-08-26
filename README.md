### manufakturGallery

Present Facebook image galleries at [WebsiteBaker] [1] and [LEPTON CMS] [2] with the descriptions and comments, integrate the gallery into the search function of the content management system and display the photos in the search results.

#### Requirements

* minimum PHP 5.2.x
* using [WebsiteBaker] [1] _or_ using [LEPTON CMS] [2]
* [kitTools] [3] must be installed
* [Dwoo] [4] must be installed
* [DropletsExtension] [5] must be installed

#### Installation

* download the actual [manufakturGallery] [6] installation archive
* in CMS backend select the file from "Add-ons" -> "Modules" -> "Install module"

#### First Steps

The manufakturGallery will automatically install the Droplet **manufaktur_gallery**.

At the place you want to display the Facebook gallery insert the the Droplet at the right place of the WYSIWYG section.

To get a list of all available Facebook galleries of your account use

    [[manufaktur_gallery?facebook_id=<YOUR_FACEBOOK_ID>&action=list]]
    
Replace <YOUR_FACEBOOK_ID> with your own ID. Save the page and look at the result in the frontend. You wil see a list with the available Album ID's, the type of the album and a brief description.

Copy the Album ID you want to use and rewrite the droplet call to

     [[manufaktur_gallery?album_id=<ALBUM_ID>]] 
     
Replace <ALBUM_ID> with the correct ID.

That's all!

For more informations, hints, tipps & tricks please visit the [phpManufaktur Add-ons Website] [7] and join the [Addons Support Group] [8].  

[1]: http://websitebaker2.org "WebsiteBaker Content Management System"
[2]: http://lepton-cms.org "LEPTON CMS"
[3]: https://addons.phpmanufaktur.de/de/name/kittools.php
[4]: https://addons.phpmanufaktur.de/de/name/dwoo.php
[5]: https://addons.phpmanufaktur.de/de/name/dropletsextension.php
[6]: https://addons.phpmanufaktur.de/de/downloads.php
[7]: https://addons.phpmanufaktur.de/de/name/manufakturgallery.php
[8]: https://phpmanufaktur.de/support
