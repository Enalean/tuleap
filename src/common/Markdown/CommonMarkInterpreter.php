<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Markdown;

use Codendi_HTMLPurifier;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;

final class CommonMarkInterpreter implements ContentInterpretor
{
    private Codendi_HTMLPurifier $html_purifier;
    private MarkdownConverter $converter;

    private function __construct(Codendi_HTMLPurifier $html_purifier, MarkdownConverter $converter)
    {
        $this->html_purifier = $html_purifier;
        $this->converter     = $converter;
    }

    public static function build(Codendi_HTMLPurifier $html_purifier, ExtensionInterface ...$extensions): self
    {
        $environment = new Environment([
            'max_nesting_level' => 10,
            'max_delimiters_per_line' => 500, // Used to prevent a DoS, see https://github.com/thephpleague/commonmark/security/advisories/GHSA-c2pc-g5qf-rfrf

        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TableTLPExtension());
        foreach ($extensions as $extension) {
            $environment->addExtension($extension);
        }

        return new self($html_purifier, new MarkdownConverter($environment));
    }

    #[\Override]
    public function getInterpretedContent(string $content): string
    {
        return $this->html_purifier->purify(
            $this->converter->convert($content)->getContent(),
            CODENDI_PURIFIER_FULL
        );
    }

    #[\Override]
    public function getInterpretedContentWithReferences(string $content, int $project_id): string
    {
        return $this->html_purifier->purifyHTMLWithReferences($this->converter->convert($content)->getContent(), $project_id);
    }

    #[\Override]
    public function getContentStrippedOfTags(string $content): string
    {
        return $this->html_purifier->purify(
            $this->converter->convert($content)->getContent(),
            Codendi_HTMLPurifier::CONFIG_STRIP_HTML
        );
    }
}
