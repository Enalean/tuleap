<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_Report_SOAP extends Tracker_Report
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    private $soap_criteria = array();

    public function __construct(
        PFUser $current_user,
        Tracker $tracker,
        PermissionsManager $permissions_manager,
        Tracker_ReportDao $dao,
        Tracker_FormElementFactory $formelement_factory
    ) {
        $id = $name = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $tracker_id = $is_query_displayed = $is_in_expert_mode = $expert_query = $updated_by = $updated_at = 0;
        parent::__construct(
            $id,
            $name,
            $description,
            $current_renderer_id,
            $parent_report_id,
            $user_id,
            $is_default,
            $tracker_id,
            $is_query_displayed,
            $is_in_expert_mode,
            $expert_query,
            $updated_by,
            $updated_at
        );

        $this->current_user        = $current_user;
        $this->tracker             = $tracker;
        $this->permissions_manager = $permissions_manager;
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
        $this->criteria            = array();
    }

    public function setSoapCriteria($criteria) {
        $this->soap_criteria = $criteria;
    }

    public function getCriteria() {
        $rank = 0;

        if (is_array($this->soap_criteria)) {
            foreach ($this->soap_criteria as $key => $value) {
                $is_advanced = false;
                $formelement = $this->formelement_factory->getFormElementByName(
                    $this->getTracker()->getId(),
                    $value->field_name
                );

                if ($formelement && $formelement->userCanRead($this->current_user)) {
                    $criteria = new Tracker_Report_Criteria(
                        0,
                        $this,
                        $formelement,
                        $rank,
                        $is_advanced
                    );
                    $formelement->setCriteriaValueFromSOAP($criteria, $value->value);
                    $this->criteria[$formelement->getId()] = $criteria;
                    $rank++;
                }
            }
        }

        return $this->criteria;
    }
}
