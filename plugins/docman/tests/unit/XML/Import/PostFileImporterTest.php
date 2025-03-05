<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PostFileImporterTest extends TestCase
{
    public function testPostImport(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <versions>
                    <version><filename>image.png</filename></version>
                    <version><filename>file.txt</filename></version>
                </versions>
            </item>
            EOS
        );

        $node_importer = $this->createMock(NodeImporter::class);
        $item          = new Docman_Item();

        $version_importer = $this->createMock(VersionImporter::class);
        $matcher          = self::exactly(2);
        $version_importer->expects($matcher)->method('import')->willReturnCallback(function (...$parameters) use ($matcher, $item) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('image.png', (string) $parameters[0]->filename);
                self::assertSame(1, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('file.txt', (string) $parameters[0]->filename);
                self::assertSame(2, $parameters[2]);
            }
        });

        $importer = new PostFileImporter($version_importer, new NullLogger());
        $importer->postImport($node_importer, $node, $item);
    }
}
