<?php

declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Presentation\Backend\Blog\Component\BaseTemplate;


class ArticleTemplate extends BaseTemplate
{
	/** File name of the main image of the edited article, for its preview in the form. */
	public ?string $mainImage = null;
}
