# Profil utilisateur, commentaires et notation des vendeurs

## Objectif

Permettre aux utilisateurs inscrits d'accéder à un espace personnel complet, de laisser des avis (commentaire + note) sur les vendeurs, et de consulter leur réputation. Un administrateur peut supprimer n'importe quel avis ; un utilisateur ordinaire ne peut supprimer que les siens.

---

## Modèle de données

### Entité `Review`

Nouvelle entité représentant un avis d'un acheteur sur un vendeur.

| Champ | Type | Description |
|---|---|---|
| `id` | int (PK, auto) | Identifiant |
| `author` | ManyToOne → User | L'utilisateur qui écrit l'avis |
| `seller` | ManyToOne → User | L'utilisateur évalué (vendeur) |
| `rating` | int (1–5) | Note numérique |
| `comment` | TEXT | Corps du commentaire |
| `createdAt` | DATETIME | Date de publication (ou de mise à jour) |

**Contrainte d'unicité** : `(author_id, seller_id)` — un utilisateur ne peut laisser qu'un seul avis par vendeur. S'il soumet à nouveau le formulaire, l'avis existant est mis à jour (rating + comment + date).

**`onDelete: 'CASCADE'`** sur la colonne `seller_id` : si un vendeur est supprimé, tous ses avis reçus sont automatiquement effacés par la base de données.

### Ajouts sur `User.php`

Deux nouvelles collections :
- `givenReviews` — OneToMany(Review, mappedBy='author') : avis rédigés par cet utilisateur.
- `receivedReviews` — OneToMany(Review, mappedBy='seller') : avis reçus par cet utilisateur en tant que vendeur.

---

## Fichiers créés / modifiés

### `src/Entity/Review.php`
Entité Doctrine avec la contrainte d'unicité `#[ORM\UniqueConstraint]`.

### `src/Repository/ReviewRepository.php`
Quatre méthodes utilitaires :
- `findBySeller(User)` — tous les avis reçus par un vendeur, triés par date décroissante.
- `findByAuthor(User)` — tous les avis rédigés par un auteur.
- `findOneByAuthorAndSeller(User, User)` — avis existant pour un couple auteur/vendeur (utilisé pour la mise à jour).
- `getAverageRating(User)` — note moyenne calculée en SQL (`AVG`), retourne `null` si aucun avis.

### `src/Controller/ProfileController.php`
Route `GET /profile` (protégée par `ROLE_USER`). Collecte pour l'utilisateur connecté :
- les avis reçus + note moyenne,
- les avis donnés.

### `src/Controller/ReviewController.php`
Deux routes :

**`POST /review/add/{id}`** (sellerId) — ajouter ou mettre à jour un avis :
1. Vérifie que l'auteur n'est pas le vendeur lui-même.
2. Valide le token CSRF (`review_add_{id}`).
3. Contrôle que la note est entre 1 et 5, et que le commentaire n'est pas vide.
4. Cherche un avis existant (couple auteur/vendeur). Si trouvé → mise à jour ; sinon → création.
5. Redirige vers la page vendeur ou le profil selon un paramètre `redirect` caché dans le formulaire.

**`POST /review/{id}/delete`** — supprimer un avis :
1. Vérifie que l'utilisateur connecté est l'auteur **ou** possède `ROLE_ADMIN`.
2. Valide le token CSRF (`review_delete_{id}`).
3. Redirige vers la page précédente (via l'en-tête `Referer`).

### `src/Controller/SellerController.php`
Route `GET /seller/{id}` — page publique d'un vendeur :
- Ses annonces actives.
- Ses avis reçus avec note moyenne.
- Formulaire pour laisser un avis (visible uniquement si connecté et pas le vendeur lui-même).
- Bouton « Supprimer » sur chaque avis visible uniquement pour son auteur ou un admin.

### Templates

| Fichier | Description |
|---|---|
| `templates/profile/show.html.twig` | Page profil de l'utilisateur connecté |
| `templates/seller/show.html.twig` | Page publique d'un vendeur avec formulaire d'avis |

**Page profil** (`/profile`) affiche quatre sections :
1. Informations personnelles (nom, email, téléphone, adresse).
2. **Réputation de vendeur** : note moyenne avec étoiles + liste de tous les avis reçus (avec bouton de suppression admin).
3. **Vendeurs évalués** : liste des avis donnés par l'utilisateur connecté (avec bouton de suppression propre).
4. Articles favoris et commandes passées.

**Page vendeur** (`/seller/{id}`) :
- En-tête avec nom, email et note moyenne (étoiles + chiffre).
- Liste des annonces du vendeur.
- Formulaire d'avis interactif (étoiles cliquables en CSS pur, textarea).
- Liste des avis reçus avec suppression conditionnelle.

### `templates/product/show.html.twig`
Le nom du vendeur est maintenant un lien cliquable vers `/seller/{id}`.

### `assets/styles/app.css`
Nouveaux blocs CSS :
- `.profile-card`, `.profile-info-grid`, `.profile-label`, `.profile-list` — mise en page du profil.
- `.rating-badge`, `.stars-display` — affichage de la note moyenne.
- `.star-rating` — sélecteur d'étoiles interactif en CSS pur (aucun JavaScript nécessaire), basé sur des `<input type="radio">` cachés et leurs `<label>`.
- `.reviews-list`, `.review-item`, `.review-header`, `.review-comment`, `.btn-review-delete` — liste d'avis.
- `.seller-header`, `.seller-avg-stars` — en-tête de la page vendeur.

### `src/DataFixtures/AppFixtures.php`
Ajout de la génération d'avis fictifs : chaque utilisateur évalue aléatoirement 0 à 3 autres utilisateurs. Les commentaires sont adaptés à chaque note (1 à 5 étoiles) grâce à un tableau de textes prédéfinis. La contrainte d'unicité est respectée via un tableau `$usedPairs`.

---

## Migration

La migration `Version20260528095545` crée la table `review` avec :
- Les colonnes `id`, `author_id`, `seller_id`, `rating`, `comment`, `created_at`.
- L'index unique `review_author_seller_unique`.
- Les clés étrangères vers `user`.

---

## Droits d'accès

| Action | Condition |
|---|---|
| Voir la page `/profile` | Connecté (`ROLE_USER`) |
| Voir `/seller/{id}` | Tous (public) |
| Laisser un avis | Connecté + ne pas s'auto-évaluer |
| Supprimer un avis | Auteur de l'avis **ou** `ROLE_ADMIN` |
