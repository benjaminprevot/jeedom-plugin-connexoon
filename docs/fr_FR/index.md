---
title: Documentation en Français
description: Documentation du plugin Jeedom permettant de piloter la box Connexoon
lang: fr_FR
type: documentation
---

# Documentation en Français

___Le plugin et sa documentation sont en cours de réécriture suite aux changements apportés par Somfy.___

## Description

Ce plugin Jeedom permet de piloter les volets roulants à partir de la box [Connexoon de Somfy](https://www.somfy.fr/produits/1811429/connexoon).

Il utilise l'API local [Somfy TaHoma Developer Mode](https://github.com/Somfy-Developer/Somfy-TaHoma-Developer-Mode).

## Configuration

Pour permettre le bon fonctionnement du plugin, l'activation du mode développeur doit être faite dans le portail, comme décrit dans cette documentation : [Somfy TaHoma Developer Mode - Getting started](https://github.com/Somfy-Developer/Somfy-TaHoma-Developer-Mode#getting-started)

### Paramètrage du plugin

Il faut ensuite renseigner les informations suivantes :

| Paramètre    | Description                                          |
| ------------ | ---------------------------------------------------- |
| IP Connexoon | Adresse IP de la box (par ex, 192.168.1.10)          |
| Code PIN     | Code PIN de la box, disponible dans le portail Somfy |
| E-mail       | Adresse permettant de se connecter au portail Somfy  |
| Mot de passe | Mot de passe du portail Somfy                        |
