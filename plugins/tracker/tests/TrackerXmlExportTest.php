<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenter;

require_once 'bootstrap.php';

class TrackerXmlExportTest extends TuleapTestCase
{

    private $tracker1;
    private $tracker2;
    private $xml_export;

    /** @var  Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory */
    private $nature_presenter_factory;
    /**
     * @var \Tracker_Artifact_XMLExport
     */
    private $tracker_artifact_XMLexport;

    public function setUp()
    {
        parent::setUp();

        $this->tracker1  = stub('Tracker')->exportToXML()->returns('<tracker>');
        $this->tracker2  = stub('Tracker')->exportToXML()->returns('<tracker>');
        $tracker_factory = stub('TrackerFactory')->getTrackersByGroupId()->returns(array($this->tracker1, $this->tracker2));
        stub($tracker_factory)->getTrackerById(456)->returns($this->tracker1);

        $this->nature_presenter_factory = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory');
        $this->artifact_link_dao        = mock('Tuleap\Tracker\Admin\ArtifactLinksUsageDao');
        $this->tracker_artifact_XMLexport = mock('Tracker_Artifact_XMLExport');

        $this->xml_export = new TrackerXmlExport(
            $tracker_factory,
            mock('Tracker_Workflow_Trigger_RulesManager'),
            mock('XML_RNGValidator'),
            $this->tracker_artifact_XMLexport,
            mock('UserXMLExporter'),
            \Mockery::spy(\EventManager::class),
            $this->nature_presenter_factory,
            $this->artifact_link_dao
        );
    }

    public function testExportToXml()
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = aMockProject()->withId(123)->build();

        stub($this->tracker1)->isActive()->returns(true);
        stub($this->tracker2)->isActive()->returns(true);

        expect($this->tracker1)->exportToXML()->once();
        expect($this->tracker2)->exportToXML()->once();

        $type = new NaturePresenter('fixed_in', '', '', true);

        stub($this->nature_presenter_factory)->getAllTypesEditableInProject()->returns(array(
            $type
        ));

        $this->xml_export->exportToXMl(
            $project,
            $xml_content,
            mock('PFUser')
        );
    }

    public function testExportToXmlDoNotIncludeDeletedTrackers()
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $project     = aMockProject()->withId(123)->build();

        stub($this->tracker1)->isActive()->returns(true);
        stub($this->tracker2)->isActive()->returns(false);

        expect($this->tracker1)->exportToXML()->once();
        expect($this->tracker2)->exportToXML()->never();

        $type = new NaturePresenter('fixed_in', '', '', true);

        stub($this->nature_presenter_factory)->getAllTypesEditableInProject()->returns(array(
            $type
        ));

        $this->xml_export->exportToXMl(
            $project,
            $xml_content,
            mock('PFUser')
        );
    }

    public function testExportSingleTracker()
    {
        $xml_content = new SimpleXMLElement('<project/>');
        $tracker_id  = 456;
        $user        = mock('PFUser');

        stub($this->tracker1)->isActive()->returns(true);

        expect($this->tracker1)->exportToXML()->never();
        expect($this->tracker1)->exportToXMLInProjectExportContext()->once();
        expect($this->tracker_artifact_XMLexport)->export()->once();

        $archive = mock('Tuleap\Project\XML\Export\ArchiveInterface');

        $this->xml_export->exportSingleTrackerToXml($xml_content, $tracker_id, $user, $archive);
    }
}
