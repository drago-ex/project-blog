<?php declare(strict_types=1);

namespace App\Presentation\Backend\Blog;

use App\Model\Blog\CommentRepository;
use DateMalformedStringException;
use Dibi\Exception;


/**
 * The widgets of the blog on the dashboard: the comments of the last 30 days as a chart and the most commented articles
 * as a ranking. A service tagged dashboard.widget for drago-ex/project-backend-ui, which calls it; no class of that
 * package is needed. Titles are translation keys, article titles are data.
 */
final readonly class DashboardWidget
{
	public function __construct(
		private CommentRepository $commentRepository,
	) {
	}


	/**
	 * @return list<array<string, mixed>>
	 * @throws Exception
	 * @throws DateMalformedStringException
	 */
	public function __invoke(): array
	{
		$days = $this->commentRepository->countByDay(30);

		return [
			[
				'type' => 'chart',
				'link' => 'Blog:',
				'title' => 'Comments in the last 30 days',
				'width' => 'wide',
				'order' => 10,
				'chart' => [
					// a number of each day: columns, not a smooth line between the days
					'type' => 'bar',
					'labels' => array_map(fn(string $day): string => (new \DateTimeImmutable($day))->format('j. n.'), array_keys($days)),
					'series' => [['name' => 'Comments', 'data' => array_values($days)]],
				],
			],
			[
				'type' => 'ranking',
				'link' => 'Blog:',
				'title' => 'Most commented articles',
				'order' => 20,
				'items' => array_map(
					fn(array $row): array => [$row['title'], $row['count']],
					$this->commentRepository->getMostCommented(5),
				),
				'empty' => 'No comments yet.',
			],
		];
	}
}
