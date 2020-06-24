<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_Report_HeaderInReportPresenter
{

    private $browse_instructions;
    private $title;
    private $select_report_url;
    private $reports_selector;
    private $options_dropdown;
    private $options_params;
    private $save_button;
    private $updated_by_username;
    private $has_changed_classname;
    private $report_name;
    private $warnings;

    public function __construct(
        $browse_instructions,
        $title,
        $select_report_url,
        $reports_selector,
        Templating_Presenter_ButtonDropdowns $options_dropdown,
        $options_params,
        $save_button,
        $updated_by_username,
        $has_changed_classname,
        $report_name,
        $warnings
    ) {
        $this->browse_instructions   = $browse_instructions;
        $this->title                 = $title;
        $this->select_report_url     = $select_report_url;
        $this->reports_selector      = $reports_selector;
        $this->options_dropdown      = $options_dropdown;
        $this->options_params        = $options_params;
        $this->save_button           = $save_button;
        $this->updated_by_username   = $updated_by_username;
        $this->has_changed_classname = $has_changed_classname;
        $this->report_name           = $report_name;
        $this->warnings              = $warnings;
    }

    public function has_browse_instructions()
    {
        return $this->browse_instructions != '';
    }

    public function browse_instructions()
    {
        return $this->browse_instructions;
    }

    public function title()
    {
        return $this->title;
    }

    public function select_report_url()
    {
        return $this->select_report_url;
    }

    public function reports_selector()
    {
        return $this->reports_selector;
    }

    public function options_dropdown()
    {
        return $this->options_dropdown;
    }

    public function saveas_url()
    {
        return '?' . http_build_query(array_merge($this->options_params, array('func' => Tracker_Report::ACTION_SAVEAS)));
    }

    public function can_save()
    {
        return $this->save_button !== false;
    }

    public function save_dropdown()
    {
        return $this->save_button;
    }

    public function revert_url()
    {
        return '?' . http_build_query(array_merge($this->options_params, array('func' => Tracker_Report::ACTION_CLEANSESSION)));
    }

    public function has_changed_classname()
    {
        return $this->has_changed_classname;
    }

    public function haschanged_explainations()
    {
        return dgettext('tuleap-tracker', 'Report has been modified. You can either');
    }

    public function isobsolete_explainations()
    {
        return sprintf(dgettext('tuleap-tracker', 'Report has been modified by <span class="tracker_report_updated_by">%1$s</span>. You can either:'), $this->updated_by_username);
    }

    public function report_haschanged_and_isobsolete_explainations()
    {
        return sprintf(dgettext('tuleap-tracker', 'Report has been modified by <span class="tracker_report_updated_by">%1$s</span>. You can either:'), $this->updated_by_username);
    }

    public function report_name()
    {
        return dgettext('tuleap-tracker', 'Report name:');
    }

    public function save_report_as()
    {
        return dgettext('tuleap-tracker', 'Save report as');
    }

    public function copy_of()
    {
        return dgettext('tuleap-tracker', 'Copy of') . ' ' . $this->report_name;
    }

    public function cancel()
    {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }

    public function or_lbl()
    {
        return $GLOBALS['Language']->getText('global', 'or');
    }

    public function save_new_report()
    {
        return dgettext('tuleap-tracker', 'Save new report');
    }

    public function revert()
    {
        return dgettext('tuleap-tracker', 'Revert');
    }

    public function has_warnings()
    {
        return count($this->warnings) > 0;
    }

    public function warnings()
    {
        return $this->warnings;
    }
}
