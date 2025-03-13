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

namespace Tuleap\Tracker\Creation;

use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class DefaultTemplatesCollectionTest extends TestCase
{
    public function testEmptyCollection(): void
    {
        $collection = new DefaultTemplatesCollection();

        self::assertFalse($collection->has('whatever'));
        self::assertEmpty($collection->getSortedDefaultTemplatesRepresentations());
    }

    public function testItHasATemplate(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add('my-template', $this->createStub(DefaultTemplate::class));

        self::assertTrue($collection->has('my-template'));
        self::assertFalse($collection->has('another-template'));
    }

    public function testItAddsTemplate(): void
    {
        $collection = new DefaultTemplatesCollection();

        self::assertFalse($collection->has('my-template'));
        $collection->add('my-template', $this->createStub(DefaultTemplate::class));
        self::assertTrue($collection->has('my-template'));
    }

    public function testItReturnsTheXMLFileOfATemplate(): void
    {
        $collection = new DefaultTemplatesCollection();
        $template   = $this->createMock(DefaultTemplate::class);
        $template->method('getXmlFile')->willReturn('/path');
        $collection->add('my-template', $template);

        self::assertEquals('/path', $collection->getXmlFile('my-template'));
    }

    public function testItThrowsExceptionIfTemplateIsNotFound(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add('my-template', $this->createStub(DefaultTemplate::class));

        $this->expectException(OutOfBoundsException::class);
        $collection->getXmlFile('another-template');
    }

    public function testGetSortedDefaultTemplatesRepresentations(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add(
            'default-bug',
            new DefaultTemplate(
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'Description', 'clockwork-orange'),
                '/path/to/xml'
            )
        );
        $collection->add(
            'default-activity',
            new DefaultTemplate(
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'Description', 'fiesta-red'),
                '/path/to/xml'
            )
        );

        self::assertEquals(
            [
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'Description', 'fiesta-red'),
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'Description', 'clockwork-orange'),
            ],
            $collection->getSortedDefaultTemplatesRepresentations()
        );
    }
}
