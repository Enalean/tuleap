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

use ProjectHistoryDao;
use Tuleap\Label\Labelable;
use Tuleap\Label\LabelableDao;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Project\Label\LabelDao;

final class LabelsUpdaterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var int */
    private $project_id;

    /** @var LabelDao */
    private $project_label_dao;

    /** @var LabelsUpdater */
    private $updater;

    /** @var LabelableDao */
    private $item_label_dao;

    /** @var ProjectHistoryDao */
    private $history_dao;

    /** @var Labelable */
    private $item;

    protected function setUp(): void
    {
        $this->item              = \Mockery::mock(\Tuleap\Label\Labelable::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $this->item_label_dao    = \Mockery::spy(\Tuleap\Label\LabelableDao::class);
        $this->project_label_dao = \Mockery::spy(\Tuleap\Project\Label\LabelDao::class);
        $this->history_dao       = \Mockery::mock(ProjectHistoryDao::class);
        $this->updater           = new LabelsUpdater($this->project_label_dao, $this->item_label_dao, $this->history_dao);
        $this->project_id        = 66;
    }

    public function testItAddsAndRemoveLabels(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3)
        );
        $body->remove = array(
            $this->buildLabelRepresentation(4),
            $this->buildLabelRepresentation(5),
            $this->buildLabelRepresentation(6)
        );

        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->with(101, array(1, 2, 3))->once();
        $this->item_label_dao->shouldReceive('removeLabelsInTransaction')->with(101, array(4, 5, 6))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItUsesTransaction(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1)
        );

        $this->project_label_dao->shouldReceive('startTransaction')->once();
        $this->project_label_dao->shouldReceive('commit')->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItOnlyAddsLabels(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3)
        );

        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->with(101, array(1, 2, 3))->once();
        $this->item_label_dao->shouldReceive('removeLabelsInTransaction')->with(101, array())->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItOnlyRemovesLabels(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->remove = array(
            $this->buildLabelRepresentation(4),
            $this->buildLabelRepresentation(5),
            $this->buildLabelRepresentation(6)
        );

        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->with(101, array())->once();
        $this->item_label_dao->shouldReceive('removeLabelsInTransaction')->with(101, array(4, 5, 6))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotKnowHowToAddAndRemoveTheSameLabel(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3)
        );
        $body->remove = array(
            $this->buildLabelRepresentation(1),
        );

        $this->expectException(\Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException::class);
        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->never();
        $this->item_label_dao->shouldReceive('removeLabelsInTransaction')->never();
        $this->project_label_dao->shouldReceive('rollback')->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotAddLabelThatIsNotInProject(): void
    {
        $this->project_label_dao->shouldReceive('checkThatAllLabelIdsExistInProjectInTransaction')->with(66, array(1))->andThrows(new UnknownLabelException());

        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
        );

        $this->expectException(\Tuleap\Label\UnknownLabelException::class);
        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->never();
        $this->item_label_dao->shouldReceive('removeLabelsInTransaction')->never();
        $this->project_label_dao->shouldReceive('rollback')->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItCreatesLabelToAdd(): void
    {
        $this->project_label_dao->shouldReceive('createIfNeededInTransaction')->with(66, 'Emergency Fix', \Mockery::any())->andReturns(10);

        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelToCreateRepresentation('Emergency Fix')
        );

        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->with(101, array(1, 2, 10))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItTrimsLabelToAdd(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelToCreateRepresentation('  Emergency Fix  ')
        );

        $this->project_label_dao->shouldReceive('createIfNeededInTransaction')->with(66, 'Emergency Fix', \Mockery::any())->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function testItDoesNotAddEmptyLabels(): void
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelToCreateRepresentation(' ')
        );

        $this->expectException(\Tuleap\Label\REST\UnableToAddEmptyLabelException::class);
        $this->project_label_dao->shouldReceive('createIfNeededInTransaction')->never();
        $this->item_label_dao->shouldReceive('addLabelsInTransaction')->never();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    private function buildLabelRepresentation(int $id): LabelRepresentation
    {
        $label = new LabelRepresentation();
        $label->id = $id;

        return $label;
    }

    private function buildLabelToCreateRepresentation(string $name): LabelRepresentation
    {
        $label = new LabelRepresentation();
        $label->label = $name;

        return $label;
    }
}
