<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Project\MappingRegistry;

class Tracker_ReportFactory
{
    public const MAPPING_KEY = 'plugin_tracker_report';

    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct()
    {
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return Tracker_ReportFactory
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance(): void
    {
        self::$_instance = null;
    }

    /**
     * @param int $id the id of the report to retrieve
     * @return Tracker_Report | null
     */
    public function getReportById($id, $user_id, $store_in_session = true)
    {
        $row = $this->getDao()
            ->searchById($id, $user_id)
            ->getRow();
        $r   = null;
        if ($row) {
            $r = $this->getInstanceFromRow($row, $store_in_session);
        }
        return $r;
    }

    /**
     * @param int $tracker_id the id of the tracker
     * @param int|null $user_id the user who are searching for reports. He cannot access to other user's reports
     *                   if null then project reports instead of user ones
     * @return Tracker_Report[]
     */
    public function getReportsByTrackerId($tracker_id, $user_id)
    {
        $reports = [];
        foreach ($this->getDao()->searchByTrackerId($tracker_id, $user_id) as $row) {
            $reports[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $reports;
    }

    /**
     * @param int $tracker_id the id of the tracker
     * @return Tracker_Report|null
     */
    public function getDefaultReportsByTrackerId($tracker_id)
    {
        $report = null;
        if ($row = $this->getDao()->searchDefaultByTrackerId($tracker_id)->getRow()) {
            $report = $this->getInstanceFromRow($row);
        }
        return $report;
    }

    /**
     * @param int $tracker_id the id of the tracker
     * @return Tracker_Report|null
     */
    public function getDefaultReportByTrackerId($tracker_id)
    {
        $default_report = null;
        if ($row = $this->getDao()->searchDefaultReportByTrackerId($tracker_id)->getRow()) {
            $default_report = $this->getInstanceFromRow($row);
        }
        return $default_report;
    }

    /**
     * @param int $user_id the user who are searching for reports. He cannot access to other user's reports
     * @param array of reports
     */
    public function getReportsByUserId($user_id)
    {
        $reports = [];
        foreach ($this->getDao()->searchByUserId($user_id) as $row) {
            $reports[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $reports;
    }

    /**
     * Save a report
     *
     * @param Tracker_Report $report the report to save
     *
     * @return bool true if the save succeed
     */
    public function save(Tracker_Report $report)
    {
        $user = UserManager::instance()->getCurrentUser();
        return $this->getDao()->save(
            $report->id,
            $report->name,
            $report->description,
            $report->current_renderer_id,
            $report->parent_report_id,
            $report->user_id,
            $report->is_default,
            $report->tracker_id,
            $report->is_query_displayed,
            $report->is_in_expert_mode,
            $report->expert_query,
            $user->getId()
        );
    }

    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping, MappingRegistry $mapping_registry)
    {
        $report_mapping = [];
        foreach ($this->getReportsByTrackerId($from_tracker_id, null) as $from_report) {
            $new_report = $this->duplicateReport($from_report, $to_tracker_id, $field_mapping, null, $mapping_registry);

            $report_mapping[$from_report->getId()] = $new_report->getId();
        }

        return $report_mapping;
    }

    /**
     * Duplicate a report. The new report will have $from_report as parent.
     *
     * @param Tracker_Report $from_report   The report to copy
     * @param int            $to_tracker_id    The id of the target tracker
     * @param array          $field_mapping The mapping of the field, if any
     * @param int|null       $current_user_id  The id of the current user
     *
     * @return Tracker_Report the new report
     */
    public function duplicateReport(
        $from_report,
        $to_tracker_id,
        $field_mapping,
        $current_user_id,
        MappingRegistry $mapping_registry,
    ) {
        $report = null;
        //duplicate report info
        if ($id = $this->getDao()->duplicate($from_report->id, $to_tracker_id)) {
            if (! $mapping_registry->hasCustomMapping(self::MAPPING_KEY)) {
                $renderer_mapping = new ArrayObject();
                $mapping_registry->setCustomMapping(self::MAPPING_KEY, $renderer_mapping);
            } else {
                $renderer_mapping = $mapping_registry->getCustomMapping(self::MAPPING_KEY);
            }
            $renderer_mapping[$from_report->id] = $id;

            //duplicate report
            $report = $this->getReportById($id, $current_user_id);
            $report->duplicate($from_report, $field_mapping, $mapping_registry);
        }
        return $report;
    }

    public function duplicateReportSkeleton($from_report, $to_tracker_id, $current_user_id)
    {
        $report = null;
        //duplicate report info
        if ($id = $this->getDao()->duplicate($from_report->id, $to_tracker_id)) {
            $report = $this->getReportById($id, $current_user_id);
        }
        return $report;
    }



    protected $dao;
    /**
     * @return Tracker_ReportDao
     */
    protected function getDao()
    {
        if (! $this->dao) {
            $this->dao = new Tracker_ReportDao();
        }
        return $this->dao;
    }

    /**
     * @return Tracker_Report_CriteriaFactory
     */
    protected function getCriteriaFactory()
    {
        return Tracker_Report_CriteriaFactory::instance();
    }

    /**
     * @return Tracker_Report_RendererFactory
     */
    protected function getRendererFactory()
    {
        return Tracker_Report_RendererFactory::instance();
    }

    /**
     * @param array the row identifing a report
     * @return Tracker_Report
     */
    protected function getInstanceFromRow($row, $store_in_session = true)
    {
        $r = new Tracker_Report(
            $row['id'],
            $row['name'],
            $row['description'],
            $row['current_renderer_id'],
            $row['parent_report_id'],
            $row['user_id'],
            $row['is_default'],
            $row['tracker_id'],
            $row['is_query_displayed'],
            $row['is_in_expert_mode'],
            $row['expert_query'],
            $row['updated_by'],
            $row['updated_at']
        );
        if ($store_in_session) {
            $r->registerInSession();
            $this->initializeReportFromSession($r);
        }

        return $r;
    }

    /**
     * Creates a Tracker_Report Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported report
     * @param array            &$xmlMapping containing the newly created formElements indexed by their XML IDs
     * @param array            &$renderers_xml_mapping containing the newly created renderers indexed by their XML IDs
     * @param int              $group_id    the Id of the project
     *
     * @return Tracker_Report Object
     */
    public function getInstanceFromXML(
        $xml,
        &$xmlMapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
        $group_id,
    ) {
        $att                        = $xml->attributes();
        $row                        = ['name' => (string) $xml->name,
            'description' => (string) $xml->description,
        ];
        $row['is_default']          = isset($att['is_default']) ? (int) $att['is_default'] : 0;
        $row['is_query_displayed']  = isset($att['is_query_displayed']) ? (int) $att['is_query_displayed'] : 1;
        $row['is_in_expert_mode']   = isset($att['is_in_expert_mode']) ? (int) $att['is_in_expert_mode'] : 0;
        $row['expert_query']        = isset($att['expert_query']) ? (string) $att['expert_query'] : "";
        $row['id']                  = 'XML_IMPORT_REPORT_' . bin2hex(random_bytes(32));
        $row['current_renderer_id'] = 0;
        $row['parent_report_id']    = 0;
        $row['tracker_id']          = 0;
        $row['user_id']             = null;
        $row['group_id']            = $group_id;
        $row['updated_by']          = null;
        $row['updated_at']          = null;
        $report                     = $this->getInstanceFromRow($row);
        if (isset($att['id'])) {
            $reports_xml_mapping[(string) $att['id']] = $report;
        }
        // create criteria
        $report->criterias = [];
        foreach ($xml->criterias->criteria as $criteria) {
            $report_criteria = $this->getCriteriaFactory()->getInstanceFromXML($criteria, $report, $xmlMapping);
            if (! $report_criteria) {
                continue;
            }
            if (isset($criteria->criteria_value)) {
                $report_criteria->getField()->setCriteriaValueFromXML(
                    $report_criteria,
                    $criteria->criteria_value,
                    $xmlMapping
                );
            }

            $report->criterias[] = $report_criteria;
        }
        // create renderers
        $report->renderers = [];
        foreach ($xml->renderers->renderer as $renderer) {
            $rend                = $this->getRendererFactory()->getInstanceFromXML($renderer, $report, $xmlMapping);
            $report->renderers[] = $rend;

            if (isset($renderer['ID'])) {
                $renderers_xml_mapping[(string) $renderer['ID']] = $rend;
            }
        }

        return $report;
    }

    /**
     * Create new default report in the DataBase
     *
     * @param int trackerId of the created tracker
     * @param Object report
     *
     * @return int id of the newly created Report
     */
    public function saveObject($trackerId, $report)
    {
        $reportId = $this->getDao()->create(
            $report->name,
            $report->description,
            $report->current_renderer_id,
            $report->parent_report_id,
            $report->user_id,
            $report->is_default,
            $trackerId,
            $report->is_query_displayed,
            $report->is_in_expert_mode,
            $report->expert_query
        );
        //create criterias
        $reportDB = self::instance()->getReportById($reportId, null);
        if ($report->criterias) {
            foreach ($report->criterias as $criteria) {
                assert($criteria instanceof Tracker_Report_Criteria);
                $criteria_id = $reportDB->addCriteria($criteria);
                $criteria->setId($criteria_id);
                // Add criteria value
                $criteria->getField()->saveCriteriaValueFromXML($criteria);
            }
        }
        //create renderers
        if ($report->renderers) {
            foreach ($report->renderers as $renderer) {
                if ($renderer) {
                    $rendererId = $reportDB->addRenderer($renderer->name, $renderer->description, $renderer->getType());
                    $renderer->setId($rendererId);
                    $rendererDB = Tracker_Report_RendererFactory::instance()->getReportRendererById($rendererId, $reportDB);
                    $rendererDB->afterSaveObject($renderer);
                }
            }
        }
        return (int) $reportDB->id;
    }

    /**
     * Delete a report
     *
     * @return bool true if success
     */
    public function delete($report_id)
    {
        return $this->getDao()->delete($report_id);
    }

    private function initializeReportFromSession(Tracker_Report $tracker_report)
    {
        $report_session = $tracker_report->getReportSession();

        if ($report_session->get('is_in_expert_mode') !== null) {
            $tracker_report->setIsInExpertMode($report_session->get('is_in_expert_mode'));
        }

        if ($report_session->get('expert_query') !== null) {
            $tracker_report->setExpertQuery($report_session->get('expert_query'));
        }
    }
}
