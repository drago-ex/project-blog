<?php

declare(strict_types=1);

use App\Presentation\Front\Blog\CommentReferences;
use Tester\Assert;

require __DIR__ . '/../../../bootstrap.php';


test('a number links the comment and shows its author', function () {
	$references = new CommentReferences([1 => 'Jana', 2 => 'Petr']);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 Jana</a> I agree.</p>',
		$references->link('<p>#1 I agree.</p>', 2),
	);
	Assert::same([1 => [2]], $references->getReplies());
});


test('the author name inserted by the reply link is not repeated', function () {
	$references = new CommentReferences([1 => 'Jana Nová', 2 => 'Petr']);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 Jana Nová</a> thanks</p>',
		$references->link("<p>#1\u{a0}Jana Nová thanks</p>", 2),
	);
});


test('a name of another author after the number stays as text', function () {
	$references = new CommentReferences([1 => 'Jana', 2 => 'Petr', 3 => 'Eva']);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 Jana</a> Petr said it too</p>',
		$references->link('<p>#1 Petr said it too</p>', 3),
	);
});


test('unknown numbers, links, code and entities are left alone', function () {
	$references = new CommentReferences([1 => 'Jana']);
	$html = '<p>#7 and issue#1 and &#35;1 <a href="https://example.com/#1">#1</a> <code>#1</code></p>';
	Assert::same($html, $references->link($html, 2));
	Assert::same([], $references->getReplies());
});


test('a reply is recorded once and not to itself', function () {
	$references = new CommentReferences([1 => 'Jana', 2 => 'Petr']);
	$references->link('<p>#1 yes, #1 again, and #2</p>', 2);
	Assert::same([1 => [2]], $references->getReplies());
});


test('the author name is escaped', function () {
	$references = new CommentReferences([1 => '<b>Jana</b>']);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 &lt;b&gt;Jana&lt;/b&gt;</a></p>',
		$references->link('<p>#1</p>', 2),
	);
});


test('a longer name is not taken for a shorter one it starts with', function () {
	$references = new CommentReferences([1 => 'Jana', 2 => 'Jana Nová']);
	Assert::same(
		'<p><a href="#comment-2" class="comment-ref">#2 Jana Nová</a> thanks</p>',
		$references->link('<p>#2 Jana Nová thanks</p>', 3),
	);
});


test('a name is matched only as a whole word', function () {
	$references = new CommentReferences([1 => 'Jan']);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 Jan</a> Janička said</p>',
		$references->link('<p>#1 Janička said</p>', 2),
	);
});


test('a name typeset by Texy (no-break space, en dash, typographic apostrophe) is still the name', function () {
	$references = new CommentReferences([1 => 'Jana z Brna', 2 => 'Pavel - Novák', 3 => "O'Brien"]);
	Assert::same(
		'<p><a href="#comment-1" class="comment-ref">#1 Jana z Brna</a> thanks</p>',
		$references->link("<p>#1 Jana z\u{a0}Brna thanks</p>", 4),
	);
	Assert::same(
		'<p><a href="#comment-2" class="comment-ref">#2 Pavel - Novák</a> yes</p>',
		$references->link("<p>#2 Pavel \u{2013} Novák yes</p>", 4),
	);
	Assert::same(
		'<p><a href="#comment-3" class="comment-ref">#3 O&#039;Brien</a> ok</p>',
		$references->link("<p>#3 O\u{2019}Brien ok</p>", 4),
	);
});
