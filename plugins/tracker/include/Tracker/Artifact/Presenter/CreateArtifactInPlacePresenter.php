<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_Artifact_Presenter_CreateArtifactInPlacePresenter
{
    /** @var Tracker */
    private $tracker;

    /** @var Artifact | null */
    private $artifact_to_link;

    /** @var Tracker_FormElement[] */
    public $form_elements;

    /** @var bool */
    private $render_with_javascript;
    public readonly CSRFSynchronizerToken $csrf_token;

    public function __construct(Tracker $tracker, $artifact_to_link, $form_elements, $render_with_javascript)
    {
        $this->tracker                = $tracker;
        $this->artifact_to_link       = $artifact_to_link;
        $this->form_elements          = $form_elements;
        $this->render_with_javascript = $render_with_javascript;
        $this->csrf_token             = new CSRFSynchronizerToken($tracker->getUri());
    }

    public function tracker_title()
    {
        return $this->tracker->getName();
    }

    public function artifact_to_link_title()
    {
        if (! $this->has_linked_artifact()) {
            return null;
        }

        return sprintf(dgettext('tuleap-tracker', 'The artifact will be linked to %1$s'), (string) $this->artifact_to_link->getTitle());
    }

    public function has_linked_artifact()
    {
        return isset($this->artifact_to_link);
    }

    public function javascript_rules()
    {
        if ($this->render_with_javascript) {
            return $this->tracker->displayRulesAsJavascript();
        } else {
            return '';
        }
    }

    public function submit()
    {
        return dgettext('tuleap-tracker', 'Submit');
    }

    public function cancel()
    {
        return dgettext('tuleap-tracker', 'Cancel');
    }
}
