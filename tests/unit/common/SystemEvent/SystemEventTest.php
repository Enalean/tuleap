<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEventTest extends TestCase
{
    public function testItRetrievesAParameterByItsIndex(): void
    {
        $event = $this->buildSystemEvent('B::A');
        $this->assertEquals('A', $event->getParameter(1));
        $this->assertEquals('B', $event->getRequiredParameter(0));
    }

    public function testItReturnsNullIfIndexNotFound(): void
    {
        $event = $this->buildSystemEvent('');
        $this->assertNull($event->getParameter(0));
    }

    public function testItRaisesAnExceptionWhenParameterIsRequiredAndNotFound(): void
    {
        $event = $this->buildSystemEvent('');
        $this->expectException('SystemEventMissingParameterException');
        $event->getRequiredParameter(0);
    }

    public function testItProperlyEncodesAndDecodesData(): void
    {
        $data = ['coin' => 'String that contains :: (the param separator)'];
        $this->assertEquals($data, SystemEvent::decode(SystemEvent::encode($data)));
    }

    private function buildSystemEvent(string $parameter): SystemEvent
    {
        $event = new class (
            1,
            'type',
            'owner',
            $parameter,
            1,
            'NEW',
            1,
            1,
            1,
            'log'
        ) extends SystemEvent {
            public function verbalizeParameters($with_link)
            {
            }
        };

        return $event;
    }
}
