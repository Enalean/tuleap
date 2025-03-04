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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TableTLPExtensionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MarkdownConverter $converter;

    protected function setUp(): void
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());

        $environment->addExtension(new TableTLPExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    public function testRendersTableWithTheTLPClass(): void
    {
        $result = $this->converter->convert(
            <<<MARKDOWN_TABLE
            | Case ID | Case Acronym | Case Full Name |
            |---------|--------------|----------------|
            | 301     | PW           | Plane Wave     |
            MARKDOWN_TABLE
        );

        $this->assertEquals(
            <<<EXPECTED_HTML
            <table class="tlp-table">
            <thead>
            <tr>
            <th>Case ID</th>
            <th>Case Acronym</th>
            <th>Case Full Name</th>
            </tr>
            </thead>
            <tbody>
            <tr>
            <td>301</td>
            <td>PW</td>
            <td>Plane Wave</td>
            </tr>
            </tbody>
            </table>\n
            EXPECTED_HTML,
            $result->getContent()
        );
    }
}
