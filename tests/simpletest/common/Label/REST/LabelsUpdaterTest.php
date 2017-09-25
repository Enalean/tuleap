<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label\REST;

use Tuleap\Label\Labelable;
use Tuleap\Label\LabelableDao;
use Tuleap\Label\UnknownLabelException;
use Tuleap\Project\Label\LabelDao;
use TuleapTestCase;

class LabelsUpdaterTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $this->item              = stub('Tuleap\Label\Labelable')->getId()->returns(101);
        $this->item_label_dao    = mock('Tuleap\Label\LabelableDao');
        $this->project_label_dao = mock('Tuleap\Project\Label\LabelDao');
        $this->history_dao       = mock('ProjectHistoryDao');
        $this->updater           = new LabelsUpdater($this->project_label_dao, $this->item_label_dao, $this->history_dao);
        $this->project_id        = 66;
    }

    public function itAddsAndRemoveLabels()
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

        expect($this->item_label_dao)->addLabelsInTransaction(101, array(1, 2, 3))->once();
        expect($this->item_label_dao)->removeLabelsInTransaction(101, array(4, 5, 6))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itUsesTransaction()
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1)
        );

        expect($this->project_label_dao)->startTransaction()->once();
        expect($this->project_label_dao)->commit()->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itOnlyAddsLabels()
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelRepresentation(3)
        );

        expect($this->item_label_dao)->addLabelsInTransaction(101, array(1, 2, 3))->once();
        expect($this->item_label_dao)->removeLabelsInTransaction(101, array())->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itOnlyRemovesLabels()
    {
        $body = new LabelsPATCHRepresentation();
        $body->remove = array(
            $this->buildLabelRepresentation(4),
            $this->buildLabelRepresentation(5),
            $this->buildLabelRepresentation(6)
        );

        expect($this->item_label_dao)->addLabelsInTransaction(101, array())->once();
        expect($this->item_label_dao)->removeLabelsInTransaction(101, array(4, 5, 6))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itDoesNotKnowHowToAddAndRemoveTheSameLabel()
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

        $this->expectException('Tuleap\Label\REST\UnableToAddAndRemoveSameLabelException');
        expect($this->item_label_dao)->addLabelsInTransaction()->never();
        expect($this->item_label_dao)->removeLabelsInTransaction()->never();
        expect($this->project_label_dao)->rollback()->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itDoesNotAddLabelThatIsNotInProject()
    {
        stub($this->project_label_dao)
            ->checkThatAllLabelIdsExistInProjectInTransaction(66, array(1))
            ->throws(new UnknownLabelException());

        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
        );

        $this->expectException('Tuleap\Label\UnknownLabelException');
        expect($this->item_label_dao)->addLabelsInTransaction()->never();
        expect($this->item_label_dao)->removeLabelsInTransaction()->never();
        expect($this->project_label_dao)->rollback()->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itCreatesLabelToAdd()
    {
        stub($this->project_label_dao)->createIfNeededInTransaction(66, 'Emergency Fix', '*')->returns(10);

        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelRepresentation(2),
            $this->buildLabelToCreateRepresentation('Emergency Fix')
        );

        expect($this->item_label_dao)->addLabelsInTransaction(101, array(1, 2, 10))->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itTrimsLabelToAdd()
    {
        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelToCreateRepresentation('  Emergency Fix  ')
        );

        expect($this->project_label_dao)->createIfNeededInTransaction(66, 'Emergency Fix', '*')->once();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    public function itDoesNotAddEmptyLabels()
    {

        $body = new LabelsPATCHRepresentation();
        $body->add = array(
            $this->buildLabelRepresentation(1),
            $this->buildLabelToCreateRepresentation(' ')
        );

        $this->expectException('Tuleap\Label\REST\UnableToAddEmptyLabelException');
        expect($this->project_label_dao)->createIfNeededInTransaction()->never();
        expect($this->item_label_dao)->addLabelsInTransaction()->never();

        $this->updater->update($this->project_id, $this->item, $body);
    }

    private function buildLabelRepresentation($id)
    {
        $label = new LabelRepresentation();
        $label->id = $id;

        return $label;
    }

    private function buildLabelToCreateRepresentation($name)
    {
        $label = new LabelRepresentation();
        $label->label = $name;

        return $label;
    }
}
