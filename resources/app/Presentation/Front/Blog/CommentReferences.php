<?php declare(strict_types=1);

namespace App\Presentation\Front\Blog;

use function strlen;


/**
 * Links "#2" in the HTML of comments to the second approved comment of the article (like on nette.org)
 * and remembers which comments replied to which.
 */
final class CommentReferences
{
	/** Spaces, dashes (escaped by preg_quote) and apostrophes as Texy may write them (no-break space, en dash, ’). */
	private const array Typography = [
		' ' => '(?:\s|\x{a0}|&nbsp;)+',
		'\-' => '[-\x{2013}\x{2014}]',
		"'" => '[\x27\x{2019}]',
	];

	/** @var array<int, int[]> numbers of the comments that refer to a comment, by its number */
	private array $replies = [];

	/** "#N", optionally followed by the name of an author, built once for all comments. */
	private readonly string $pattern;


	public function __construct(
		/** @var array<int, string> authors of the approved comments by their number, from 1 */
		private readonly array $names,
	) {
		// the longest names first, so "Jana Nová" is not matched as "Jana" followed by text
		$alternatives = array_unique(array_values($names));
		usort($alternatives, fn(string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
		$alternatives = array_map(fn(string $name): string => $this->toPattern($name), $alternatives);

		$this->pattern = '~(?<![\w&#/])#(\d+)\b'
			. ($alternatives === [] ? '' : '(?:(?:\s|\x{a0}|&nbsp;)+(' . implode('|', $alternatives) . ')(?![\p{L}\p{N}]))?')
			. '~u';
	}


	/**
	 * Replaces "#N" (with the author name after it, which the reply link inserts) by a link to the comment N.
	 * Only text is changed, not tags, links or code; numbers of no comment stay as they are.
	 */
	public function link(string $html, int $number): string
	{
		$parts = preg_split('~(<[^>]*>)~', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
		$skip = 0;
		foreach ($parts as $i => $part) {
			if (str_starts_with($part, '<')) {
				if (preg_match('~^<(a|code|pre)\b~i', $part)) {
					$skip++;
				} elseif ($skip > 0 && preg_match('~^</(a|code|pre)>~i', $part)) {
					$skip--;
				}
			} elseif ($skip === 0) {
				$parts[$i] = $this->linkText($part, $number);
			}
		}

		return implode('', $parts);
	}


	/** @return array<int, int[]> numbers of the comments that refer to a comment, by its number */
	public function getReplies(): array
	{
		return $this->replies;
	}


	private function linkText(string $text, int $number): string
	{
		return (string) preg_replace_callback($this->pattern, function (array $match) use ($number): string {
			$target = (int) $match[1];
			$name = $this->names[$target] ?? null;
			if ($name === null) {
				return $match[0];
			}

			// a name typed after the number belongs to the link only when it is the author of that comment
			$typedName = $match[2] ?? '';
			$rest = $typedName !== '' && !preg_match('~^' . $this->toPattern($name) . '$~u', $typedName)
				? substr($match[0], strlen('#' . $match[1]))
				: '';

			if ($target !== $number && !in_array($number, $this->replies[$target] ?? [], true)) {
				$this->replies[$target][] = $number;
			}

			return '<a href="#comment-' . $target . '" class="comment-ref">#' . $target . ' '
				. htmlspecialchars($name, ENT_QUOTES) . '</a>' . $rest;
		}, $text);
	}


	/** A name as it appears in the HTML of Texy: escaped, with its spaces, dashes and apostrophes typeset. */
	private function toPattern(string $name): string
	{
		return strtr(preg_quote(htmlspecialchars($name, ENT_NOQUOTES), '~'), self::Typography);
	}
}
