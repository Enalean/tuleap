<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocWithContextTest extends TestCase
{
    public function testItCanAddContext(): void
    {
        $document = new ArtidocDocument(['item_id' => 1]);

        $artidoc = new ArtidocWithContext($document);

        self::assertSame($document, $artidoc->document);
        self::assertNull($artidoc->getContext('foo'));

        $artidoc = $artidoc->withContext('foo', 123);
        self::assertSame(123, $artidoc->getContext('foo'));

        $artidoc = $artidoc->withContext('foo', 456);
        self::assertSame(456, $artidoc->getContext('foo'));

        $object  = new \stdClass();
        $artidoc = $artidoc->withContext('bar', $object);
        self::assertSame(456, $artidoc->getContext('foo'));
        self::assertSame($object, $artidoc->getContext('bar'));
    }
}
