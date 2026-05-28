# Modifications — Liste des articles en vente (page d'accueil)

## Fichiers modifiés

### `src/Controller/HomeController.php`
- Injection de `ProductRepository` en paramètre de la méthode `index()`.
- Appel de `findAll()` pour récupérer tous les produits en base.
- Passage de la variable `products` au template Twig.

### `templates/home/index.html.twig`
- Ajout d'un header avec le nom de l'app et la nav (connexion/déconnexion).
- Affichage de la liste des produits sous forme de tableau avec les colonnes :
  - **Nom**, **Catégorie**, **Stock**, **Prix**, **Description**
- La description est tronquée à 100 caractères avec `…` si elle dépasse.
- Gestion des états de stock via une classe CSS sur chaque `<tr>` :
  - `stock-out` (fond rouge) → `stock == 0` (rupture de stock)
  - `stock-low` (fond orange) → `stock > 0 && stock <= stockMin` (stock bas)
- Affichage d'un message si aucun produit n'est disponible.
- Légende visuelle expliquant les codes couleur.

### `assets/styles/app.css`
- Remplacement du fond bleu ciel par un gris clair neutre (`#f3f4f6`).
- Styles du layout principal (`.home-container`, `.home-header`, `.home-nav`).
- Styles des boutons (`.btn`, `.btn-primary`, `.btn-danger`) et badge admin.
- Styles de la légende de stock (`.stock-legend`, `.legend-low`, `.legend-out`).
- Styles du tableau produits (`.product-table`) avec :
  - `.stock-low` → `background-color: #fed7aa` (orange doux)
  - `.stock-out` → `background-color: #fecaca` (rouge doux)
  - Effet hover léger sur les lignes.

## Logique de stock bas

Le seuil de stock bas est piloté par le champ `stockMin` de l'entité `Product` :

| Condition | Affichage |
|---|---|
| `stock == 0` | Fond **rouge** (rupture) |
| `stock > 0` et `stock <= stockMin` | Fond **orange** (stock bas) |
| Sinon | Fond normal |

> Si `stockMin` est `null` pour un produit, la règle "stock bas" ne s'applique pas.
