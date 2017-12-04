<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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


namespace Tuleap\AgileDashboard;

use AgileDashboard_FirstKanbanCreator;
use TuleapTestCase;

require_once dirname(__FILE__).'/../../bootstrap.php';

class FirstKanbanCreatorTest extends TuleapTestCase
{
    /**
     * @var AgileDashboard_FirstKanbanCreator
     */
    private $kanban_creator;

    public function setUp()
    {
        parent::setUp();

        $project               = aMockProject()->withId(123)->build();
        $this->tracker_factory = mock('TrackerFactory');
        $this->kanban_manager  = mock('AgileDashboard_KanbanManager');
        $this->kanban_factory  = mock('AgileDashboard_KanbanFactory');
        $this->xml_import      = mock('TrackerXmlImport');
        $this->report_updater  = mock('Tuleap\AgileDashboard\REST\v1\Kanban\TrackerReport\TrackerReportUpdater');
        $this->report_factory  = mock('Tracker_ReportFactory');
        $this->kanban_creator  = new AgileDashboard_FirstKanbanCreator(
            $project,
            $this->kanban_manager,
            $this->tracker_factory,
            $this->xml_import,
            $this->kanban_factory,
            $this->report_updater,
            $this->report_factory
        );

        $this->user   = aUser()->withId(130)->build();
        $this->kanban = stub('AgileDashboard_Kanban')->getId()->returns(1);
        $this->tracker = aMockTracker()->withId(101)->build();
    }

    public function itCreatesAFirstKanban()
    {
        stub($this->kanban_manager)->getTrackersUsedAsKanban()->returns(array());
        stub($this->tracker_factory)->isShortNameExists()->returns(false);
        stub($this->xml_import)->createFromXMLFile()->returns($this->tracker);
        stub($this->kanban_factory)->getKanban()->returns($this->kanban);

        expect($this->kanban_manager)->createKanban()->once();

        $this->kanban_creator->createFirstKanban($this->user);
    }

    public function itAddsAssignedToMeReportAsSelectableReport()
    {
        $default_report = stub('Tracker_Report')->getId()->returns(10);
        stub($default_report)->isPublic()->returns(true);
        stub($default_report)->getName()->returns("Default");

        $assigned_to_me_report = stub('Tracker_Report')->getId()->returns(20);
        stub($assigned_to_me_report)->isPublic()->returns(true);
        stub($assigned_to_me_report)->getName()->returns("Assigned to me");

        stub($this->kanban_manager)->getTrackersUsedAsKanban()->returns(array());
        stub($this->tracker_factory)->isShortNameExists()->returns(false);
        stub($this->xml_import)->createFromXMLFile()->returns($this->tracker);
        stub($this->kanban_factory)->getKanban()->returns($this->kanban);
        stub($this->kanban_manager)->createKanban()->returns(1);
        stub($this->report_factory)->getReportsByTrackerId(101, null)->returns(
            array($default_report, $assigned_to_me_report)
        );

        expect($this->report_updater)->save($this->kanban, array(20))->once();

        $this->kanban_creator->createFirstKanban($this->user);
    }

    public function itDoesNotAddAReportAsSelectableReportIfAssignedToMeReportNotFound()
    {
        $default_report = stub('Tracker_Report')->getId()->returns(10);
        stub($default_report)->isPublic()->returns(true);
        stub($default_report)->getName()->returns("Default");

        stub($this->kanban_manager)->getTrackersUsedAsKanban()->returns(array());
        stub($this->tracker_factory)->isShortNameExists()->returns(false);
        stub($this->xml_import)->createFromXMLFile()->returns($this->tracker);
        stub($this->kanban_factory)->getKanban()->returns($this->kanban);
        stub($this->kanban_manager)->createKanban()->returns(1);
        stub($this->report_factory)->getReportsByTrackerId(101, null)->returns(
            array($default_report)
        );

        expect($this->report_updater)->save()->never();

        $this->kanban_creator->createFirstKanban($this->user);
    }

    public function itDoesNotAddAReportAsSelectableReportIfNoPublicReportFound()
    {
        $default_report = stub('Tracker_Report')->getId()->returns(10);
        stub($default_report)->isPublic()->returns(false);
        stub($default_report)->getName()->returns("Default");

        stub($this->kanban_manager)->getTrackersUsedAsKanban()->returns(array());
        stub($this->tracker_factory)->isShortNameExists()->returns(false);
        stub($this->xml_import)->createFromXMLFile()->returns($this->tracker);
        stub($this->kanban_factory)->getKanban()->returns($this->kanban);
        stub($this->kanban_manager)->createKanban()->returns(1);
        stub($this->report_factory)->getReportsByTrackerId(101, null)->returns(
            array()
        );

        expect($this->report_updater)->save()->never();

        $this->kanban_creator->createFirstKanban($this->user);
    }

    public function itDoesNotAddTwiceAssignedToMeReportAsSelectableReport()
    {
        $default_report = stub('Tracker_Report')->getId()->returns(10);
        stub($default_report)->isPublic()->returns(true);
        stub($default_report)->getName()->returns("Default");

        $assigned_to_me_report = stub('Tracker_Report')->getId()->returns(15);
        stub($assigned_to_me_report)->isPublic()->returns(true);
        stub($assigned_to_me_report)->getName()->returns("Assigned to me");

        $other_assigned_to_me_report = stub('Tracker_Report')->getId()->returns(20);
        stub($other_assigned_to_me_report)->isPublic()->returns(true);
        stub($other_assigned_to_me_report)->getName()->returns("Assigned to me");

        stub($this->kanban_manager)->getTrackersUsedAsKanban()->returns(array());
        stub($this->tracker_factory)->isShortNameExists()->returns(false);
        stub($this->xml_import)->createFromXMLFile()->returns($this->tracker);
        stub($this->kanban_factory)->getKanban()->returns($this->kanban);
        stub($this->kanban_manager)->createKanban()->returns(1);
        stub($this->report_factory)->getReportsByTrackerId(101, null)->returns(
            array($default_report, $assigned_to_me_report, $other_assigned_to_me_report)
        );

        expect($this->report_updater)->save($this->kanban, '*')->once();

        $this->kanban_creator->createFirstKanban($this->user);
    }
}
