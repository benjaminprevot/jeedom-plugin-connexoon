---
title: Connexoon
description: Documentation du plugin Jeedom permettant de piloter la box Connexoon
lang: fr_FR
---

## Description

Ce plugin Jeedom permet de piloter les volets roulants à partir de la box [Connexoon de Somfy](https://www.somfy.fr/produits/1811429/connexoon).

Il utilise l'API mise à disposition [https://developer.somfy.com/](https://developer.somfy.com/).

## Configuration

Pour permettre le bon fonctionnement du plugin, une configuration préalable doit être faite dans le portail du développeur Somfy.

Les informations générés devront ensuite être recopiés dans les paramètres du plugin.

### Portail du développeur

Dans un premier, il faut se connecter à [https://developer.somfy.com/](https://developer.somfy.com/).

Si vous disposez déjà d'un compte sur ce portail, vous pouvez vous rendre directement au paragraphe [Création d'une application](#création-d-une-application)

#### Création d'un compte

En haut à droite de l'écran, cliquer sur _Log in_.

#### Création d'une application

Une fois connecté au portail du développeur, cliquer dans le menu haut sur _My Apps_.

Vous voyez la liste des applications créées.

Cliquez sur le bouton _Add a new App_.

Renseignez les informations demandées :
- _App Name_ : Nom que vous souhaitez donner à votre application
- _Callback URL_ : URL vers votre box Jeedom (même si elle n'est pas accessible d'un réseau externe)
- _App Description_ : Une description de votre application
