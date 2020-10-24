---
title: Documentation en Français
description: Documentation du plugin Jeedom permettant de piloter la box Connexoon
lang: fr_FR
type: documentation
---

# Documentation en Français

## Description

Ce plugin Jeedom permet de piloter les volets roulants à partir de la box [Connexoon de Somfy](https://www.somfy.fr/produits/1811429/connexoon).

Il utilise l'API mise à disposition [https://developer.somfy.com/](https://developer.somfy.com/).

## Configuration

Pour permettre le bon fonctionnement du plugin, une configuration préalable doit être faite dans le portail du développeur Somfy.

Les informations générées devront ensuite être recopiées dans les paramètres du plugin.

Si plusieurs URLs permettent d'accéder à l'interface Jeedom, il faudra mettre en place une configuration par URL en se connectant sur chacune d'elle.

### Paramètre du plugin

Il faut tout d'abord vous connectez sur l'écran de configuration du plugin afin de récupérer l'URL permettant de valider l'authentification.

S'il s'agit de la première configuration, il suffit de cliquer sur le bouton _Ajouter la configuration courante_.

Un formulaire est alors affiché avec un champ non modifiable _Callback URL_.

Conservez la valeur de ce champ pour la suite de la configuration.

### Portail du développeur

Il faut maintenant se connecter à [https://developer.somfy.com/](https://developer.somfy.com/).

Si vous disposez déjà d'un compte sur ce portail, vous pouvez vous rendre directement au paragraphe [Création d'une application](#création-dune-application).

#### Création d'un compte

En haut à droite de l'écran, cliquer sur _Log in_.

![Lien Log in](login-link.png)

__TODO__ : Ajouter les captures pour la création de compte

#### Création d'une application

Une fois connecté au portail du développeur, cliquer dans le menu haut sur _My Apps_.

![Lien My Apps](my-apps-link.png)

Vous voyez la liste des applications créées, elle est vide si vous n'en avez pas encore mis en place.

Cliquez sur le bouton _Add a new App_.

![Lien Add a new App](add-a-new-app-link.png)

Renseignez les informations demandées :
- _App Name_ : Nom que vous souhaitez donner à votre application
- _Callback URL_ : URL vers votre box Jeedom, il s'agit de la valeur copiée précédemment dans l'écran de configuration du plugin
- _App Description_ : Une description de votre application

![Formulaire Add a new App](add-a-new-app-form.png)

Cliquez sur le bouton _Create App_.

![Lien Create App](create-app-link.png)

Si tout s'est déroulé correctement, un message indique que l'application est correctement créée et elle apparaît alors dans la liste.

![Application créée avec succès](app-creation-successful.png)

Cliquez ensuite sur la ligne correspondant à votre application.

Les informations de l'application sont affichées, en particulier les clés permettant d'utiliser l'API.

![Détails de l'application](app-details.png)

Notez les valeurs des _Consumer Key_ et _Consumer Secret_.

Elles seront utilisées plus tard pour configurer le plugin dans l'interface Jeedom.

### Plugin

Enfin, il faut renseigner les informations lui permettant au plugin d'utiliser l'API Somfy.

Pour cela, il suffit de renseigner les valeurs de _Consumer Key_ et _Consumer Secret_ notées précédemment.

Ensuite, cliquez sur le bouton _Sauvegarder_.

Une fenêtre s'ouvre vous demandant de reseigner les informations de connexion au portail Somfy puis d'autoriser le plugin à utiliser l'API.

Si vous venez de créer l'application dans le portail Somfy, il est possible qu'un délai soit nécessaire avant qu'elle ne soit disponible.

En cas d'erreur d'authentification, attendez quelques minutes et recommencez l'enregistrement des paramètres.

## Gestion de plusieurs URLs

Le plugin permet de gérer plusieurs accès à votre box Jeedom.

Pour des raisons de sécurité, il faut créer une application dans le portail Somfy par URL d'accès à la box.

La configuration se fait ensuite en se connectant à chaque URL.
