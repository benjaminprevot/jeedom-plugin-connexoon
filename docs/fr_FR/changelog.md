---
title: Changelog en Français
description: Changelog du plugin Jeedom permettant de piloter la box Connexoon
lang: fr_FR
type: changelog
---

# Changelog en Français

## [Beta] - 2023-07-28

### CHANGEMENTS IMPACTANTS !!!

- Les volets roulants créés initialement avec l'ancien système Somfy sont supprimés

### Fonctionnalités

- Les volets roulants sont chargés et les actions de bases (`ouvrir`, `fermer`, `stopper` et `identifier`) sont disponibles
- Les widgets sont disponibles sur le dashboard
- Le widget prend en compte le type d'équipement
- Création d'un démon pour écouter les évènements toutes les secondes
- La mise à jour de la visibilité d'un objet est possible
- L'écran de configuration a été amélioré

### Corrections

- Correction du filtre appliqué à l'API Somfy pour lister les volets roulants
- Utilisation de Fontawesome pour le widget

### Technique

- Réécriture de la librairie Somfy

[Beta]: https://github.com/benjaminprevot/jeedom-plugin-connexoon/tree/beta
