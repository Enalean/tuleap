<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config\Command;

use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_ColumnMappingFieldDao;
use Cardwall_OnTop_ColumnMappingFieldValueDao;
use Cardwall_OnTop_Config_Command_DeleteColumns;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_DeleteColumnsTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private int $tracker_id;
    private Cardwall_OnTop_ColumnMappingFieldDao&MockObject $field_dao;
    private Cardwall_OnTop_ColumnMappingFieldValueDao&MockObject $value_dao;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private Cardwall_OnTop_Config_Command_DeleteColumns $command;

    protected function setUp(): void
    {
        $this->tracker_id = 666;
        $tracker          = TrackerTestBuilder::aTracker()->withId($this->tracker_id)->build();

        $this->field_dao = $this->createMock(Cardwall_OnTop_ColumnMappingFieldDao::class);
        $this->value_dao = $this->createMock(Cardwall_OnTop_ColumnMappingFieldValueDao::class);

        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->command = new Cardwall_OnTop_Config_Command_DeleteColumns($tracker, $this->dao, $this->field_dao, $this->value_dao);
    }

    public function testItDeletesOneColumn(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            [
                12 => ['label' => 'Todo'],
                14 => ['label' => ''],
            ]
        );
        $this->field_dao->expects(self::never())->method('deleteCardwall');
        $this->value_dao->expects($this->once())->method('deleteForColumn')->with($this->tracker_id, 14);
        $this->dao->expects($this->once())->method('delete')->with($this->tracker_id, 14);
        $this->command->execute($request);
    }

    public function testItDeletes2Columns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            [
                12 => ['label' => 'Todo'],
                13 => ['label' => ''],
                14 => ['label' => ''],
            ]
        );
        $this->field_dao->expects(self::never())->method('deleteCardwall');
        $matcher = self::exactly(2);
        $this->value_dao->expects($matcher)
            ->method('deleteForColumn')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(13, $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(14, $parameters[1]);
                }
            });
        $matcher = self::exactly(2);
        $this->dao->expects($matcher)
            ->method('delete')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(13, $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(14, $parameters[1]);
                }
            });
        $this->command->execute($request);
    }

    public function testItDeleteFieldMappingWhenRemoveTheLastColumn(): void
    {
        $request = new HTTPRequest();
        $request->set('column', [14 => ['label' => '']]);
        $this->field_dao->expects($this->once())->method('deleteCardwall')->with($this->tracker_id);
        $this->value_dao->expects($this->once())->method('deleteForColumn')->with($this->tracker_id, 14);
        $this->dao->expects($this->once())->method('delete')->with($this->tracker_id, 14);
        $this->command->execute($request);
    }

    public function testItDeletesAllColumns(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'column',
            [
                12 => ['label' => ''],
                13 => ['label' => ''],
            ]
        );
        $this->field_dao->expects($this->once())->method('deleteCardwall')->with($this->tracker_id);
        $matcher = self::exactly(2);
        $this->value_dao->expects($matcher)
            ->method('deleteForColumn')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(12, $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(13, $parameters[1]);
                }
            });
        $matcher = self::exactly(2);
        $this->dao->expects($matcher)
            ->method('delete')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(12, $parameters[1]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->tracker_id, $parameters[0]);
                    self::assertSame(13, $parameters[1]);
                }
            });
        $this->command->execute($request);
    }
}
