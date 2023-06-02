IMTP Boost Based theme
==

Theme pour la "Pédagothèque Numérique" - IMT.

[![Moodle plugin CI for Moodle 3.9 and 3.10](https://github.com/call-learning/moodle-theme_imtpn/actions/workflows/main.yml/badge.svg)](https://github.com/call-learning/moodle-theme_imtpn/actions/workflows/main.yml)

Installation
==

A rajouter dans config.php, si on veut que les scripts js personnalisés marchent correctement:
    
    $CFG->customscripts = dirname(__FILE__) . '/theme/imtpn/customscripts/';

Pour les tests behat:
    
    $CFG->behat_extraallowedsettings = ['customscripts'];

Images
==

Les images viennent principalement de:

* Unsplash : https://unsplash.com/ (https://unsplash.com/license)
* Pexels : https://www.pexels.com/fr-fr/license


Login and Shibolleth
==

Si le module d'authentification authsettingshibboleth est activé, on va afficher une image qui correspond
à celle chargée dans le plugin d'authentification et on l'affiche en grand format.


Dépendances
==

Dépendances sur les blocks:

* https://github.com/call-learning/moodle-block_featured_courses/ (page de garde)
* https://github.com/call-learning/moodle-block_mcms/ (page de themes de catalogue de cours et page de garde)
* https://github.com/call-learning/moodle-block_rss_thumbnails/ 
* https://github.com/call-learning/moodle-block_forum_feed/ (page de tableau de bord)
* https://github.com/call-learning/moodle-block_enhanced_myoverview/ (page de tableau de bord/ enseignant)
* https://github.com/call-learning/moodle-block_thumblinks_action/ (page de themes de catalogue de cours) 
* https://github.com/call-learning/moodle-block_sharing_cart/tree/resourcelibrary_integration (integration avec bibliothèque)

Ensuite il nous faudra les modules locaux:

* https://github.com/call-learning/moodle-local_resourcelibrary/ (catalogue)
* https://github.com/call-learning/moodle-local_syllabus/ (syllabus)

Un module d'authentification modifié (pour le syllabus):

* https://github.com/call-learning/moodle-enrol_apply/tree/imtpn_fixes

Et finalement le thème:

* https://github.com/call-learning/moodle-theme_clboost/ (theme de base, theme outil)
* https://github.com/call-learning/moodle-theme_imtpn/ (theme principal imtpn)
* https://github.com/call-learning/moodle-theme_imtpn_lille/ (theme dérivé, spécifique à une école) 
