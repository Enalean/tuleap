<?php
/*
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn;

use Tracker_ArtifactLinkInfo;
use Codendi_HTMLPurifier;

/**
 * I am responsible of rendering an artifact link info to be displayed in the browser
 */
class HTMLOutputStrategy implements OutputStrategy
{
    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(Codendi_HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    /** @return string */
    public function fetchDefault(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $artifact_link_info->getLink();
    }

    /** @return string */
    public function fetchFormatted(Tracker_ArtifactLinkInfo $artifact_link_info, $formatted_value)
    {
        $artlink_as_html  = '<a href="' . $artifact_link_info->getUrl() . '">';
        $artlink_as_html .= $this->purifier->purify($formatted_value);
        $artlink_as_html .= '</a>';

        return $artlink_as_html;
    }

    /** @return string */
    public function fetchWhenNoFieldToFormat(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $this->getDefaultFormatWithWarning(
            $artifact_link_info,
            $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'cannot_format')
        );
    }

    /** @return string */
    public function fetchWhenUnsupportedField(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $this->getDefaultFormatWithWarning(
            $artifact_link_info,
            $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'unsupported_field')
        );
    }

    private function getDefaultFormatWithWarning(Tracker_ArtifactLinkInfo $artifact_link_info, $warning)
    {
        $title = $this->purifier->purify($warning);

        return $artifact_link_info->getLink() .
            ' <i class="fa fa-exclamation-triangle format-warning" title="' . $title . '"></i>';
    }
}
