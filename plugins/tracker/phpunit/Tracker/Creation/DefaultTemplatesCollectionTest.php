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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DefaultTemplatesCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEmptyCollection(): void
    {
        $collection = new DefaultTemplatesCollection();

        $this->assertFalse($collection->has('whatever'));
        $this->assertEmpty($collection->getSortedDefaultTemplatesRepresentations());
    }

    public function testItHasATemplate(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add('my-template', Mockery::mock(DefaultTemplate::class));

        $this->assertTrue($collection->has('my-template'));
        $this->assertFalse($collection->has('another-template'));
    }

    public function testItAddsTemplate(): void
    {
        $collection = new DefaultTemplatesCollection();

        $this->assertFalse($collection->has('my-template'));
        $collection->add('my-template', Mockery::mock(DefaultTemplate::class));
        $this->assertTrue($collection->has('my-template'));
    }

    public function testItReturnsTheXMLFileOfATemplate(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add(
            'my-template',
            Mockery::mock(DefaultTemplate::class)
                ->shouldReceive(['getXmlFile' => '/path'])
                ->getMock()
        );

        $this->assertEquals('/path', $collection->getXmlFile('my-template'));
    }

    public function testItThrowsExceptionIfTemplateIsNotFound(): void
    {
        $collection = new DefaultTemplatesCollection();
        $collection->add('my-template', Mockery::mock(DefaultTemplate::class));

        $this->expectException(\OutOfBoundsException::class);
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

        $this->assertEquals(
            [
                new TrackerTemplatesRepresentation('default-activity', 'Activities', 'Description', 'fiesta-red'),
                new TrackerTemplatesRepresentation('default-bug', 'Bugs', 'Description', 'clockwork-orange')
            ],
            $collection->getSortedDefaultTemplatesRepresentations()
        );
    }
}
