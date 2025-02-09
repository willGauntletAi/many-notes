<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VaultNode;

final readonly class ProcessVaultNodeLinks
{
    public function handle(VaultNode $node): void
    {
        $node->links()->detach();

        if ((string) $node->content === '') {
            return;
        }

        $pattern = <<<REGEX
            /
            (?<!\!)         # Negative lookbehind: Ensure the link is not preceded by "!"
            \[.+?\]         # Match a markdown-style link text [any text]
            \(              # Match opening parenthesis "("
                (.*?\.md)   # Capture group 1: Match a Markdown file ending with '.md'
                (?:\s".+")? # Optional: Match a title in quotes after the filename
            \)              # Match closing parenthesis ")"
            /xi
            REGEX;
        /** @var string $content */
        $content = $node->content;
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[1] === []) {
            return;
        }

        $linkPaths = array_column($matches[1], 0);
        $linkPositions = array_column($matches[0], 1);
        $links = array_map(
            fn (string $path, int $position): array => ['path' => $path, 'position' => $position],
            $linkPaths,
            $linkPositions,
        );

        foreach ($links as $link) {
            /**
             * @var string $fullPath
             *
             * @phpstan-ignore-next-line larastan.noUnnecessaryCollectionCall
             */
            $fullPath = $node->ancestorsAndSelf()->get()->last()->full_path;
            $path = new ResolveTwoPaths()->handle($fullPath, $link['path']);

            $destinationNode = new GetVaultNodeFromPath()->handle($node->vault_id, $path, $node->parent_id);

            if (is_null($destinationNode)) {
                continue;
            }

            $node->links()->attach($destinationNode->id, ['position' => $link['position']]);
        }
    }
}
