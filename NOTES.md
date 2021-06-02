Implementation notes
===

Si le module d'authentification authsettingshibboleth est activé, on devrait pouvoir
piocher sur la page de login les informations relatives à chaque fournisseur
d'identité (à partir du setting: auth_shibboleth | organization_selection).

On peut récupérer les informations dans les champs description de chaque URL
donnée (https://wiki.shibboleth.net/confluence/display/SP3/UIInfoMetadataFilter)

Malheureusement ces champs ne sont pas actuellement utilisés sur toutes les instances IMT, donc
il faudra réviser cela lors des prochaines mises à jour.
