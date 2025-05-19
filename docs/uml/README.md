# Documentation UML - Site de Camping

Ce répertoire contient les diagrammes UML pour le projet Site de Camping. Les diagrammes sont créés en utilisant PlantUML, un outil qui permet de créer des diagrammes UML en utilisant une syntaxe basée sur du texte.

## Diagrammes Disponibles

1. `class-diagram.puml` - Diagramme de classes montrant les relations entre les entités du système
2. `use-case-diagram.puml` - Diagramme des cas d'utilisation montrant les interactions possibles
3. `sequence-diagram.puml` - Diagramme de séquence illustrant le processus de réservation

## Comment Visualiser les Diagrammes

Il existe plusieurs façons de visualiser ces diagrammes PlantUML :

### 1. Utilisation de VS Code

1. Installez l'extension "PlantUML" dans VS Code
2. Ouvrez n'importe quel fichier .puml
3. Appuyez sur Alt+D pour prévisualiser le diagramme

### 2. Utilisation du Serveur PlantUML en Ligne

1. Allez sur [PlantUML Online Server](http://www.plantuml.com/plantuml/uml/)
2. Copiez le contenu d'un fichier .puml
3. Collez-le dans l'éditeur
4. Le diagramme sera généré automatiquement

### 3. Utilisation de la Ligne de Commande PlantUML

1. Installez PlantUML (nécessite Java) :
   ```bash
   # Sur Windows avec Chocolatey
   choco install plantuml

   # Sur macOS avec Homebrew
   brew install plantuml
   ```

2. Générez les diagrammes :
   ```bash
   plantuml *.puml
   ```

## Structure des Diagrammes

### Diagramme de Classes

Le diagramme de classes montre les principales entités du système et leurs relations :

- Utilisateur : Représente les utilisateurs du système avec leurs informations personnelles
- Evenement : Représente les événements de camping avec leurs détails
- Reservation : Représente les réservations d'événements
- Avis : Représente les avis des utilisateurs sur les événements
- Equipement : Représente l'équipement de camping associé aux événements

### Diagramme des Cas d'Utilisation

Le diagramme des cas d'utilisation montre les interactions possibles avec le système :

- Inscription et connexion
- Création et réservation d'événements
- Gestion des réservations
- Système de notation et d'avis
- Gestion des équipements
- Administration du système

### Diagramme de Séquence

Le diagramme de séquence illustre le processus de réservation d'un événement :

- Consultation des détails de l'événement
- Vérification de la disponibilité
- Création de la réservation
- Confirmation et notification

## Ajout de Nouveaux Diagrammes

Pour ajouter de nouveaux diagrammes :

1. Créez un nouveau fichier .puml dans ce répertoire
2. Utilisez la syntaxe PlantUML pour définir votre diagramme
3. Suivez la même convention de nommage : `[type-de-diagramme]-diagram.puml`
4. Mettez à jour ce README pour inclure les informations sur le nouveau diagramme

## Bonnes Pratiques

1. Gardez les diagrammes clairs et lisibles
2. Utilisez des noms significatifs pour les classes et les relations
3. Documentez les relations complexes avec des commentaires
4. Maintenez les diagrammes à jour avec les changements de code
5. Utilisez un style cohérent à travers tous les diagrammes 