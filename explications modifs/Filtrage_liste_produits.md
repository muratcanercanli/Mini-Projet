# Filtrage par colonne sur la liste des produits

## Objectif

Permettre à l'utilisateur de filtrer les produits affichés sur la page d'accueil selon plusieurs critères indépendants ou combinés, directement depuis les en-têtes du tableau.

Cela répond à la consigne : **« moteur de recherche avancé, basé sur plusieurs critères (nom, catégorie, …), utilisables de manière indépendante ou combinée »**.

---

## Critères de filtrage disponibles

| Colonne     | Type de filtre       | Paramètre GET     |
|-------------|----------------------|-------------------|
| Nom         | Texte (recherche partielle, insensible à la casse) | `name`  |
| Catégorie   | Liste déroulante (toutes les catégories disponibles) | `category` |
| Stock       | Plage numérique (min / max) | `stockMin`, `stockMax` |
| Prix        | Plage numérique (min / max) | `priceMin`, `priceMax` |

Les filtres sont transmis via une requête **GET**, ce qui rend les résultats filtrés partageables par URL.

---

## Fichiers modifiés

### 1. `src/Repository/ProductRepository.php`

Ajout de la méthode `findByFilters(array $filters): array`.

Elle construit une requête Doctrine QueryBuilder dynamique :
- Pour le nom : `LIKE LOWER(:name)` avec `%…%` pour une recherche partielle insensible à la casse.
- Pour la catégorie : jointure sur `p.category` et filtre sur `c.id`.
- Pour prix et stock : clauses `>= :min` et `<= :max` ajoutées uniquement si la valeur est fournie.

Si aucun filtre n'est actif, la méthode retourne tous les produits (comportement identique à `findAll()`).

### 2. `src/Controller/HomeController.php`

- Injection de `Request` pour lire les paramètres GET.
- Injection de `CategoryRepository` pour récupérer la liste des catégories (nécessaire pour le `<select>` dans le formulaire de filtrage).
- Appel de `findByFilters($filters)` à la place de `findAll()`.
- Passage des variables `filters` et `categories` à la vue.

### 3. `templates/home/index.html.twig`

- Ajout d'une **ligne de filtres** (`<tr class="filter-row">`) dans le `<thead>` du tableau.
- Chaque cellule contient un champ de saisie correspondant à la colonne :
  - `<input type="text">` pour le nom.
  - `<select>` pour la catégorie (peuplé dynamiquement).
  - Deux `<input type="number">` (Min/Max) pour stock et prix.
- Un bouton **Filtrer** soumet le formulaire (méthode GET).
- Un lien **Réinitialiser** renvoie sur la page sans paramètres.
- Bandeau d'information quand des filtres sont actifs (nombre de résultats + lien d'effacement).
- Message adapté dans le corps du tableau si aucun produit ne correspond aux filtres.

### 4. `assets/styles/app.css`

Ajout des styles pour :
- `.filter-row th` — fond légèrement coloré pour distinguer la ligne de filtres.
- `.filter-input` — champs de saisie compacts avec focus stylisé.
- `.filter-range` — disposition côte-à-côte pour les champs min/max.
- `.filter-active-info` — bandeau bleu indiquant les filtres actifs.
- `.btn-sm` — variante de bouton plus petite pour l'espace réduit du tableau.

---

## Fonctionnement

1. L'utilisateur remplit un ou plusieurs champs dans la ligne de filtres et clique sur **Filtrer**.
2. Le formulaire soumet une requête GET sur `/` avec les paramètres renseignés.
3. Le contrôleur lit les paramètres, les passe au repository qui construit la requête SQL filtrée.
4. La vue affiche les résultats et montre un bandeau avec le nombre de résultats trouvés.
5. Un clic sur **Réinitialiser** ou **✕ Effacer les filtres** revient à la liste complète.
