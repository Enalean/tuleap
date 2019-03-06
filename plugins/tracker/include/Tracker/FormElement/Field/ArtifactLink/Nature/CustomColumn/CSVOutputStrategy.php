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

/**
 * I am responsible of rendering an artifact link info to be displayed in a spreadsheet
 */
class CSVOutputStrategy implements OutputStrategy
{
    /** @return string */
    public function fetchDefault(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $artifact_link_info->getArtifactId();
    }

    /** @return string */
    public function fetchFormatted(Tracker_ArtifactLinkInfo $artifact_link_info, $formatted_value)
    {
        return $formatted_value;
    }

    /** @return string */
    public function fetchWhenNoFieldToFormat(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $this->fetchDefault($artifact_link_info);
    }

    /** @return string */
    public function fetchWhenUnsupportedField(Tracker_ArtifactLinkInfo $artifact_link_info)
    {
        return $this->fetchDefault($artifact_link_info);
    }
}
