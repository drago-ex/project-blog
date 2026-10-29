<?php declare(strict_types=1);

/**
 * Test: App\Presentation\Front\Blog\BlogRouter
 */

use App\Presentation\Front\Blog\BlogRouter;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


/** @return array<string, mixed>|null */
function matchBlog(string $path): ?array
{
	return BlogRouter::create()->match(new Request(new UrlScript('https://example.com' . $path, '/')));
}


test('The article list and an article by its slug', function () {
	Assert::same(['presenter' => 'Front:Blog', 'action' => 'default', 'lang' => 'cs'], matchBlog('/blog'));

	$params = matchBlog('/en/blog/first-article');
	Assert::same('Front:Blog', $params['presenter'] ?? null);
	Assert::same('detail', $params['action'] ?? null);
	Assert::same('first-article', $params['slug'] ?? null);
	Assert::same('en', $params['lang'] ?? null);
});


test('Articles of a category', function () {
	$params = matchBlog('/blog/kategorie/nette');
	Assert::same('Front:Blog', $params['presenter'] ?? null);
	Assert::same('category', $params['action'] ?? null);
	Assert::same('nette', $params['category'] ?? null);

	// an article may be called "kategorie", the category list needs a slug after it
	Assert::same('detail', matchBlog('/blog/kategorie')['action'] ?? null);

	$url = new UrlScript('https://example.com/', '/');
	Assert::same(
		'https://example.com/en/blog/kategorie/nette',
		BlogRouter::create()->constructUrl(
			['presenter' => 'Front:Blog', 'action' => 'category', 'category' => 'nette', 'lang' => 'en'],
			$url,
		),
	);
});


test('Other addresses go on to the next routers', function () {
	Assert::null(matchBlog('/'));
	Assert::null(matchBlog('/blogs'));
	Assert::null(matchBlog('/blog/First'));
	Assert::null(matchBlog('/blog/a/b'));
});


test('The router links to the list and to an article', function () {
	$url = new UrlScript('https://example.com/', '/');
	$router = BlogRouter::create();
	Assert::same(
		'https://example.com/blog',
		$router->constructUrl(['presenter' => 'Front:Blog', 'action' => 'default', 'lang' => 'cs'], $url),
	);
	Assert::same(
		'https://example.com/blog/first-article',
		$router->constructUrl(['presenter' => 'Front:Blog', 'action' => 'detail', 'slug' => 'first-article', 'lang' => 'cs'], $url),
	);
});
