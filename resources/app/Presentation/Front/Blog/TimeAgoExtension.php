<?php declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use Latte\Extension;
use Nette\Localization\Translator;


/**
 * Latte filter "ago" for the dates of articles: {$date|ago} prints e.g. "2 months ago" ("před 2 měsíci").
 * Czech and English differ only between one and more after "před" / "ago", so each unit has two messages.
 */
final class TimeAgoExtension extends Extension
{
	/** Seconds of a unit, the message for one and for more, from the largest unit. */
	private const array Units = [
		[31_536_000, 'a year ago', '%d years ago'],
		[2_592_000, 'a month ago', '%d months ago'],
		[86_400, 'a day ago', '%d days ago'],
		[3_600, 'an hour ago', '%d hours ago'],
		[60, 'a minute ago', '%d minutes ago'],
	];


	public function __construct(
		private readonly Translator $translator,
	) {
	}


	public function getFilters(): array
	{
		return ['ago' => $this->ago(...)];
	}


	public function ago(\DateTimeInterface $date, ?\DateTimeInterface $now = null): string
	{
		$seconds = ($now ?? new \DateTimeImmutable)->getTimestamp() - $date->getTimestamp();
		foreach (self::Units as [$unit, $one, $more]) {
			$count = intdiv($seconds, $unit);
			if ($count >= 1) {
				return (string) $this->translator->translate($count === 1 ? $one : $more, $count);
			}
		}

		return (string) $this->translator->translate('just now');
	}
}
