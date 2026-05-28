# Gestion du panier (Cart)

## Objectif

Permettre à un utilisateur connecté d'ajouter des articles à un panier, de modifier les quantités, de retirer des articles, et de valider sa commande. La validation réduit le stock des produits concernés.

---

## Modèle de données

### Entité `Cart`
Un panier appartient à un acheteur (`buyer`, relation ManyToOne → User). Deux champs pilotent son cycle de vie :
- `creationDate` / `modificationDate` — dates de création et dernière modification.
- `purchasedAt` (DateTime nullable) — `null` = panier actif, valorisé = commande validée. Cela permet de conserver l'historique des commandes sans table supplémentaire.

Un panier contient une collection de `CartItems` (OneToMany avec `cascade: ['remove']`, ce qui supprime automatiquement les lignes quand le panier est supprimé).

### Entité `CartItems`
Ligne du panier : références vers le `Cart` et le `Product`, plus la `quantity`.

### Corrections apportées à `Cart.php`
L'entité originale contenait deux bugs Doctrine :
- `mappedBy: 'cartId'` → corrigé en `mappedBy: 'cart'` (le nom de la propriété dans `CartItems`).
- Appels à `setCartId()` / `getCartId()` dans `addCartItem` / `removeCartItem` → corrigés en `setCart()` / `getCart()`.

### Ajouts sur `User.php`
Ajout de la relation inverse `carts` (OneToMany → Cart) pour compléter la relation bidirectionnelle.

### Migration
La migration `Version20260528093921` ajoute à la table `cart` :
- `buyer_id INT NULL` (FK → `user.id`)
- `purchased_at DATETIME NULL`

---

## Fichiers créés / modifiés

| Fichier | Rôle |
|---|---|
| `src/Entity/Cart.php` | Corrections de bugs + ajout `buyer`, `purchasedAt`, `getTotal()` |
| `src/Entity/User.php` | Ajout relation inverse `carts` |
| `src/Repository/CartRepository.php` | Méthode `findActiveCartForUser()` |
| `src/Controller/CartController.php` | 5 actions : show, add, updateQuantity, removeItem, checkout |
| `templates/cart/show.html.twig` | Page du panier |
| `templates/product/show.html.twig` | Formulaire "Ajouter au panier" |
| `templates/home/index.html.twig` | Lien "Mon panier" dans la navigation |
| `assets/styles/app.css` | Styles panier |
| `migrations/Version20260528093921.php` | Migration BDD |

---

## Routes du CartController

Toutes les routes sont préfixées `/cart` et nécessitent `ROLE_USER`.

| Méthode | URL | Nom | Description |
|---|---|---|---|
| GET | `/cart` | `app_cart_show` | Affiche le panier actif |
| POST | `/cart/add/{id}` | `app_cart_add` | Ajoute un produit au panier |
| POST | `/cart/item/{id}/quantity` | `app_cart_update_quantity` | Modifie la quantité (0 = supprime) |
| POST | `/cart/item/{id}/remove` | `app_cart_remove_item` | Retire un article |
| POST | `/cart/checkout` | `app_cart_checkout` | Valide la commande |

---

## Logique métier

### Ajout au panier (`add`)
1. Recherche le panier actif de l'utilisateur (`purchasedAt IS NULL`) ou en crée un nouveau.
2. Vérifie que la quantité demandée + la quantité déjà dans le panier ne dépasse pas le stock disponible.
3. Si le produit est déjà dans le panier, incrémente la quantité ; sinon crée un nouveau `CartItem`.

### Modification de quantité (`updateQuantity`)
- Vérifie que l'article appartient bien au panier de l'utilisateur connecté (sécurité).
- Quantité ≤ 0 → supprime l'article.
- Quantité > stock → message d'erreur, refus.

### Suppression d'un article (`removeItem`)
- Vérification CSRF + appartenance au panier de l'utilisateur.
- Suppression de l'entité `CartItems`.

### Validation de commande (`checkout`)
1. Vérifie le stock de chaque article une dernière fois (race condition possible entre l'ajout et le checkout).
2. Réduit le stock de chaque produit (`stock -= quantity`).
3. Marque le panier comme acheté (`purchasedAt = now`).
4. Redirige vers l'accueil avec un message de confirmation.

Un nouveau panier sera créé automatiquement lors du prochain ajout.

### Sécurité
- Toutes les actions POST utilisent des **tokens CSRF** pour éviter les requêtes forgées.
- Les actions sur un `CartItem` vérifient que l'item appartient au panier actif de l'utilisateur connecté.
- Un vendeur ne peut pas ajouter son propre produit à son panier (vérification dans le template).
