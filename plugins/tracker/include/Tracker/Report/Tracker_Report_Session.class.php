<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Report_Session extends Codendi_Session
{

    protected $report_id;
    protected $report_namespace;


    public function __construct($report_id)
    {
        parent::__construct();
        $this->report_id         = $report_id;
        $this->report_namespace  = $report_id;
        if (! isset($this->session['trackers']['reports'][$this->report_namespace]['has_changed'])) {
            $this->session['trackers']['reports'][$this->report_namespace] = [
                'has_changed'   => false,
                'checkout_date' => $_SERVER['REQUEST_TIME'],
            ];
        }
        $this->session_namespace = &$this->session['trackers']['reports'][$this->report_namespace];
        $this->session_namespace_path = ".trackers.reports.$this->report_namespace";
    }

    public function hasChanged()
    {
        //return $this->get('has_changed');
        return $this->session['trackers']['reports'][$this->report_namespace]['has_changed'];
    }

    public function setHasChanged($has_changed = true)
    {
        //$this->set('has_changed', $has_changed);
        $this->session['trackers']['reports'][$this->report_namespace]['has_changed'] = $has_changed;
    }

    /**
     * Make a copy of the entire report subtree
     * @param int $id report id to copy
     * @param int $new_id destination report id
     */
    public function copy($id, $new_id)
    {
        $previous_session_namespace_path = $this->getSessionNamespacePath();
        //going up to session root
        $this->changeSessionNamespace(".");
        $report      = $this->get("trackers.reports.$id");
        //copy
        $report_copy = $report;
        //now we need to reindex renderers
        $i = 0;
        $report_copy['renderers'] = ($report_copy['renderers']) ? $report_copy['renderers'] : [];
        foreach ($report_copy['renderers'] as $renderer_id => $renderer) {
            $i = $i - 1;
            //set new id for previously existing renderers (before adding new renderers in session)
            $report_copy['renderers'][$i] = $report_copy['renderers'][$renderer_id];
            $report_copy['renderers'][$i]['id'] = $i;
            //removing old id
            if ($renderer_id >= 0) {
                unset($report_copy['renderers'][$renderer_id]);
            }
            if (isset($report_copy['renderers'][$i]['charts'])) {
                $j = 0;
                foreach ($report_copy['renderers'][$i]['charts'] as $chart_id => $chart) {
                    $j = $j - 1;
                    $report_copy['renderers'][$i]['charts'][$j] = $report_copy['renderers'][$i]['charts'][$chart_id];
                    $report_copy['renderers'][$i]['charts'][$j]['id'] = $j;
                    if ($chart_id >= 0) {
                        unset($report_copy['renderers'][$i]['charts'][$chart_id]);
                    }
                }
            }
        }
        $this->set("trackers.reports.$new_id", $report_copy);
        $this->set("trackers.reports.$id.has_changed", false);
        $this->set("trackers.reports.$new_id.has_changed", false);
        //going back down to the current namespace
        $this->changeSessionNamespace($previous_session_namespace_path);
    }



    //                  CRITERIA SESSION METHODS
    /**
     * remove a criterion (field) from session
     * @param int $field_id
     */
    public function removeCriterion($field_id)
    {
        $this->set("criteria.$field_id.is_removed", 1);
    }

    /**
     * Store value and options (is_advanced) for a given criterion (field)
     * NOTICE : value is overwritten
     * NOTICE : opts are not overwritten if empty because they may have been set earlier through Ajax request
     * @param int $field_id
     * @param mixed $value
     * @param array $opts
     * @todo empty value may allow to set options only?
     */
    public function storeCriterion($field_id, $value, $opts = [])
    {
        $this->set("criteria.{$field_id}.value", $value);
        if (isset($opts['is_advanced'])) {
            $this->set("criteria.{$field_id}.is_advanced", $opts['is_advanced']);
        }
        if (! $this->get("criteria.$field_id.is_removed")) {
            $this->set("criteria.$field_id.is_removed", 0);
        }
    }

    public function storeAdditionalCriterion(Tracker_Report_AdditionalCriterion $additional_criterion)
    {
        $key = $additional_criterion->getKey();
        $this->set("additional_criteria.{$key}.value", $additional_criterion->getValue());
    }

    /**
     * Update a criterion
     * NOTICE: Do not set value if empty
     *
     */
    public function updateCriterion($field_id, $value, $opts = [])
    {
        if (! empty($value) || is_array($value)) {
            $this->set("criteria.{$field_id}.value", $value);
        }
        if (isset($opts['is_advanced'])) {
            $this->set("criteria.{$field_id}.is_advanced", $opts['is_advanced']);
        }
        if (isset($opts['is_removed'])) {
            $this->set("criteria.{$field_id}.is_removed", $opts['is_removed']);
        }
    }

    public function &getCriteria()
    {
        $criteria = &$this->get('criteria');
        return $criteria;
    }

    public function getAdditionalCriteria()
    {
        return $this->get('additional_criteria');
    }

    /**
     * Returns a given criterion if it is already in session
     * @param int $field_id
     * @return mixed array or false if the criterion does not exist
     */
    public function &getCriterion($field_id)
    {
        $criterion = &$this->get("criteria.{$field_id}");
        return $criterion;
    }

   /**
    * remove a renderer from session
    * @param int $renderer_id
    */
    public function removeRenderer($renderer_id)
    {
        $renderers =& $this->get('renderers');
        if (isset($renderers[$renderer_id])) {
            unset($renderers[$renderer_id]);
        }
    }

   /**
    * rename a renderer in session
    * @param int $renderer_id
    */
    public function renameRenderer($renderer_id, $name, $description)
    {
        $renderers =& $this->get("renderers.$renderer_id");
        if ($renderers) {
            $renderers['name'] = $name;
            $renderers['description'] = $description;
        }
    }

   /**
    * move a renderer in session
    * @param array $renderers_rank
    */
    public function moveRenderer($renderers_rank)
    {
        foreach ($renderers_rank as $rank => $renderer_id) {
            $this->set("renderers.{$renderer_id}.rank", $rank);
        }
    }

    public function storeRenderer($renderer_id, $data, $opts = [])
    {
        $this->set("renderers.{$renderer_id}", $data);
    }

    public function storeExpertMode()
    {
        $this->set('is_in_expert_mode', true);
    }

    public function storeNormalMode()
    {
        $this->set('is_in_expert_mode', false);
    }

    public function storeExpertQuery($expert_query)
    {
        $this->set('expert_query', $expert_query);
    }
}
