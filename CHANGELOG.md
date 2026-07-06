# Changelog — Thème IMT Pédagothèque Numérique

## Version 4.1.2 — 6 juillet 2026

### Corrections
- **Contraste des couleurs corrigé** : les zones avec fond coloré et texte étaient difficilement lisibles (texte foncé sur fond clair). Le contraste a été ajusté pour une meilleure lisibilité.
- **Accessibilité améliorée** : des attributs ARIA manquants ont été ajoutés aux menus déroulants et images pour une meilleure compatibilité avec les technologies d'accessibilité (lecteurs d'écran).
- **Formulaire de connexion** : correction d'un problème d'affichage pour la checkbox "Se souvenir de mon nom d'utilisateur".
- **Simplification du code** : mise à jour du code interne pour améliorer la maintenance future (sans impact sur l'apparence).

---

## Version 4.1.1 — 25 septembre 2025

### Nouvelles fonctionnalités
- **Modification du logo** : il est maintenant possible de changer le logo affiché dans l'en-tête du site via les paramètres d'administration. Un logo spécifique peut être défini pour les fonds sombres.
- **Gestion des images de logos** : un nouveau système permet d'uploader et servir les images du logo dans les paramètres du thème.

### Corrections
- **Contraste des couleurs** : réglage des conflits entre les couleurs d'arrière-plan et les textes (zones `bg-secondary` avec texte sombre, sélecteurs désactivés).
- **Accessibilité** : ajout des attributs `alt` sur les images des blocs MCMS pour les lecteurs d'écran.

---

## Version 4.1.0 — 28 septembre 2025

### Nouvelles fonctionnalités
- **Pages de profil simplifiées** : option pour retirer les composants non essentiels de la page de profil, selon la configuration choisie par l'administrateur.
- **Gestion avancée des groupes** : améliorations du formulaire de création/édition de groupe et de la table d'affichage des groupes.
- **Catalogue de syllabus** : tests et corrections sur le catalogue de syllabus (Behat).

### Corrections
- Améliorations internes du code (standards de codage, structure des classes, optimisation des scripts) — n'affecte pas l'apparence ou le comportement pour les utilisateurs finaux.

---

## Notes

- Le thème est compatible Moodle 3.5+.
- Les mises à jour de version sont gérées automatiquement via le fichier `version.php`.