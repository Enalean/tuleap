<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Label\REST;

use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tuleap\Label\Labelable;
use Tuleap\Label\LabelableDao;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Project\Label\LabelDao;

final class LabelsUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $project_id;

    private LabelDao&MockObject $project_label_dao;

    private LabelsUpdater $updater;

    private LabelableDao&MockObject $item_label_dao;

    private ProjectHistoryDao&MockObject $history_dao;

    private Labelable&MockObject $item;

    protected function setUp(): void
    {
        $this->item = $this->createMock(\Tuleap\Label\Labelable::class);
        $this->item->method('getId')->willReturn(101);
        $this->item_label_dao    = $this->createMock(\Tuleap\Label\LabelableDao::class);
        $this->project_label_dao = $this->createMock(\Tuleap\Project\Label\LabelDao::class);
        $this->history_dao       = $this->createMock(ProjectHistoryDao::class);
        $this->updater           = new LabelsUpdater($this->project_label_dao, $this->item_label_dao, $this->history_dao);
        $this->project_id        = 66;

        $this->project_label_dao->method('startTransaction');
        $this->project_label_dao->method('rollBack');
        $this->project_label_dao->method('commit');
        $this->project_label_dao->method('checkThatAllLabelIdsExistInProjectInTransaction');
        $this->item_label_dao->method('addLabelsInTransaction');
        $this->item_label_dao->method('removeLabelsInTransaction');
    }

    public function testItAddsAndRemoveLabels(): void
    {
        $body         = new LabelsPATCHRepresentation();
        $body->add    = [
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3),
        ];
        $body->remove = [
            $this->buildLabelRepresentation(4),
            $this->buildLabelRepresentation(5),
            $this->buildLabelRepresentation(6),
        ];

        $this->item_label_dao->expects(self::once())->method('addLabelsInTransaction')->with(101, [1, 2, 3]);
        $this->item_label_dao->expects(self::once())->method('removeLabelsInTransaction')->with(101, [4, 5, 6]);

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItUsesTransaction(): void
    {
        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelRepresentation(1),
        ];

        $this->project_label_dao->expects(self::once())->method('startTransaction');
        $this->project_label_dao->expects(self::once())->method('commit');

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItOnlyAddsLabels(): void
    {
        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3),
        ];

        $this->item_label_dao->expects(self::once())->method('addLabelsInTransaction')->with(101, [1, 2, 3]);
        $this->item_label_dao->expects(self::once())->method('removeLabelsInTransaction')->with(101, []);

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItOnlyRemovesLabels(): void
    {
        $body         = new LabelsPATCHRepresentation();
        $body->remove = [
            $this->buildLabelRepresentation(4),
            $this->buildLabelRepresentation(5),
            $this->buildLabelRepresentation(6),
        ];

        $this->item_label_dao->expects(self::once())->method('addLabelsInTransaction')->with(101, []);
        $this->item_label_dao->expects(self::once())->method('removeLabelsInTransaction')->with(101, [4, 5, 6]);

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotKnowHowToAddAndRemoveTheSameLabel(): void
    {
        $body         = new LabelsPATCHRepresentation();
        $body->add    = [
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3),
        ];
        $body->remove = [
            $this->buildLabelRepresentation(1),
        ];

        self::expectException(\Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException::class);
        $this->item_label_dao->expects(self::never())->method('addLabelsInTransaction');
        $this->item_label_dao->expects(self::never())->method('removeLabelsInTransaction');
        $this->project_label_dao->expects(self::once())->method('rollback');

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotAddLabelThatIsNotInProject(): void
    {
        $this->project_label_dao->method('checkThatAllLabelIdsExistInProjectInTransaction')->with(66, [1])->willThrowException(new UnknownLabelException());

        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelRepresentation(1),
        ];

        $this->expectException(\Tuleap\Label\UnknownLabelException::class);
        $this->item_label_dao->expects(self::never())->method('addLabelsInTransaction');
        $this->item_label_dao->expects(self::never())->method('removeLabelsInTransaction');
        $this->project_label_dao->expects(self::once())->method('rollback');

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItCreatesLabelToAdd(): void
    {
        $this->project_label_dao->method('createIfNeededInTransaction')->with(66, 'Emergency Fix', self::anything())->willReturn(10);

        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelToCreateRepresentation('Emergency Fix'),
        ];

        $this->item_label_dao->expects(self::once())->method('addLabelsInTransaction')->with(101, [1, 2, 10]);

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItTrimsLabelToAdd(): void
    {
        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelToCreateRepresentation('  Emergency Fix  '),
        ];

        $this->project_label_dao->expects(self::once())->method('createIfNeededInTransaction')->with(66, 'Emergency Fix', self::anything());

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotAddEmptyLabels(): void
    {
        $body      = new LabelsPATCHRepresentation();
        $body->add = [
            $this->buildLabelRepresentation(1),
            $this->buildLabelToCreateRepresentation(' '),
        ];

        self::expectException(\Tuleap\Label\REST\UnableToAddEmptyLabelException::class);
        $this->project_label_dao->expects(self::never())->method('createIfNeededInTransaction');
        $this->item_label_dao->expects(self::never())->method('addLabelsInTransaction');

        $this->updater->update($this->project_id, $this->item, $body);
    }

    private function buildLabelRepresentation(int $id): LabelRepresentation
    {
        $label     = new LabelRepresentation();
        $label->id = $id;

        return $label;
    }

    private function buildLabelToCreateRepresentation(string $name): LabelRepresentation
    {
        $label        = new LabelRepresentation();
        $label->label = $name;

        return $label;
    }
}
