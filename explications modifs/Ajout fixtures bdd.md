# Modifications — Script de fixtures (base de données)

## Packages installés

| Package | Version | Rôle |
|---|---|---|
| `doctrine/doctrine-fixtures-bundle` | ^4.3 | Intégration des fixtures dans Symfony (`php bin/console doctrine:fixtures:load`) |
| `fakerphp/faker` | ^1.24 | Génération de données aléatoires réalistes (adresses, téléphones, dates…) |

Installés en **dev** uniquement (`composer require --dev`) : ils ne sont pas embarqués en production.

---

## Fichier créé

### `src/DataFixtures/AppFixtures.php`

Classe unique qui peuple toute la base d'un seul appel. Structure :

#### 1. Catégories (3)

| Nom | Description |
|---|---|
| Voiture | Véhicules particuliers neufs et d'occasion |
| Moto | Motos, scooters et deux-roues motorisés |
| Utilitaire | Fourgonnettes, vans et utilitaires légers |

#### 2. Utilisateurs (7)

- **1 administrateur** (`admin@motostore.fr` / `admin1234`) avec le rôle `ROLE_ADMIN`.
- **6 vendeurs** avec des noms/prénoms fixes et le mot de passe `password123`.
- Adresses et numéros de téléphone générés par Faker (`fr_FR`).
- Les mots de passe sont **hashés** via `UserPasswordHasherInterface` (pas de mot de passe en clair en base).

#### 3. Produits (32 véhicules)

| Catégorie | Nb | Exemples |
|---|---|---|
| Voiture | 12 | Renault Clio, VW Golf, Kia Niro EV, Opel Corsa-e… |
| Moto | 11 | Honda CB500F, Yamaha MT-07, BMW R1250 GS, Ducati Monster… |
| Utilitaire | 9 | Renault Kangoo, VW Transporter T6.1, Nissan Townstar EV… |

Chaque produit a :
- **Nom** : modèle réel avec motorisation
- **Description** : texte détaillé (>100 chars) pour tester la troncature en page d'accueil
- **Prix** : en euros entiers, cohérent avec le marché réel
- **stock** et **stockMin** : volontairement variés pour déclencher les colorations du tableau

#### Répartition des états de stock

| État | Condition | Nb produits |
|---|---|---|
| Rupture (rouge) | `stock == 0` | 6 |
| Stock bas (orange) | `0 < stock <= stockMin` | 3 |
| Normal | `stock > stockMin` | 23 |

---

## Comment utiliser

```bash
# 1. S'assurer que la base de données existe et que les migrations sont à jour
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction

# 2. Charger les fixtures (⚠️ purge la base existante)
php bin/console doctrine:fixtures:load

# Pour ajouter sans purger (append)
php bin/console doctrine:fixtures:load --append
```

> **Attention** : `doctrine:fixtures:load` supprime et recharge toutes les données par défaut.  
> Utiliser `--append` pour conserver les données existantes.
