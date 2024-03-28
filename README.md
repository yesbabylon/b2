# Instance init logic



## .env file

USERNAME=www.example.com
PASSWORD=arbitrary_password
APP_USERNAME=root
APP_PASSWORD=other_password



[TEMPLATE]



Note : il faudra générer le `.env` en fournissant 3 params USERNAME(FQDN), PASSWORD, APP_PASSWORD


## Processus d'Initialisation de eQualPress
Le script bash à lancé est ``init.bash``. Si c'est pour du developpement ou du testing lancer ``test_eQulPress_multi_instance_setup.bash``

### init.bash
1. **Chargement des Variables d'Environnement depuis un fichier .env**
   - Téléchargement du fichier `.env` depuis le repo GitHub `yesbabylon/b2` dans le répertoire courant, si le fichier n'existe pas.
   - Initialisation des variables d'environnement à partir du fichier `.env`.

2. **Création de l'Utilisateur:**
   - Création d'un nouvel utilisateur avec le nom de domaine spécifié dans le fichier `.env`.
   - Attribution d'un mot de passe à l'utilisateur.
   - Création de répertoires pour sauvegarde et réplication.
   - Copie du répertoire de status pour le nouvel utilisateur.
   - Configuration du répertoire de l'utilisateur pour l'accès FTP.
   - Création d'un répertoire pour le commutateur de maintenance.
   - Attribution des permissions en écriture au groupe sur le répertoire de l'utilisateur.
   - Redémarrage du service SFTP pour permettre la connexion FTP.
   - Ajout de l'utilisateur au groupe Docker.
   - Définition de `ssh-login` comme shell pour le compte utilisateur.
   - Ajout des configurations de domaine et de contact à `.env`.
   - Attribution des permissions d'exécution à un script d'initialisation spécifique.

3. **Vérification des Prérequis:**
   - Vérification de l'installation de Git.
   - Vérification de l'installation de Docker.

4. **Configuration des Variables pour Docker:**
   - Calcul du hash MD5 du nom d'utilisateur pour définir `DB_HOST`.
   - Renommage du service PHPMyAdmin avec le hash MD5 de l'utilisateur.
   - Calcul du nombre d'instances pour définir `DB_PORT`, `PHPMYADMIN_PORT` et `EQ_PORT`.

### equal.setup.bash
5. **Clonage de l'application eQual :**
   - **Clonage de `eQual Framework` :**
     - Téléchargement de l'application eQual depuis le référentiel GitHub `equalframework/equal`.
     - Téléchargement des fichiers de configuration depuis le référentiel GitHub `yesbabylon/b2`.
     - Renommage du fichier `index.php` en `equal.php` pour éviter les conflits avec WordPress.

   - **Remplacement des Fichiers de Configuration :**
     - Téléchargement des fichiers de configuration (`docker-compose.yml`, `config/config.json`, `public/assets/env/config.json`) depuis le référentiel GitHub `yesbabylon/b2`.
     - Remplacement du fichier `.htaccess` avec une version compatible.

   - **Remplacement des Placeholders dans les Fichiers de Configuration :**
     - Remplacement des placeholders dans les fichiers de configuration avec les valeurs appropriées extraites des variables d'environnement et du fichier `.env`.

   - **Construction et Lancement des Conteneurs Docker :**
     - Construction des conteneurs Docker à l'aide de `docker-compose`.
     - Démarrage des conteneurs Docker.

   - **Clonage et Configuration de `Symbiose` :**    | si ``--with_sb`` ou ``-s``
     - Clonage de l'application Symbiose depuis le référentiel GitHub `yesbabylon/symbiose`.
     - Déplacement des répertoires `core` et `demo` dans le répertoire `packages`.
     - Suppression du répertoire `packages-core`.

   - **Initialisation de la Base de Données et du Package Core de eQual :**
     - Initialisation de la base de données eQual.
     - Attente de 5 secondes pour permettre l'initialisation de la base de données.
     - Initialisation du package core.
     - Attente de 15 secondes pour permettre l'initialisation de la base de données.

### equalpress.setup.bash  | si ``--with_wp`` ou ``-w``
6. **Installation de Wordpress dans eQual :**
   - **Renommage du Fichier PHP pour Éviter les Conflits :**
     - Le fichier `index.php` dans le répertoire public est renommé en `equal.php`. 
     - Cela est nécessaire pour éviter tout conflit lors de l'installation de WordPress.

   - **Téléchargement, Installation et Configuration de WordPress :**
     - Téléchargement du WP-CLI pour l'automatisation des tâches d'installation de WordPress.
     - Installation et configuration de WordPress avec les informations fournies.
     - Ajustement des permissions des fichiers pour correspondre aux normes de sécurité.


### Fichier `.env` actuel:
```js
USERNAME=equal.local
PASSWORD=arbitrary_password

APP_USERNAME=root
APP_PASSWORD=test

DB_NAME=equal

EQ_VERSION=dev-2.0

WP_VERSION=latest
WP_TITLE=eQualpress
WP_EMAIL=root@host.local
```
