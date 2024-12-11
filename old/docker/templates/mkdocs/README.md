## mkdocs

* pour les instances mkdocs, pour que la connexion directe en ssh fonctionne : 


dans le container, créer un fichier /bin/bash avec le contenu suivant : 
#!/bin/sh
exec /bin/busybox ash "$@"

modifier les droits
chmod 755 /bin/bash

* Correctif du template
/usr/lib/python3.6/site-packages/mkdocs_material-4.0.1-py3.6.egg/material/partials/header.html

laisser l'attribut href du lien avec l'icone, mais retirer la valeur (="...")