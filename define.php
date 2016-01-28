<?php
/*
Copyright © <2011> <singler> <julien>

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 US
*/
define("DEBUG", 1);
define("PATH_CONTROLLERS", "application/controllers/");
define("PATH_VIEWS", "application/views/");
define("PATH_MODELS", "application/models/");
define("PATH_LIB", "library/");
define("PATH_LANG", "application/language/");

// utilisation d'EASYJQUERY
define("EASYJQUERY", 1);
// Utilisation du bootstrap de twitter
define("BOOTSTRAP", 1);
define("PATH_BOOTSTRAP_CSS", "bootstrap/");
define("PATH_BOOTSTRAP_JS", "bootstrap/");

// Paths pour les ressources
define("IMAGES", "/public/images");
define("CSS", "/public/css");
define("JS", "/public/js");

// Salt pour le 
define("SALT", "dblink");
define("CAPTCHME_PUBLIC", "c617f88a2c8ab4c30f32b3310537c4cafeff6c683bb017468e4fe4ba7a166c9d");
define("CAPTCHME_PRIVATE", "2d9ea846e49207a9cc2d93b874966edc50b4cb645da4f19292cad762b5e7c8aa");
define("CAPTCHME_AUTH", "16190c4cb021b810b2359d2c784ece3167749b784f4aa248ac186cf447fc05cc");

// Include des define pour la bdd
include('define_base.php');
include('define_conf.php');

?>
