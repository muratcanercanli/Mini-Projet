# CRUD Produits — Création, lecture, modification, suppression

## Fichiers créés

### `src/Form/ProductFormType.php`
Formulaire Symfony pour créer ou modifier un produit.

| Champ | Type Symfony | Obligatoire | Contraintes |
|---|---|---|---|
| `name` | `TextType` | Oui | NotBlank, Length(max:255) |
| `description` | `TextareaType` | Oui | NotBlank, Length(max:255) |
| `category` | `EntityType` (Category) | Oui | NotBlank |
| `price` | `IntegerType` | Oui | NotBlank, Positive |
| `stock` | `IntegerType` | Oui | NotBlank, PositiveOrZero |
| `stockMin` | `IntegerType` | **Non** | PositiveOrZero |

Les champs `seller`, `creationDate` et `modificationDate` ne sont **pas** dans le formulaire — ils sont renseignés automatiquement dans le contrôleur.

---

### `src/Controller/ProductController.php`
4 actions CRUD, toutes préfixées `/product/…`.

| Route | Méthode HTTP | Nom Symfony | Accès |
|---|---|---|---|
| `/product/{id}` | GET | `app_product_show` | Public |
| `/product/new` | GET + POST | `app_product_new` | `ROLE_USER` |
| `/product/{id}/edit` | GET + POST | `app_product_edit` | `ROLE_USER` + propriétaire ou admin |
| `/product/{id}/delete` | POST | `app_product_delete` | `ROLE_USER` + propriétaire ou admin |

**Règle de propriété** (edit / delete) : si l'utilisateur connecté n'est pas le vendeur du produit **et** n'est pas admin (`ROLE_ADMIN`), une exception `AccessDeniedException` est levée (→ page 403).

**Token CSRF** : le formulaire de suppression envoie un token `delete{id}` vérifié côté contrôleur via `isCsrfTokenValid()`.

---

### `templates/product/_form.html.twig`
Partial Twig partagé entre `new.html.twig` et `edit.html.twig`. Contient les blocs de champs du formulaire (`.form-group`, `.form-row-2`). Inclus avec `{% include 'product/_form.html.twig' %}`.

### `templates/product/new.html.twig`
Page de création d'annonce. Étend `base.html.twig`, inclut le partial `_form.html.twig`.

### `templates/product/edit.html.twig`
Page de modification d'annonce. Même structure que `new.html.twig`, affiche en sous-titre le nom du produit en cours de modification.

### `templates/product/show.html.twig`
Fiche détail d'un produit. Affiche :
- Nom, catégorie (badge), prix
- Badge de stock coloré : vert / orange / rouge selon le niveau
- Description complète
- Grille de métadonnées : vendeur (nom + e-mail), stock minimum, dates de création et de modification
- Boutons **Modifier** et **Supprimer** visibles uniquement pour le propriétaire du produit ou un admin

---

## Fichiers modifiés

### `templates/home/index.html.twig`
- Chaque ligne du tableau est maintenant cliquable (`onclick`) et redirige vers la fiche `show`.
- Le nom du produit est un lien `<a>` stylisé (`product-name-link`).
- Ajout d'un bouton **"+ Publier une annonce"** dans le header, visible uniquement pour les utilisateurs connectés (`ROLE_USER`).
- Affichage des messages flash `success` / `error`.

### `assets/styles/app.css`
Refonte complète pour centraliser tous les styles. Nouveaux blocs ajoutés :
- `.flash-success` / `.flash-error` — messages flash
- `.form-card`, `.form-card-title`, `.form-card-subtitle` — carte formulaire
- `.form-row-2`, `.form-group`, `.form-hint` — structure du formulaire
- `.btn-submit`, `.btn-secondary`, `.btn-back` — variantes de boutons
- `.product-show`, `.product-show-header`, `.product-price` — fiche détail
- `.badge`, `.badge-category`, `.badge-stock-ok/low/out` — badges colorés
- `.product-meta` — grille dl/dt/dd pour les métadonnées
- `.product-actions` — zone des boutons d'action

---

## Sécurité

La sécurité est assurée à deux niveaux :

1. **`security.yaml` (access_control)** — bloque les routes avant même d'atteindre le contrôleur :
   ```yaml
   - { path: ^/product/new,          roles: ROLE_USER }
   - { path: ^/product/\d+/edit,     roles: ROLE_USER }
   - { path: ^/product/\d+/delete,   roles: ROLE_USER }
   ```

2. **`#[IsGranted('ROLE_USER')]`** dans le contrôleur — redondant mais défensif.

3. **Vérification de propriété** dans `edit` et `delete` — empêche un utilisateur de modifier/supprimer le produit d'un autre vendeur.

4. **Token CSRF** sur la suppression — empêche les attaques CSRF sur l'action destructive.
