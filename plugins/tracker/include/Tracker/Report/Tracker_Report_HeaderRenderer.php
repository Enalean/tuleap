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

use Tuleap\Layout\IncludeAssets;

class Tracker_Report_HeaderRenderer
{

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    /**
     * @var Tracker_ReportFactory
     */
    private $report_factory;

    public function __construct(
        Tracker_ReportFactory $report_factory,
        Codendi_HTMLPurifier $purifier,
        TemplateRenderer $renderer
    ) {
        $this->report_factory       = $report_factory;
        $this->purifier             = $purifier;
        $this->renderer             = $renderer;
    }

    public function displayHeader(
        Tracker_IFetchTrackerSwitcher $layout,
        Codendi_Request $request,
        PFUser $current_user,
        Tracker_Report $report,
        $report_can_be_modified
    ) {
        $link_artifact_id = (int) $request->get('link-artifact-id');
        if ($report_can_be_modified) {
            $title            = '';
            $breadcrumbs      = array();
            $params           = array('body_class' => array('in_tracker_report'));
            $toolbar          = $report->getTracker()->getDefaultToolbar();

            $report->getTracker()->displayHeader($layout, $title, $breadcrumbs, $toolbar, $params);
        }

        if ($request->get('pv')) {
            return;
        }

        $reports = $this->report_factory->getReportsByTrackerId($report->tracker_id, $current_user->getId());

        if ($link_artifact_id) {
            $this->displayHeaderInArtifactLinkModal($layout, $request, $current_user, $report, $reports, $link_artifact_id);
        } else {
            $this->displayHeaderInReport($request, $current_user, $report, $reports, $report_can_be_modified);
        }
    }

    private function displayHeaderInReport(
        Codendi_Request $request,
        PFUser $current_user,
        Tracker_Report $report,
        array $reports,
        $report_can_be_modified
    ) {
        $options_params = array(
            'tracker'       => $report->tracker_id,
            'select_report' => $report->id,
        );

        $is_admin = $report->getTracker()->userIsAdmin($current_user);
        $warnings = $this->getMissingPublicReportWarning($reports, $is_admin);

        $assets = new IncludeAssets(__DIR__ . '/../../../../../src/www/assets/trackers', '/assets/trackers');
        $GLOBALS['HTML']->includeFooterJavascriptFile($assets->getFileURL('tracker-report-expert-mode.js'));
        $this->renderer->renderToPage(
            'header_in_report',
            new Tracker_Report_HeaderInReportPresenter(
                $this->purifier->purify($report->getTracker()->getBrowseInstructions(), CODENDI_PURIFIER_FULL),
                $GLOBALS['Language']->getText('plugin_tracker_report', 'current_report'),
                $this->getSelectReportUrl($request, $report),
                $this->getReportSelector($report, $reports),
                $this->getReportOptionsDropdown($current_user, $report, $options_params, $reports),
                $options_params,
                $this->getSaveOrRevert($current_user, $report, $options_params, $report_can_be_modified),
                $report->getLastUpdaterUserName(),
                $this->getClassNameHasChanged($report),
                $report->getName(),
                $warnings
            )
        );
    }

    private function getSaveOrRevert(PFUser $current_user, Tracker_Report $report, array $options_params, $report_can_be_modified)
    {
        if ($current_user->isAnonymous() || !$report_can_be_modified) {
            return false;
        }

        if ($report->userCanUpdate($current_user)) {
            $default_save = new Templating_Presenter_ButtonDropdownsOption(
                'tracker_report_updater_save',
                $GLOBALS['Language']->getText('plugin_tracker_report', 'save'),
                false,
                '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_SAVE)))
            );
            $extra_save =  array(
                new Templating_Presenter_ButtonDropdownsOptionWithModal(
                    'tracker_report_updater_saveas',
                    $GLOBALS['Language']->getText('plugin_tracker_report', 'save_as'),
                    false,
                    '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_SAVEAS))) . '#tracker_report_updater_saveas-modal'
                )
            );
        } elseif (! $current_user->isAnonymous()) {
            $default_save = new Templating_Presenter_ButtonDropdownsOptionWithModal(
                'tracker_report_updater_saveas',
                $GLOBALS['Language']->getText('plugin_tracker_report', 'save_as'),
                false,
                '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_SAVEAS))) . '#tracker_report_updater_saveas-modal'
            );
            $extra_save =  array();
        }

        return new Templating_Presenter_SplitButtonDropdowns(
            'tracker_report_save_dropdown',
            'btn-primary',
            $default_save,
            $extra_save
        );
    }

    private function getClassNameHasChanged(Tracker_Report $report)
    {
        $is_obsolete = $report->isObsolete();

        $classname_has_changed = '';
        if ($report->report_session->hasChanged() && !$is_obsolete) {
            $classname_has_changed .= 'tracker_report_haschanged';
        }
        if ($report->report_session->hasChanged() && $is_obsolete) {
            $classname_has_changed .= 'tracker_report_haschanged_and_isobsolete';
        }
        if (!$report->report_session->hasChanged() && $is_obsolete) {
            $classname_has_changed .= 'tracker_report_isobsolete';
        }

        return $classname_has_changed;
    }

    private function getReportOptionsDropdown(PFUser $current_user, Tracker_Report $report, array $options_params, array $reports)
    {
        return new Templating_Presenter_ButtonDropdowns(
            'tracker_report_options',
            $GLOBALS['Language']->getText('plugin_tracker_report', 'options'),
            $this->getReportOptionsList($current_user, $report, $options_params, $reports)
        );
    }

    private function getReportOptionsList(PFUser $current_user, Tracker_Report $report, array $options_params, array $reports)
    {
        $states_list = array();
        $actions_list = array();

        if ($report->getTracker()->userIsAdmin($current_user)) {
            $is_public = ($report->user_id ? 0 : 1);
            $states_list[] = new Templating_Presenter_ButtonDropdownsOption(
                'tracker_report_updater_scope',
                $GLOBALS['Language']->getText('plugin_tracker_report', 'public'),
                $is_public,
                '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_SCOPE, 'report_scope_public' => intval(! $is_public))))
            );
        }

        if (count($reports) > 1 && $report->getTracker()->userIsAdmin($current_user)) {
            $states_list[] = new Templating_Presenter_ButtonDropdownsOption(
                'tracker_report_updater_default',
                $GLOBALS['Language']->getText('plugin_tracker_report', 'default'),
                $report->is_default,
                '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_DEFAULT, 'report_default' => intval(! $report->is_default))))
            );
        }

        if (! $current_user->isAnonymous()) {
            $actions_list[] = new Templating_Presenter_ButtonDropdownsOptionWithModal(
                'tracker_report_updater_duplicate',
                $GLOBALS['Language']->getText('plugin_tracker_report', 'save_as'),
                false,
                '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_SAVEAS))) . '#tracker_report_updater_saveas-modal'
            );
        }

        if (count($reports) > 1) {
            if ($report->user_id || ($report->user_id == null && $report->getTracker()->userIsAdmin($current_user) && $report->nbPublicReport($reports) > 1)) {
                $actions_list[] = new Templating_Presenter_ButtonDropdownsOption(
                    'tracker_report_updater_delete',
                    $GLOBALS['Language']->getText('global', 'delete'),
                    false,
                    '?' . http_build_query(array_merge($options_params, array('func' => Tracker_Report::ACTION_DELETE)))
                );
            }
        }

        if (count($actions_list)) {
            $states_list[] = new Templating_Presenter_ButtonDropdownsOptionDivider();
        }

        return array_merge($states_list, $actions_list);
    }

    private function displayHeaderInArtifactLinkModal(Tracker_IFetchTrackerSwitcher $layout, Codendi_Request $request, PFUser $current_user, Tracker_Report $report, array $reports, $link_artifact_id)
    {
        $project = null;
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($link_artifact_id);
        if ($artifact) {
            $project = $artifact->getTracker()->getProject();
        }

        $this->renderer->renderToPage(
            'header_in_artifact_link_modal',
            new Tracker_Report_HeaderInArtifactLinkModalPresenter(
                $GLOBALS['Language']->getText('plugin_tracker_report', 'current_report'),
                $layout->fetchTrackerSwitcher($current_user, '<br />', $project, $report->getTracker()),
                $this->getSelectReportUrl($request, $report),
                $this->getReportSelector($report, $reports)
            )
        );
    }

    private function getSelectReportUrl(Codendi_Request $request, Tracker_Report $report)
    {
        $params = array('tracker' => $report->tracker_id);

        if ($request->exist('criteria')) {
            $params['criteria'] = $request->get('criteria');
        }

        return '?' . http_build_query($params);
    }

    private function getReportSelector(Tracker_Report $report, array $reports)
    {
        $options = '';
        if (count($reports) > 1) {
            $options = '<select id="tracker_select_report" name="select_report">';
            $personal = '';
            $public   = '';
            foreach ($reports as $r) {
                $prefix = '<option value="' . $r->id . '"';
                $suffix = '>' . $this->purifier->purify($r->name, CODENDI_PURIFIER_CONVERT_HTML)  . '</option>';
                $selected = $r->id == $report->id ? 'selected="selected"' : '';
                if ($r->isPublic()) {
                    $public .= $prefix . ' ' . $selected . $suffix;
                } else {
                    $personal .= $prefix . ' ' . $selected . $suffix;
                }
            }
            if ($personal !== '') {
                $options .= '<optgroup label="Personal reports">';
                $options .= $personal;
                $options .= '</optgroup>';
            }
            if ($public !== '') {
                $options .= '<optgroup label="Public reports">';
                $options .= $public;
                $options .= '</optgroup>';
            }
            $options .= '</select>';
            $options .= '<noscript><input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></noscript>';
        } else {
            $options = $this->purifier->purify($report->name, CODENDI_PURIFIER_CONVERT_HTML);
        }
        return $options;
    }

    private function getMissingPublicReportWarning(array $reports, $is_admin)
    {
        $warnings = array();

        if (! $is_admin) {
            return $warnings;
        }

        $public_reports_exist = false;
        foreach ($reports as $report) {
            \assert($report instanceof Tracker_Report);
            if ($report->isPublic()) {
                 $public_reports_exist = true;
            }
        }

        if (! $public_reports_exist) {
            $warnings[] = $GLOBALS['Language']->getText('plugin_tracker_report', 'no_public_reports');
        }

        return $warnings;
    }
}
