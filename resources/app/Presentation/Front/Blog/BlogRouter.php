<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use Nette;
use Nette\Application\Routers\RouteList;


/**
 * Blog addresses /blog, /blog/kategorie/slug and /blog/slug (7.conf.neon is loaded before the front router
 * in 9.conf.neon, whose <presenter>/<action> would make them /blog/detail?slug=…).
 */
final class BlogRouter
{
	use Nette\StaticClass;

	private const string Slug = '[a-z0-9]+(?:-[a-z0-9]+)*';


	public static function create(): RouteList
	{
		$router = new RouteList;
		$router->withModule('Front')
			->addRoute('[<lang=cs cs|en>/]blog/kategorie/<category ' . self::Slug . '>', 'Blog:category')
			->addRoute('[<lang=cs cs|en>/]blog/<slug ' . self::Slug . '>', 'Blog:detail')
			->addRoute('[<lang=cs cs|en>/]blog', 'Blog:default');

		return $router;
	}
}
