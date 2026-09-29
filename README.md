<p align="center">
  <img src="frontend/public/images/blissim-logo.svg" alt="Blissim" width="180">
</p>

<h1 align="center">Catalogue de produits</h1>

<p align="center">
  Test technique · Catalogue, fiches produits et commentaires<br>
  PHP 8.3 · Symfony 7.4 · React · PostgreSQL 16 · Docker
</p>

<p align="center">
  <a href="#démarrage">Démarrage</a> ·
  <a href="#partie-sql">SQL</a> ·
  <a href="#architecture">Architecture</a> ·
  <a href="#tests-et-qualité">Tests</a>
</p>

## Le projet

Une application pour parcourir un catalogue, consulter une fiche produit et publier, modifier ou supprimer des commentaires via des formulaires HTML React. Les commentaires sont conservés dans PostgreSQL avec PDO, sans ORM.

- Catalogue beauté, parfums et soins, avec recherche par titre.
- Fiches produits avec image, description et prix en USD.
- Validation des formulaires et confirmation avant suppression.
- États de chargement, messages d’erreur et possibilité de réessayer.
- Documentation interactive de l’API avec Swagger.

> **Source des produits :** FakeStoreAPI étant indisponible lors du développement, [DummyJSON](https://dummyjson.com/docs/products) a été retenu comme alternative. Le catalogue est volontairement limité aux catégories beauté, parfums et soins pour rester dans l’univers Blissim. Le fournisseur est isolé derrière `ProductApiClientInterface` ; revenir à FakeStoreAPI nécessite un adaptateur à son format de réponse.

## Démarrage

### Prérequis

- Docker avec Docker Compose v2, démarré.
- Ports `8001` et `5432` disponibles.
- Accès à Internet pour télécharger les images, les dépendances et les produits.

PHP, Composer et Node.js sont fournis par les conteneurs.

### Installation

Depuis la racine du dépôt, copier la configuration :

```bash
cp .env.example .env
```

Sous PowerShell : `Copy-Item .env.example .env`.

Puis lancer l’application :

```bash
docker compose up -d --build
```

| Accès | Adresse |
| --- | --- |
| Application | [localhost:8001](http://localhost:8001) |
| Swagger | [localhost:8001/api/doc](http://localhost:8001/api/doc) |
| Contrat OpenAPI | [JSON](http://localhost:8001/api/doc.json) · [YAML](http://localhost:8001/api/doc.yaml) |

Le premier lancement initialise la base avec [database/blissim.sql](database/blissim.sql). Les données persistent dans un volume Docker. Les lancements suivants conservent la base existante.

Pour changer le port de l’application, modifier `APP_PORT` dans `.env`. Pour arrêter les services en conservant les données :

```bash
docker compose down
```

### Base de données

Valeurs locales par défaut, modifiables dans `.env` :

| Paramètre | Valeur |
| --- | --- |
| Système | PostgreSQL 16 |
| Hôte depuis les conteneurs | `postgres` |
| Hôte depuis la machine | `localhost:5432` |
| Base | `blissim` |
| Utilisateur | `blissim` |
| Mot de passe | `blissim` |

Adminer est disponible en option :

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Ouvrir [localhost:8081](http://localhost:8081), sélectionner PostgreSQL et utiliser `postgres` comme serveur.

## Partie SQL

Le fichier **[database/blissim.sql](database/blissim.sql)** contient le modèle e-commerce, les contraintes, les données de test et les deux requêtes demandées. Il s’exécute sur PostgreSQL dans une base vide.


Les lignes de commande conservent le prix unitaire au moment de l’achat. Les clés étrangères assurent les relations ; les contraintes encadrent les quantités, les prix et les statuts.

| Requête | Convention retenue |
| --- | --- |
| Clients ayant commandé le produit `1` | Tous les statuts ; chaque client apparaît une fois, même en cas d’achats répétés. Les homonymes sont conservés. |
| Produits vendus sur les 7 derniers jours | Commandes payées ou expédiées ; bornes inclusives ; commandes futures exclues ; tri par quantité décroissante. |

Le seed couvre notamment les achats répétés, les homonymes, les commandes anciennes, annulées, en attente et futures. Les résultats attendus sont commentés dans le fichier SQL.

La table `comments` sert à l’application. Ses identifiants produits désignent les produits de l’API externe : ils sont indépendants de la table e-commerce `products`.

## Architecture

```text
backend/
  src/Controller/     Routes HTTP
  src/UseCase/        Coordination des opérations et DTO de sortie
  src/Manager/        Gestion des produits et des commentaires
  src/Repository/     Requêtes PDO
  src/Service/        Client HTTP et connexion à la base
  src/Dto/            Entrées validées et sorties de l’API
  src/Entity/         Objets typés
  src/Http/           Réponses JSON et gestion des exceptions
  tests/              Tests unitaires et fonctionnels
frontend/src/         Pages, composants React et CSS
database/blissim.sql  Modèle, seed et requêtes SQL
docker/               Images, scripts de démarrage et configuration Nginx
```

Le backend interroge DummyJSON et valide ses réponses. Les commentaires sont lus et écrits via des requêtes PDO préparées. Les DTO d’entrée sont validés par Symfony ; les erreurs techniques sont journalisées avec Monolog et renvoyées au navigateur sans leurs détails internes.

### API

Toutes les routes utilisent le préfixe `/api/v1`.

| Méthode | Route | Action |
| --- | --- | --- |
| `GET` | `/products` | Lister les produits |
| `GET` | `/products/{id}` | Consulter un produit |
| `GET` | `/products/{id}/comments` | Lister ses commentaires |
| `POST` | `/comments` | Créer un commentaire |
| `PUT` | `/comments/{id}` | Modifier le nom et le contenu |
| `DELETE` | `/comments/{id}` | Supprimer un commentaire |

Exemple de corps pour la création :

```json
{
  "product_id": 1,
  "author": "Camille",
  "content": "Mon avis sur ce produit."
}
```

Les réponses utilisent `data` en cas de succès et `error` en cas d’erreur. La création renvoie `201`, la suppression `204` sans corps. La définition complète est dans [backend/config/openapi.yaml](backend/config/openapi.yaml).

## Tests et qualité

Les tests automatisés portent sur le **backend** : CRUD avec PostgreSQL, validation, isolation des commentaires par produit, erreurs API/base et contrat HTTP. DummyJSON est simulé ; les tables de test sont temporaires et propres à chaque connexion.

Avec les conteneurs démarrés :

```bash
docker compose exec --user www-data -T backend composer test
docker compose exec --user www-data -T backend composer quality
docker compose exec --user www-data -T backend php bin/console lint:container
docker compose exec --user www-data -T backend php bin/console lint:yaml config
docker compose exec -T frontend npm run build
```

`composer quality` lance PHPStan au niveau 6 et la vérification du style PSR-12. Le frontend est vérifié par compilation et par un parcours manuel : catalogue, recherche, fiche, ajout, rechargement, modification et suppression d’un commentaire.

Avec GNU Make, `make check` regroupe ces contrôles. `make help` affiche les autres raccourcis.

## Développement

Les sources sont montées dans les conteneurs. Le frontend est servi par Vite derrière Nginx. Son démarrage synchronise les dépendances avec `npm ci` lorsque les fichiers de dépendances changent.

Après une modification des dépendances PHP sur un volume existant :

```bash
docker compose exec -T backend composer install --no-interaction --prefer-dist
docker compose exec -T backend php bin/console assets:install public
docker compose exec --user www-data -T backend php bin/console cache:clear
```

Pour diagnostiquer un problème :

```bash
docker compose logs --tail=50 backend frontend nginx
docker compose exec -T backend tail -n 50 var/log/dev.log
```

## Périmètre

Cette application est une démonstration sans authentification : tous les visiteurs peuvent modifier ou supprimer les commentaires. L’ajout nécessite de vérifier le produit auprès de DummyJSON ; les opérations sur les commentaires existants restent indépendantes de cette API côté backend. Aucun catalogue de secours n’est activé en cas de panne.

La configuration Docker est destinée au développement et à l’évaluation locale. Le logo et les favicons proviennent de [blissim.fr](https://blissim.fr/).
