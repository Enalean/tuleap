<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban;

use SimpleXMLElement;

require_once dirname(__FILE__) . '/../../../bootstrap.php';

class KanbanXmlImporterTest extends \TuleapTestCase
{
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \AgileDashboard_KanbanColumnManager
     */
    private $kanban_column_manager;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \AgileDashboard_KanbanManager
     */
    private $kanban_manager;
    /**
     * @var \AgileDashboard_KanbanColumnFactory
     */
    private $dashboard_kanban_column_factory;
    /**
     * @var KanbanXmlImporter
     */
    private $kanban_xml_importer;
    /**
     * @var \AgileDashboard_ConfigurationManager
     */
    private $agile_dashboard_configuration_manager;

    public function setUp()
    {
        parent::setUp();

        $this->dashboard_kanban_column_factory       = mock('\AgileDashboard_KanbanColumnFactory');
        $this->agile_dashboard_configuration_manager = mock('\AgileDashboard_ConfigurationManager');
        $this->kanban_column_manager                 = mock('\AgileDashboard_KanbanColumnManager');
        $this->kanban_manager                        = mock('\AgileDashboard_KanbanManager');

        $this->user                = aUser()->withId(101)->build();
        $this->project             = aMockProject()->withId(100)->build();
        $this->kanban_xml_importer = new KanbanXmlImporter(
            mock('\Logger'),
            $this->kanban_manager,
            $this->agile_dashboard_configuration_manager,
            $this->kanban_column_manager,
            mock('\AgileDashboard_KanbanFactory'),
            $this->dashboard_kanban_column_factory
        );
    }

    public function itDontContinueImportWhenKanbanNodeIsNotFound()
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <trackers>
                  <tracker xmlns="http://codendi.org/tracker" id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>t10</name>
                    <item_name>t11</item_name>
                    <description>t12</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t20</name>
                    <item_name>t21</item_name>
                    <description>t22</description>
                  </tracker>
                  <tracker xmlns="http://codendi.org/tracker" id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>
              </trackers>
              <agiledashboard></agiledashboard>
              </project>'
        );

        $field_mapping = mock('TrackerXmlFieldsMapping');

        $this->agile_dashboard_configuration_manager->expectNever('updateConfiguration');
        $this->kanban_xml_importer->import(
            $xml,
            array(),
            $this->project,
            $field_mapping,
            $this->user
        );
    }

    public function itImportsAKanbanWithItsOwnConfiguration()
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = mock('TrackerXmlFieldsMapping');

        $this->agile_dashboard_configuration_manager->expectOnce('updateConfiguration');
        expect($this->kanban_manager)->createKanban('My personal kanban', 50)->once();
        expect($this->dashboard_kanban_column_factory)->getColumnForAKanban()->count(3);
        expect($this->kanban_column_manager)->updateWipLimit()->count(3);

        $this->kanban_xml_importer->import(
            $xml,
            array(
                'T22' => 50
            ),
            $this->project,
            $field_mapping,
            $this->user
        );
    }

    public function itImportsAKanbanWithASimpleConfiguration()
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <kanban_list title="Kanban">
                  <kanban tracker_id="T22" name="My personal kanban" />
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = mock('TrackerXmlFieldsMapping');

        $this->agile_dashboard_configuration_manager->expectOnce('updateConfiguration');
        expect($this->kanban_manager)->createKanban('My personal kanban', 50)->once();

        $this->kanban_xml_importer->import(
            $xml,
            array(
                'T22' => 50
            ),
            $this->project,
            $field_mapping,
            $this->user
        );
    }

    public function itImportsMultipleKanban()
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="kanban" full-name="kanban" description="kanban" access="public">
              <agiledashboard>
                <kanban_list title="Kanban">
                    <kanban tracker_id="T22" name="My personal kanban">
                      <column wip="1" REF="V383"/>
                      <column wip="2" REF="V384"/>
                      <column wip="3" REF="V385"/>
                    </kanban>
                 <kanban tracker_id="T21" name="Support request" />
                </kanban_list>
              </agiledashboard>
            </project>'
        );
        $field_mapping = mock('TrackerXmlFieldsMapping');

        $this->agile_dashboard_configuration_manager->expectOnce('updateConfiguration');
        expect($this->kanban_manager)->createKanban()->count(2);
        expect($this->dashboard_kanban_column_factory)->getColumnForAKanban()->count(3);
        expect($this->kanban_column_manager)->updateWipLimit()->count(3);

        $this->kanban_xml_importer->import(
            $xml,
            array(
                'T22' => 50,
                'T21' => 51
            ),
            $this->project,
            $field_mapping,
            $this->user
        );
    }
}
