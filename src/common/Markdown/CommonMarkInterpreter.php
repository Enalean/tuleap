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
use League\CommonMark\Environment;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\MarkdownConverter;

final class CommonMarkInterpreter implements ContentInterpretor
{
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;
    /**
     * @var MarkdownConverter
     */
    private $converter;

    private function __construct(Codendi_HTMLPurifier $html_purifier, MarkdownConverter $converter)
    {
        $this->html_purifier = $html_purifier;
        $this->converter     = $converter;
    }

    public static function build(Codendi_HTMLPurifier $html_purifier, ExtensionInterface ...$extensions): self
    {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TableTLPExtension());
        foreach ($extensions as $extension) {
            $environment->addExtension($extension);
        }
        $environment->mergeConfig(['max_nesting_level' => 10]);

        return new self($html_purifier, new MarkdownConverter($environment));
    }

    public function getInterpretedContent(string $content): string
    {
        return $this->html_purifier->purify(
            $this->converter->convertToHtml($content),
            CODENDI_PURIFIER_FULL
        );
    }

    public function getInterpretedContentWithReferences(string $content, int $project_id): string
    {
        return $this->html_purifier->purifyHTMLWithReferences($this->converter->convertToHtml($content), $project_id);
    }

    public function getContentStrippedOfTags(string $content): string
    {
        return $this->html_purifier->purify(
            $this->converter->convertToHtml($content),
            Codendi_HTMLPurifier::CONFIG_STRIP_HTML
        );
    }
}
