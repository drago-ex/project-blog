<?php

declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use Drago\Form\Forms;
use Nette;
use Nette\Application\Routers\RouteList;


/**
 * Blog addresses /blog, /blog/kategorie/slug and /blog/slug (7.conf.neon is loaded before the front router
 * in 9.conf.neon, whose <presenter>/<action> would make them /blog/detail?slug=…).
 */
final class BlogRouter
{
	use Nette\StaticClass;

	public static function create(): RouteList
	{
		$router = new RouteList;
		$router->withModule('Front')
			->addRoute('[<lang=cs cs|en>/]blog/kategorie/<category ' . Forms::SlugPattern . '>', 'Blog:category')
			->addRoute('[<lang=cs cs|en>/]blog/<slug ' . Forms::SlugPattern . '>', 'Blog:detail')
			->addRoute('[<lang=cs cs|en>/]blog', 'Blog:default');

		return $router;
	}
}
