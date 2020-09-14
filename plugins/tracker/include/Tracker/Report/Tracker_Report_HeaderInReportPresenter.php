<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
    /**
     * @var string
     */
    public $item_name;
    /**
     * @var bool
     */
    public $is_user_anonymous;
    /**
     * @var string
     */
    public $browse_instructions;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $select_report_url;

    /**
     * @var string
     */
    public $reports_selector;
    /**
     * @var Templating_Presenter_ButtonDropdowns
     */
    public $options_dropdown;
    /**
     * @var array
     */
    public $options_params;
    /**
     * @var Templating_Presenter_SplitButtonDropdowns | false
     */
    public $save_dropdown;
    /**
     * @var string
     */
    public $updated_by_username;
    /**
     * @var string
     */
    public $has_changed_classname;
    /**
     * @var string
     */
    public $artifact_creation_url;
    /**
     * @var string
     */
    private $report_name;
    /**
     * @var array
     */
    public $warnings;

    public function __construct(
        string $browse_instructions,
        string $title,
        string $select_report_url,
        string $reports_selector,
        Templating_Presenter_ButtonDropdowns $options_dropdown,
        array $options_params,
        $save_button,
        string $has_changed_classname,
        array $warnings,
        Tracker_Report $report,
        PFUser $user
    ) {
        $this->browse_instructions   = $browse_instructions;
        $this->title                 = $title;
        $this->select_report_url     = $select_report_url;
        $this->reports_selector      = $reports_selector;
        $this->options_dropdown      = $options_dropdown;
        $this->options_params        = $options_params;
        $this->save_dropdown         = $save_button;
        $this->updated_by_username   = $report->getLastUpdaterUserName();
        $this->has_changed_classname = $has_changed_classname;
        $this->report_name           = $report->getName();
        $this->warnings              = $warnings;
        $this->item_name             = $report->getTracker()->getItemName();
        $this->is_user_anonymous     = $user->isAnonymous();
        $this->artifact_creation_url = $report->getTracker()->getSubmitUrl();
    }

    public function has_browse_instructions(): bool
    {
        return $this->browse_instructions != '';
    }

    public function saveas_url(): string
    {
        return '?' . http_build_query(array_merge($this->options_params, ['func' => Tracker_Report::ACTION_SAVEAS]));
    }

    public function can_save(): bool
    {
        return $this->save_dropdown !== false;
    }

    public function revert_url(): string
    {
        return '?' . http_build_query(array_merge($this->options_params, ['func' => Tracker_Report::ACTION_CLEANSESSION]));
    }

    public function haschanged_explainations(): string
    {
        return dgettext('tuleap-tracker', 'Report has been modified. You can either');
    }

    public function isobsolete_explainations(): string
    {
        return sprintf(dgettext('tuleap-tracker', 'Report has been modified by <span class="tracker_report_updated_by">%1$s</span>. You can either:'), $this->updated_by_username);
    }

    public function report_haschanged_and_isobsolete_explainations(): string
    {
        return sprintf(dgettext('tuleap-tracker', 'Report has been modified by <span class="tracker_report_updated_by">%1$s</span>. You can either:'), $this->updated_by_username);
    }

    public function report_name(): string
    {
        return dgettext('tuleap-tracker', 'Report name:');
    }

    public function save_report_as(): string
    {
        return dgettext('tuleap-tracker', 'Save report as');
    }

    public function copy_of(): string
    {
        return dgettext('tuleap-tracker', 'Copy of') . ' ' . $this->report_name;
    }

    public function cancel(): string
    {
        return $GLOBALS['Language']->getText('global', 'btn_cancel');
    }

    public function or_lbl(): string
    {
        return $GLOBALS['Language']->getText('global', 'or');
    }

    public function save_new_report(): string
    {
        return dgettext('tuleap-tracker', 'Save new report');
    }

    public function revert(): string
    {
        return dgettext('tuleap-tracker', 'Revert');
    }

    public function has_warnings(): bool
    {
        return count($this->warnings) > 0;
    }
}
