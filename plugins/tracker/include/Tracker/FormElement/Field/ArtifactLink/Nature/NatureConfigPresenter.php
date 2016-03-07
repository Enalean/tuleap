<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tracker\FormElement\Field\ArtifactLink\Nature;

use Tracker_FormElement_Field_ArtifactLink;

class NatureConfigPresenter {

    public function __construct() {
        $this->desc          = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'desc');
        $this->title         = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'title');
        $this->shortname     = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'shortname');
        $this->forward_label = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'forward_label');
        $this->reverse_label = $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'reverse_label');
        $this->natures = array(
            new NaturePresenter(
                Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', '_is_child_forward'),
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', '_is_child_reverse')
            )
        );
    }
}