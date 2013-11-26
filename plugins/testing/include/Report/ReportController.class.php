<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

/**
 * Controller for a report resource
 */
class Testing_Report_ReportController extends TestingController {

    const RENDER_PREFIX = 'Report/';

    public function __construct(
        Codendi_Request $request,
        Testing_Defect_DefectDao $defects_dao,
        TestingConfiguration $conf,
        Testing_Campaign_MatrixRowPresenterCollectionFactory $matrix_factory
    ) {
        parent::__construct('testing', $request);
        $this->defects_dao      = $defects_dao;
        $this->matrix_factory   = $matrix_factory;
        $this->defect_tracker   = $conf->getDefectTracker();
        $this->release_tracker  = $conf->getReleaseTracker();
        $this->cycle_tracker    = $conf->getCycleTracker();
        $this->testcase_tracker = $conf->getTestCaseTracker();
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render(self::RENDER_PREFIX . $name, $presenter);
    }

    public function index() {
        $presenter = new Testing_Report_ReportPresenter(
            $this->getReleaseDefectCollectionPresenter(),
            $this->matrix_factory->getReleasePresenter($this->testcase_tracker, $this->cycle_tracker)
        );
        $this->render(self::RENDER_PREFIX . __FUNCTION__, $presenter);
    }

    private function getReleaseDefectCollectionPresenter() {
        $collection = new Testing_Report_ReleaseDefectPresenterCollection();
        foreach ($this->defects_dao->searchDefectsAndReleases($this->defect_tracker->getId(), $this->release_tracker->getId()) as $row) {
            $collection->append(
                new Testing_Report_ReleaseDefectPresenter(
                    new Testing_Release_ReleaseInfoPresenter(
                        $this->getProject(),
                        new Testing_Release_ArtifactRelease($row['release_id'])
                    ),
                    new Testing_Defect_DefectPresenter(
                        new Testing_Defect_Defect($row['defect_id'])
                    )
                )
            );
        }
        return $collection;
    }
}
