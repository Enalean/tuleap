<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

abstract class Tracker_Artifact_SubmitAbstractRenderer extends Tracker_Artifact_ArtifactRenderer
{

    public function __construct(Tracker $tracker, EventManager $event_manager)
    {
        parent::__construct($tracker, $event_manager);

        $this->redirect->query_parameters = array(
            'tracker'  => $this->tracker->getId(),
            'func'     => 'submit-artifact',
        );
    }

    protected function fetchSubmitInstructions()
    {
        if ($this->tracker->submit_instructions) {
            $hp = Codendi_HTMLPurifier::instance();
            return '<p class="submit_instructions">' . $hp->purify($this->tracker->submit_instructions, CODENDI_PURIFIER_FULL) . '</p>';
        }
    }

    protected function fetchFormElements(Codendi_Request $request)
    {
        $html = '';
        $html .= '<div class="tracker_artifact">';
        foreach ($this->tracker->getFormElements() as $form_element) {
            $submitted_values = $request->get('artifact');
            if (! $submitted_values || ! is_array($submitted_values)) {
                $submitted_values = [];
            }
            $html .= $form_element->fetchSubmit($submitted_values);
        }
        $html .= '</div>';

        return $html;
    }
}
