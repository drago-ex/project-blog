<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog;

use App\Model\Blog\CommentRepository;


/**
 * The number of comments waiting for approval next to "Blog" in the administration menu and in the bell of the navbar;
 * the article list shows them by article. A service tagged sidebar.badge for drago-ex/project-backend-ui, which calls it;
 * no class of that package is needed.
 */
final readonly class CommentBadge
{
	public function __construct(
		private CommentRepository $commentRepository,
	) {
	}


	/**
	 * The number next to the menu item of the link, and the label of the entry in the bell.
	 * @return list<array{link: string, count: int, label: string}>
	 */
	public function __invoke(): array
	{
		return [[
			'link' => 'Blog:',
			'count' => $this->commentRepository->countPending(),
			'label' => 'Comments waiting for approval',
		]];
	}
}
