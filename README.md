IMTP Boost Based theme
==

This is a theme for the "Pédagothèque Numérique" for IMT.


Installation
==

A rajouter dans config.php, si on veut que le mur pédagogique marche correctement:
    
    $CFG->customscripts = dirname(__FILE__) . '/theme/imtpn/customscripts/';

Pour les tests behat:
    
    $CFG->behat_extraallowedsettings = ['customscripts'];

Logos
==

Images
==

Images are mostly coming from:

* Unsplash : https://unsplash.com/ (https://unsplash.com/license)
* Pexels : https://www.pexels.com/fr-fr/license


Login and Shibolleth
==

Si le module d'authentification authsettingshibboleth est activé, on va afficher une image qui correspond
à celle chargée dans le plugin d'authentification et on l'affiche en grand format.

Templates
==

