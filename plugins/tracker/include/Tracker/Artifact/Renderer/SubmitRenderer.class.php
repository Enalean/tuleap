<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
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

use Tuleap\Tracker\Artifact\Renderer\ListFieldsIncluder;

class Tracker_Artifact_SubmitRenderer extends Tracker_Artifact_SubmitAbstractRenderer
{
    /**
     * @var Tracker_IDisplayTrackerLayout
     */
    private $layout;

    public function __construct(Tracker $tracker, EventManager $event_manager, Tracker_IDisplayTrackerLayout $layout)
    {
        parent::__construct($tracker, $event_manager);
        $this->layout = $layout;
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user)
    {
        return $this->fetchArtifactForm(
            $this->fetchNewArtifactForm($request)
        );
    }

    protected function displayHeader()
    {
        $breadcrumbs = [
            [
                'title' => sprintf(dgettext('tuleap-tracker', 'New %s'), $this->tracker->getItemName()),
                'url'   => $this->tracker->getSubmitUrl(),
            ],
        ];

        $this->tracker->displayHeader(
            $this->layout,
            $this->tracker->name,
            $breadcrumbs,
            [],
            ['body_class' => ['widgetable']]
        );
        ListFieldsIncluder::includeListFieldsAssets();

        echo $this->fetchSubmitInstructions();
    }

    private function fetchNewArtifactForm(Codendi_Request $request)
    {
        $html = '';

        $html .= $this->fetchFormElements($request);

        $html .= '<div class="artifact-submit-button">';
        $html .= '<input type="hidden" id="submit-type" />';
        $html .= '<div class="btn-group dropup">';
        $html .= '<button class="btn btn-large btn-primary" data-test="artifact-submit-button" type="submit">'
            . $GLOBALS['Language']->getText('global', 'btn_submit') . '</button>';
        $html .= '<button class="btn btn-large btn-primary dropdown-toggle" data-toggle="dropdown" data-test="artifact-submit-options"><span class="caret"></span></button>';
        $html .= '<ul class="dropdown-menu">';
        $html .= '<li><input type="submit" name="submit_and_continue" class="btn btn-link" value="' . $GLOBALS['Language']->getText('global', 'btn_submit_and_continue') . '" /></li>';
        $html .= '<li><input type="submit" name="submit_and_stay" class="btn btn-link" value="' . $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') . '" data-test="artifact-submit-and-stay" /></li>';
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function displayFooter()
    {
        $include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../../../scripts/artifact/frontend-assets',
            '/assets/trackers/artifact'
        );
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('create-view.js'));
        $this->tracker->displayFooter($this->layout);
    }
}
