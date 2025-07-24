<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AutolinkExtensionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MarkdownConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $environment->addExtension(new AutolinkExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    public function testCreatesLinksAutomaticallyForSupportedSchemes(): void
    {
        $result = $this->converter->convert(
            <<<MARKDOWN_CONTENT
            https://example.com

            https://sub_domain.example.com

            http://example.com

            ftp://example.com

            foo@example.com
            MARKDOWN_CONTENT
        );

        self::assertEquals(
            <<<EXPECTED_HTML
            <p><a href="https://example.com">https://example.com</a></p>
            <p><a href="https://sub_domain.example.com">https://sub_domain.example.com</a></p>
            <p><a href="http://example.com">http://example.com</a></p>
            <p>ftp://example.com</p>
            <p>foo@example.com</p>\n
            EXPECTED_HTML,
            $result->getContent()
        );
    }
}
