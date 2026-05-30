# Panel d'administration — Gestion des utilisateurs, produits et commentaires

## Objectif

Fournir à l'administrateur une interface dédiée permettant de visualiser, modifier et supprimer n'importe quel utilisateur, ses produits publiés et ses commentaires (avis), le tout accessible uniquement avec le rôle `ROLE_ADMIN`.

---

## Nouvelles routes

| Méthode | URL | Nom de route | Description |
|---------|-----|--------------|-------------|
| GET | `/admin` | `app_admin_dashboard` | Tableau de bord — liste de tous les utilisateurs |
| GET | `/admin/user/{id}` | `app_admin_user_detail` | Détail d'un utilisateur (infos, produits, avis) |
| POST | `/admin/user/{id}/edit` | `app_admin_user_edit` | Modifier les informations et le rôle d'un utilisateur |
| POST | `/admin/user/{id}/delete` | `app_admin_user_delete` | Supprimer un utilisateur et toutes ses données |
| POST | `/admin/product/{id}/delete` | `app_admin_product_delete` | Supprimer un produit (contexte admin) |
| POST | `/admin/review/{id}/delete` | `app_admin_review_delete` | Supprimer un commentaire (contexte admin) |

Toutes ces routes sont protégées par `#[IsGranted('ROLE_ADMIN')]` au niveau de la classe `AdminController`.

---

## Fichiers créés / modifiés

### `src/Controller/AdminController.php`

Nouveau contrôleur avec six actions :

**`dashboard()`** — charge tous les utilisateurs via `UserRepository::findAll()` et calcule la note moyenne de chacun via `ReviewRepository::getAverageRating()`. Passe un tableau `usersData` (user + averageRating) au template.

**`userDetail()`** — charge l'entité `User` par son id (param converter Doctrine) et sa note moyenne. Passe `targetUser` et `averageRating` au template.

**`editUser()`** — traite le formulaire POST inline (sans FormType). Valide le token CSRF, met à jour les champs `name`, `surname`, `email`, `address`, `phoneNumber`, et le tableau `roles` selon la case à cocher `is_admin`. L'admin ne peut pas modifier son propre rôle (la checkbox est désactivée côté template). Redirige vers la page de détail.

**`deleteUser()`** — effectue un nettoyage en cascade avant de supprimer l'utilisateur :
1. Délie les paniers (`cart.buyer = null`) pour conserver l'historique des commandes.
2. Supprime les `CartItems` de chaque produit, puis les produits eux-mêmes (pas de cascade Doctrine sur ces relations).
3. Supprime l'utilisateur — les `givenReviews` et `receivedReviews` sont éliminées via `orphanRemoval: true`, la ManyToMany des favoris est gérée automatiquement par Doctrine. Redirige vers le tableau de bord.
4. Bloque l'auto-suppression (l'admin ne peut pas supprimer son propre compte).

**`deleteProduct()`** — supprime les `CartItems` du produit puis le produit. Redirige vers la page de détail du vendeur si celui-ci existe encore.

**`deleteReview()`** — supprime un avis. Reçoit un champ caché `redirect_user_id` pour rediriger vers la page de l'utilisateur concerné.

---

### `templates/admin/dashboard.html.twig`

Tableau HTML reprenant les classes CSS existantes (`product-table`, `profile-card`, etc.) listant tous les utilisateurs avec :
- Nom, email, rôle (badge Admin / Membre)
- Nombre de produits publiés
- Nombre d'avis reçus et note moyenne
- Boutons **Voir** (→ détail) et **Supprimer** (avec `confirm()` JavaScript)

---

### `templates/admin/user_detail.html.twig`

Page de détail structurée en quatre sections (`profile-card`) :

1. **Infos + formulaire d'édition** : champs pré-remplis (prénom, nom, email, téléphone, adresse) + checkbox "Rôle administrateur". Bouton rouge "Supprimer l'utilisateur" en haut à droite (masqué pour le compte courant).

2. **Produits publiés** : tableau avec nom (lien cliquable vers la fiche publique), catégorie, prix, stock (badges colorés), date de publication. Boutons **Modifier** (redirige vers `/product/{id}/edit`, déjà accessible à l'admin via `ProductController`) et **Supprimer**.

3. **Avis reçus** : liste des avis reçus en tant que vendeur, avec lien vers le profil admin de l'auteur.

4. **Avis donnés** : liste des avis donnés par cet utilisateur, avec lien vers le profil admin du vendeur évalué.

---

### `assets/styles/app.css`

Nouveaux blocs CSS ajoutés à la fin du fichier :

| Classe | Rôle |
|--------|------|
| `.badge-user` | Badge gris "Membre" (pendant du `.badge-admin` violet) |
| `.admin-section-header` | En-tête de section du tableau de bord (titre + compteur) |
| `.admin-count` | Pastille bleue indigo pour les compteurs |
| `.admin-table` | Marge inférieure du tableau admin |
| `.admin-actions-cell` | Flexbox pour aligner boutons d'action en ligne |
| `.admin-card-header` | Flexbox espace-between pour titre + bouton supprimer |
| `.admin-edit-form .form-control` | Style des inputs dans le formulaire d'édition admin |
| `.admin-checkbox-label` | Style de la checkbox rôle admin |
| `.admin-rating` | Couleur ambre pour la note moyenne dans le tableau |

---

### `templates/home/index.html.twig` et `templates/profile/show.html.twig`

Ajout d'un lien **Administration** dans la barre de navigation, visible uniquement si `is_granted('ROLE_ADMIN')`, pointant vers `app_admin_dashboard`.

---

## Sécurité

| Action | Condition |
|--------|-----------|
| Accéder à `/admin/*` | `ROLE_ADMIN` (contrôlé par `#[IsGranted]` + `security.yaml`) |
| Modifier un utilisateur | `ROLE_ADMIN` + token CSRF valide |
| Supprimer un utilisateur | `ROLE_ADMIN` + token CSRF valide + ne pas être soi-même |
| Supprimer un produit (admin) | `ROLE_ADMIN` + token CSRF valide |
| Supprimer un commentaire (admin) | `ROLE_ADMIN` + token CSRF valide |

Toutes les actions destructives (POST) utilisent un token CSRF spécifique à l'entité (`admin_user_delete_{id}`, etc.) pour prévenir les attaques CSRF.

---

## Comportement lors de la suppression d'un utilisateur

| Donnée | Comportement |
|--------|-------------|
| Paniers (commandes passées) | `buyer` mis à `null` — l'historique de commande est conservé |
| Produits publiés | Supprimés, après suppression préalable de leurs `CartItems` |
| Avis donnés | Supprimés via `orphanRemoval: true` sur `User.givenReviews` |
| Avis reçus | Supprimés via `orphanRemoval: true` sur `User.receivedReviews` |
| Favoris | Entrées de la table de jonction supprimées automatiquement par Doctrine |
