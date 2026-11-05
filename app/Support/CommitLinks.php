<?php

namespace App\Support;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\AbstractWebResource;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

/**
 * Turns commit hashes into links to the commit in the board's repository:
 * 7 to 40 lowercase hex characters with at least one digit and one letter
 * (so words like "decade" stay words), standing on their own. Hashes in
 * plain text and inline code made of just the hash are linked; code
 * blocks, links and images are left alone.
 */
class CommitLinks implements ExtensionInterface
{
	private const HASH = '(?<![\w\/.#@-])(?=[a-f]*\d)(?=\d*[a-f])[0-9a-f]{7,40}(?![\w\/-])';

	public function __construct(private string $repository) {}

	public function register(EnvironmentBuilderInterface $environment): void
	{
		$environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
			$nodes = [];

			foreach ($event->getDocument()->iterator() as $node) {
				if (($node instanceof Text || $node instanceof Code) && ! $this->insideLink($node)) {
					$nodes[] = $node;
				}
			}

			foreach ($nodes as $node) {
				$node instanceof Code ? $this->linkCode($node) : $this->linkText($node);
			}
		});
	}

	/**
	 * The URL of a commit, e.g. https://github.com/owner/repo/commit/93a2f49.
	 */
	public static function url(string $repository, string $hash): string
	{
		return rtrim($repository, '/').'/commit/'.$hash;
	}

	private function linkText(Text $node): void
	{
		$parts = preg_split('/('.self::HASH.')/', $node->getLiteral(), -1, PREG_SPLIT_DELIM_CAPTURE);

		if ($parts === false || count($parts) === 1) {
			return;
		}

		foreach ($parts as $i => $part) {
			if ($part === '') {
				continue;
			}

			// The captured hashes sit at the odd offsets.
			$node->insertBefore($i % 2 === 1 ? $this->link($part, new Text($part)) : new Text($part));
		}

		$node->detach();
	}

	private function linkCode(Code $node): void
	{
		$hash = trim($node->getLiteral());

		if (preg_match('/^'.self::HASH.'$/', $hash) === 1) {
			$node->replaceWith($this->link($hash, new Code($hash)));
		}
	}

	private function link(string $hash, Node $label): Link
	{
		$link = new Link(self::url($this->repository, $hash));
		$link->appendChild($label);

		return $link;
	}

	private function insideLink(Node $node): bool
	{
		for ($parent = $node->parent(); $parent !== null; $parent = $parent->parent()) {
			if ($parent instanceof AbstractWebResource) {
				return true;
			}
		}

		return false;
	}
}
