<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog\Component\Article;

use App\Model\Blog\CommentEntity;
use App\Presentation\Backend\Blog\Component\BaseTemplate;


class ArticleTemplate extends BaseTemplate
{
	/** File name of the main image of the edited article, for its preview in the form. */
	public ?string $mainImage = null;

	/** The main image asks for the confirmation of its removal. */
	public bool $confirmRemoveImage = false;

	/** The field of a new category is open (it has a value after a validation error). */
	public bool $newCategory = false;

	/** The edited article, 0 for a new one (which has no comments tab). */
	public int $articleId = 0;

	/** @var CommentEntity[] all comments of the edited article, approved or not */
	public array $comments = [];

	/** @var array<int, string> HTML of the basic Texy of the comments by id, made safe by TexyFormatter */
	public array $commentHtml = [];

	/** The comment that asks for the confirmation of its delete. */
	public ?int $confirmDeleteComment = null;
}
