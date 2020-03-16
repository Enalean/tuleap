<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Report_REST extends Tracker_Report
{

    public const OPERATOR_PROPERTY_NAME = 'operator';
    public const VALUE_PROPERTY_NAME    = 'value';
    public const DEFAULT_OPERATOR       = 'contains';
    public const OPERATOR_CONTAINS      = 'contains';
    public const OPERATOR_EQUALS        = '=';
    public const OPERATOR_BETWEEN       = 'between';
    public const OPERATOR_GREATER_THAN  = '>';
    public const OPERATOR_LESS_THAN     = '<';

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    private $allowed_operators = array(
        self::DEFAULT_OPERATOR,
        self::OPERATOR_EQUALS,
        self::OPERATOR_BETWEEN,
        self::OPERATOR_GREATER_THAN,
        self::OPERATOR_LESS_THAN,
    );

    protected $rest_criteria = array();

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

    /**
     * @param sting $criteria
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    public function setRESTCriteria($criteria)
    {
        $criteria = json_decode(stripslashes($criteria), true);
        $this->checkForJsonErrors();
        $this->harmoniseCriteria($criteria);

        $this->rest_criteria = $criteria;
    }

    private function checkForJsonErrors()
    {
        $error = '';
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return;
            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
                $error = 'Criteria syntax error, invalid JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded criteria';
                break;
            default:
                $error = 'Unknown JSON criteria error';
                break;
        }

        throw new Tracker_Report_InvalidRESTCriterionException($error);
    }

    /**
     * Transforms each criterion into the same format.
     *
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    private function harmoniseCriteria(&$criteria)
    {
        if (! is_array($criteria)) {
            $criteria = array();
        }

        foreach ($criteria as $field => $criterion) {
            if ($this->isCriterionBasic($criterion)) {
                $criterion = $criteria[$field] = array(
                    self::OPERATOR_PROPERTY_NAME => self::DEFAULT_OPERATOR,
                    self::VALUE_PROPERTY_NAME    => $criterion
                );
            }

            if (! is_array($criterion)) {
                throw new Tracker_Report_InvalidRESTCriterionException('Criterion for field ' . $field . ' is malformed');
            }

            $this->checkCriterionProperties($criterion, $field);
        }
    }

    /**
     * There are multiple formats for a criteron. However, all adavanced formats must be of the form
     * "field_id" => array(
     *     "operator" => "contains",
     *     "value"    => [string, array]
     *  )
     * @return bool
     */
    private function isCriterionBasic($criterion)
    {
        if (is_array($criterion)) {
            if (isset($criterion[self::OPERATOR_PROPERTY_NAME]) || isset($criterion[self::VALUE_PROPERTY_NAME])) {
                return false;
            }
        }

        return true;
    }

    private function checkCriterionProperties($criterion, $field)
    {
        if (! isset($criterion[self::OPERATOR_PROPERTY_NAME]) || ! isset($criterion[self::VALUE_PROPERTY_NAME])) {
            throw new Tracker_Report_InvalidRESTCriterionException('Criterion for field ' . $field . ' is malformed');
        }
        if (! in_array($criterion[self::OPERATOR_PROPERTY_NAME], $this->allowed_operators)) {
            throw new Tracker_Report_InvalidRESTCriterionException('Invalid operator for field ' . $field);
        }
    }

    public function getCriteria()
    {
        $rank       = 0;
        $tracker_id = $this->getTracker()->getId();

        foreach ($this->rest_criteria as $field_identifier => $criterion) {
            $formelement = $this->formelement_factory->getFormElementById($field_identifier);

            if (! $formelement) {
                $formelement = $this->formelement_factory->getFormElementByName($tracker_id, $field_identifier);
            }

            if ($formelement && $formelement->userCanRead($this->current_user)) {
                $this->addCriterionToFormElement($formelement, $criterion, $rank);
                $rank++;
            }
        }

        return $this->criteria;
    }

    private function addCriterionToFormElement($formelement, $criterion, $rank)
    {
        $is_advanced = false;

        $criteria = new Tracker_Report_Criteria(
            0,
            $this,
            $formelement,
            $rank,
            $is_advanced
        );

        $set = $formelement->setCriteriaValueFromREST($criteria, $criterion);
        if ($set) {
            $this->criteria[$formelement->getId()] = $criteria;
        }
    }
}
