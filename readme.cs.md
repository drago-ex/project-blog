# Drago Project Blog

[English](readme.md) | Čeština

Články, kategorie a komentáře blogu pro Drago Project.

Balíček přidává modul administrace pro psaní článků, správu kategorií a schvalování komentářů u jejich článku a
veřejnou část se seznamem článků, kategoriemi a detailem článku s komentáři.

[![PHP version](https://badge.fury.io/ph/drago-ex%2Fproject-blog.svg)](https://badge.fury.io/ph/drago-ex%2Fproject-blog)
[![Coding Style](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/project-blog/actions/workflows/coding-style.yml)

## Požadavky

- PHP >= 8.3, < 9
- Composer

## Instalace

```bash
composer require drago-ex/project-blog
```

## Soubory projektu

Kopírování souborů obstarává automaticky [drago-ex/project-tools](https://github.com/drago-ex/project-tools),
který musí být v projektu nainstalovaný. Bez něj zkopírujte soubory ručně podle sekce `copy`
v `composer.json` tohoto balíčku. Pro přeskočení balíčku nastavte `"skip": true` v
`extra.drago-tools.packages.<package-name>` v kořenovém `composer.json`.

## Migrace databáze

```bash
php vendor/bin/migration db:migrate --packages
```

Migrace vytvoří tabulky `blog_article`, `blog_category` a `blog_comment`.

## Assety

Lišta pole s obsahem a tlačítko pro kopírování bloků kódu pocházejí z
[drago-ex/texy-toolbar](https://github.com/drago-ex/texy-toolbar); nastavte je podle jeho README (`admin.js` pro
lištu, `front.js` pro lištu komentářů a tlačítko kopírování). Blog funguje i bez nich.

## Oprávnění

Blog funguje bez oprávnění i bez [drago-ex/permission](https://github.com/drago-ex/permission):
administrace pak zobrazuje všechna svá tlačítka.

Pro statická oprávnění jsou pravidla přístupu připravená v [`permission.php`](permission.php); drago-ex/permission
z nich vygeneruje provider (nastavení popisuje jeho README):

```bash
php vendor/bin/create-permission --packages
```

Aby tlačítka pro přidání, úpravu a mazání viděly jen role s privilegiem, přidejte trait `WriteAccess` z
drago-ex/permission; jeho `isWriteAllowed()` se ptá na privilegium `{component}-write`. V
`app/Presentation/Backend/Blog/Component/BaseControl.php` trait použijte a výsledek předejte do šablony
(s `public bool $writeAllowed = false;` v `BaseTemplate`):

```php
use Drago\Permission\WriteAccess;

abstract class BaseControl extends ExtraControl implements OffcanvasHandle, ModalHandle
{
	use Component;
	use WriteAccess;

	public function createRender(): Template
	{
		// ...
		$template->writeAllowed = $this->isWriteAllowed();
```

potom podle něj skryjte akce gridu a tlačítko pro přidání zobrazte jen s oprávněním:

```php
$grid->addActionEdit(fn(int $id) => $this->handleEdit($id))
	->setVisible($this->isWriteAllowed());
```

```latte
{embed '../@datagrid-heading.latte', addButton: $writeAllowed}
```

V článku zobrazte tlačítka schválení a smazání v záložce komentářů (`<div class="article-comment-actions …">`)
jen s `n:if="$writeAllowed"` a nechte `ArticleControl::getToggleCommentsLink()` bez oprávnění vracet null, aby
stav komentářů v gridu nebyl klikací.

Pro dynamická oprávnění nastavovaná v administraci nainstalujte
[drago-ex/project-permission](https://github.com/drago-ex/project-permission) a ručně naimportujte privilegia blogu
do jeho tabulky `resources`, aby je bylo možné přidělovat rolím (např. redaktor, který články jen prohlíží):

```bash
php vendor/bin/migration db:migrate vendor/drago-ex/project-blog/migrations-permission
```

V projektu sestaveném přes [netis](https://github.com/netis-cms/netis) tyto změny udělá project-preset
a privilegia naimportuje webový instalátor z project-install.

## Instalované soubory

Balíček kopíruje:

- `resources/app/Model` do `app/Model`
- `resources/app/Presentation/Backend` do `app/Presentation/Backend`
- `resources/app/Presentation/Front` do `app/Presentation/Front`

## Administrace

Presenter administrace je dostupný jako:

```latte
{link :Backend:Blog:}
```

S [drago-ex/project-backend-ui](https://github.com/drago-ex/project-backend-ui) se v postranním menu objeví
položka „Blog“ v sekci „Obsah“ (`Backend/Blog/6.menu.neon`). Novou kategorii napíšete přímo ve formuláři článku
(„+ Nová kategorie“); tlačítko „Kategorie“ nad články otevře jejich seznam pro přejmenování a mazání. Slug nové
kategorie se vytvoří z názvu pomocí rozšíření PHP intl. Komentáře se spravují u svého článku: seznam článků
ukazuje, zda jsou povolené (kliknutím na stav se přepne), jejich počet i ty, které čekají na schválení, a otevřený
článek má záložku „Komentáře“ pro jejich schválení nebo smazání. Počet všech komentářů čekajících na schválení je
vidět u položky „Blog“ v menu a u zvonku v navbaru (`CommentBadge`).

Modul používá zdroj oprávnění `Backend:Blog`. Provider administrace povoluje roli administrátora.

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
(`Front/Blog/@article-featured.latte`). Karta je `Front/Blog/@article-item.latte`, takže i jiné stránky mohou
články zobrazit stejně. Autorem je přihlášený uživatel, který článek vytvořil.

Úvodní obrázek se nahrává ve formuláři článku. Zmenší se, aby se vešel do 1600 × 1200 px, a uloží do
`www/upload/blog/`; výměna nebo odebrání obrázku a smazání článku soubor smažou. Zmenšování potřebuje
rozšíření PHP GD (Docker obraz z drago-ex/project-docker ho má); bez něj formulář nahrávání nenabízí.
Přidejte `/www/upload/` do `.gitignore`.

Komentář může napsat kdokoli; zobrazí se po schválení administrátorem. Komentáře používají základní Texy (bez obrázků,
nadpisů a HTML). Každý článek je povoluje nebo zakazuje vlastním přepínačem „Povolit komentáře“. Přihlášený
uživatel jméno a e-mail nevyplňuje, komentář dostane ty z jeho účtu. Schválené komentáře jsou očíslované (`#1`,
`#2`, …) a „Odpovědět“ vloží do formuláře `#2 Jméno`; takové číslo v komentáři odkazuje na komentář, který vypisuje
své odpovědi. Odkaz pro odpověď potřebuje `front.js` z drago-ex/project-preset (bez něj jen vede k formuláři).

Provider oprávnění veřejné části používá zdroj `Front:Blog` a povoluje ho návštěvníkům.
