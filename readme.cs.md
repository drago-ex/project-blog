# Drago Project Blog

Články, kategorie a komentáře blogu pro Drago Project.

Balíček přidává modul administrace pro psaní článků, správu kategorií a schvalování komentářů u jejich článku a
veřejnou část se seznamem článků, kategoriemi a detailem článku s komentáři.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Rozšíření PHP GD (zmenšování úvodních obrázků) a intl (slugy nových kategorií); Docker obraz z
  [drago-ex/project-docker](https://github.com/drago-ex/project-docker) má obě
- [Drago Project](https://github.com/drago-ex/project)

## Instalace

```bash
composer require drago-ex/project-blog
```

## Soubory kopírované do projektu

Soubory do projektu kopíruje [drago-ex/project-tools](https://github.com/drago-ex/project-tools)
podle nastavení v `extra.drago-tools` v `composer.json` tohoto balíčku.

## Migrace databáze

```bash
php vendor/bin/migration db:migrate --packages
```

Migrace vytvoří tabulky `blog_article`, `blog_category` a `blog_comment`.

## Assety

Zaregistrujte rozšíření Naja, které po kliknutí na „Odpovědět“ pod komentářem vloží „#2 Jméno“ do formuláře komentáře,
v `assets/front.js` (bez něj odkaz jen vede k formuláři):

```js
import CommentReply from './naja/comment-reply.js';

registerNajaExtensions(
	// ...
	CommentReply
);
```

Lišta pole s obsahem a tlačítko pro kopírování bloků kódu pocházejí z
[drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar); nastavte je podle jeho README (`admin.js` pro
lištu, `front.js` pro lištu komentářů a tlačítko kopírování). Blog funguje i bez nich.

Seznamy článků a kategorií pocházejí z [drago-ex/datagrid](https://github.com/drago-ex/datagrid); nastavte jeho assety
v `admin.js` a `admin.scss` podle jeho README.

## Administrace

Presenter administrace je dostupný jako:

```latte
{link :Backend:Blog:}            {* articles with their comments *}
{link :Backend:Blog:categories}  {* categories *}
```

S [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui) dostane stránka položku menu z `Backend/Blog/6.menu.neon`.

Novou kategorii napíšete přímo ve formuláři článku
(tlačítko „+“ vedle kategorie); tlačítko „Kategorie“ nad články otevře jejich seznam pro přejmenování a mazání. Slug nové
kategorie se vytvoří z názvu. Komentáře se spravují u svého článku: seznam článků
ukazuje, zda jsou povolené (kliknutím na stav se přepne), jejich počet i ty, které čekají na schválení, a otevřený
článek má záložku „Komentáře“ pro jejich schválení nebo smazání. Počet všech komentářů čekajících na schválení
dodává `CommentBadge`, služba pro čísla v menu a zvonek v navbaru project-backend-ui, zaregistrovaná v `Backend/Blog/6.menu.neon`.

Modul používá zdroj oprávnění `Backend:Blog`.

## Veřejná část

Routy `/blog`, `/blog/kategorie/<slug>` a `/blog/<slug>` (i s `/en/`) přidává
`app/Presentation/Front/Blog/7.conf.neon` před router veřejné části.

```latte
{link :Front:Blog:}
{link :Front:Blog:detail, slug: 'first-article'}
```

Obsah článku se píše v [Texy](https://texy.info/cs/syntax) a bezpečně ho vypisuje drago-ex/texy-toolbar;
perex slouží jako meta popis článku.

Výpis ukazuje články jako karty vedle sebe: široký obrázek, relativní datum (`{$date|ago}`, např. „před 2 měsíci“)
s kategorií, titulek, perex a autor s avatarem z [Gravataru](https://gravatar.com) (drago-ex/gravatar, zaregistrovaný
v `Front/Blog/7.conf.neon`). Nejnovější článek je přes celý řádek s velkým obrázkem vedle textu
(`Front/Blog/@article-featured.latte`), ostatní jsou karty (`Front/Blog/@article-item.latte`). Jiné stránky
zobrazí články stejně pomocí `ArticleListFactory::create()` a `Front/Blog/@article-list.latte`. Autorem je přihlášený uživatel, který článek vytvořil.

Úvodní obrázek se nahrává ve formuláři článku. Zmenší se, aby se vešel do 1600 × 1200 px, a uloží do
`www/upload/blog/`; výměna nebo odebrání obrázku a smazání článku soubor smažou.
Přidejte `/www/upload/` do `.gitignore`.

Komentář může napsat kdokoli; zobrazí se po schválení administrátorem. Komentáře používají základní Texy (bez obrázků,
nadpisů a HTML). Každý článek je povoluje nebo zakazuje vlastním přepínačem „Povolit komentáře“. Přihlášený
uživatel jméno a e-mail nevyplňuje, komentář dostane ty z jeho účtu. Schválené komentáře jsou očíslované (`#1`,
`#2`, …) a „Odpovědět“ vloží do formuláře `#2 Jméno`; takové číslo v komentáři odkazuje na komentář, který vypisuje
své odpovědi. Vložení textu do formuláře zajistí rozšíření Naja `CommentReply` (viz Assety).

Zdroj `Front:Blog` je povolený návštěvníkům.

## Pravidla přístupu

Pravidla přístupu jsou v `acl.neon` vedle presenterů: `app/Presentation/Backend/Blog/acl.neon` (zdroj `Backend:Blog` jen pro administrátora)
a `app/Presentation/Front/Blog/acl.neon` (zdroj `Front:Blog` pro všechny).
[drago-ex/permission](https://github.com/drago-ex/permission) je sloučí se svými výchozími rolemi, ve kterých administrátor smí všechno.

Privilegia administrace (`Backend:Blog`) jsou `default` a `categories` (stránky), `edit` (přidání a úpravy
článků a kategorií, přepínač komentářů, hlavní obrázek), `delete`, `approveComment` a `deleteComment`. Její tlačítka
se zobrazí jen těm, kdo je smí použít. S [drago-ex/project-permission](https://github.com/drago-ex/project-permission)
lze tato privilegia přidělit rolím v jeho administraci, např. moderátorovi `default` a `approveComment`.
