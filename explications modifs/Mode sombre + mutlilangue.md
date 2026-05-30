# Mode sombre / clair et multilingue (FR / EN)

## 1. Mode sombre / clair

### Principe

Le thème est géré côté client via des **variables CSS** et un attribut `data-theme` posé sur la balise `<html>`.  
La préférence est persistée dans le `localStorage` du navigateur.

### Implémentation technique

#### Variables CSS (`assets/styles/app.css`)

Toutes les couleurs thématiques sont déclarées comme variables CSS dans `:root` (thème clair) et surchargées dans `[data-theme="dark"]` :

```css
:root {
    --bg-page:  #f3f4f6;
    --bg-card:  #ffffff;
    --text-1:   #111827;
    /* … */
}

[data-theme="dark"] {
    --bg-page:  #0f172a;
    --bg-card:  #1e293b;
    --text-1:   #f1f5f9;
    /* … */
}
```

Les règles CSS utilisent ensuite `var(--nom-variable)` à la place des valeurs hexadécimales.  
Résultat : un seul changement d'attribut HTML bascule l'intégralité de l'interface.

#### Script anti-flash (`templates/base.html.twig`)

Un script inline synchrone est placé dans le `<head>`, **avant** le chargement de la feuille de styles. Il lit la préférence en `localStorage` et positionne immédiatement `data-theme` pour éviter un flash de thème clair au démarrage :

```html
<script>
  (function(){
    try{var t=localStorage.getItem('theme');
    if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}
    catch(e){}
  })()
</script>
```

#### Attribut `lang` (`templates/base.html.twig`)

```html
<html lang="{{ app.request.locale }}">
```

La locale Symfony est ainsi reflétée dans le HTML pour l'accessibilité et le SEO.

#### JavaScript de bascule (`assets/app.js`)

```js
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('theme-toggle');
    // …
    toggle.addEventListener('click', function () {
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        // mise à jour de l'icône 🌙 / ☀
    });
});
```

#### Bouton toggle (`templates/_controls.html.twig`)

Le partial `_controls.html.twig` est inclus dans la `<nav>` de **chaque page** via `{{ include('_controls.html.twig') }}`.  
Il contient le bouton thème et le sélecteur de langue.

---

## 2. Multilingue (Français / Anglais)

### Principe

Symfony fournit le composant `symfony/translation` (déjà installé).  
La locale active est lue depuis la **session** et appliquée à chaque requête via un event subscriber.  
Le changement de langue se fait via une URL dédiée.

### Implémentation technique

#### Fichiers de traduction

| Fichier | Langue |
|---------|--------|
| `translations/messages.fr.yaml` | Français (défaut) |
| `translations/messages.en.yaml` | Anglais |

Les clés sont regroupées par domaine fonctionnel :  
`nav.*`, `home.*`, `product.*`, `cart.*`, `profile.*`, `login.*`, `register.*`, `seller.*`, `admin.*`, `admin_detail.*`, `controls.*`

**Exemple :**
```yaml
# messages.fr.yaml
nav.my_profile: "Mon profil"
product.add_to_cart: "Ajouter au panier"

# messages.en.yaml
nav.my_profile: "My profile"
product.add_to_cart: "Add to cart"
```

#### Configuration (`config/packages/translation.yaml`)

```yaml
framework:
    default_locale: fr
    translator:
        default_path: '%kernel.project_dir%/translations'
        fallbacks:
            - fr
```

La locale par défaut est `fr`. Si une clé est manquante en anglais, elle tombera en fallback sur le français.

#### LocaleController (`src/Controller/LocaleController.php`)

Route : `GET /locale/{locale}` (contrainte : `fr|en`)  
Stocke la locale dans la session et redirige vers la page précédente (`Referer`) :

```php
#[Route('/locale/{locale}', name: 'app_switch_locale', requirements: ['locale' => 'fr|en'])]
public function switchLocale(string $locale, Request $request): RedirectResponse
{
    $request->getSession()->set('_locale', $locale);
    return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_home'));
}
```

#### LocaleSubscriber (`src/EventSubscriber/LocaleSubscriber.php`)

Ecoute l'événement `kernel.request` avec une priorité de 20 (avant le firewall).  
Lit `_locale` dans la session et l'applique sur la requête :

```php
public function onKernelRequest(RequestEvent $event): void
{
    $request = $event->getRequest();
    if (!$request->hasPreviousSession()) return;
    $locale = $request->getSession()->get('_locale', $this->defaultLocale);
    $request->setLocale($locale);
}
```

#### Utilisation dans les templates Twig

Texte simple :
```twig
{{ 'nav.my_profile'|trans }}
```

Texte avec paramètre :
```twig
{{ 'profile.order_date'|trans({'%date%': cart.purchasedAt|date('d/m/Y')}) }}
```

Pluralisation (gérée inline) :
```twig
{{ products|length != 1 ? 'home.results_plural'|trans : 'home.results_singular'|trans }}
```

#### Sélecteur de langue (`templates/_controls.html.twig`)

```twig
<div class="lang-switcher">
    <a href="{{ path('app_switch_locale', {locale: 'fr'}) }}"
       class="lang-btn {% if app.request.locale == 'fr' %}lang-btn--active{% endif %}">FR</a>
    <a href="{{ path('app_switch_locale', {locale: 'en'}) }}"
       class="lang-btn {% if app.request.locale == 'en' %}lang-btn--active{% endif %}">EN</a>
</div>
```

---

## 3. Éléments non traduits

Les **messages flash** (succès/erreur) sont générés dans les contrôleurs PHP et transmis en chaîne brute.  
Pour les traduire, il faudrait injecter le service `TranslatorInterface` dans chaque contrôleur et appeler `$translator->trans('ma.cle')` à la place des chaînes en dur — ce n'est pas inclus dans cette implémentation.

Les **libellés des champs de formulaire Symfony** (nom, description, prix…) sont définis dans les classes `Form/` via l'option `label`. Pour les traduire, utiliser `label: 'ma.cle'` et créer les clés correspondantes dans le domaine `messages`.

---

## 4. Fichiers créés / modifiés

| Fichier | Action |
|---------|--------|
| `translations/messages.fr.yaml` | Créé |
| `translations/messages.en.yaml` | Créé |
| `src/Controller/LocaleController.php` | Créé |
| `src/EventSubscriber/LocaleSubscriber.php` | Créé |
| `templates/_controls.html.twig` | Créé |
| `config/packages/translation.yaml` | Modifié |
| `assets/app.js` | Modifié |
| `assets/styles/app.css` | Modifié (variables CSS) |
| `templates/base.html.twig` | Modifié |
| `templates/home/index.html.twig` | Modifié |
| `templates/product/show.html.twig` | Modifié |
| `templates/product/new.html.twig` | Modifié |
| `templates/product/edit.html.twig` | Modifié |
| `templates/product/_form.html.twig` | Modifié |
| `templates/cart/show.html.twig` | Modifié |
| `templates/profile/show.html.twig` | Modifié |
| `templates/security/login.html.twig` | Modifié |
| `templates/registration/register.html.twig` | Modifié |
| `templates/seller/show.html.twig` | Modifié |
| `templates/admin/dashboard.html.twig` | Modifié |
| `templates/admin/user_detail.html.twig` | Modifié |
