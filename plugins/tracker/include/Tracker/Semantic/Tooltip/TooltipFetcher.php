<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Tooltip;

use Tuleap\Tracker\Artifact\Artifact;

final class TooltipFetcher
{
    public function fetchArtifactTooltip(Artifact $artifact, TooltipFields $tooltip, \PFUser $user): string
    {
        $readable_fields = $this->getReadableFields($artifact, $tooltip, $user);
        if (empty($readable_fields)) {
            return '';
        }

        $html = '<table>';
        foreach ($readable_fields as $field) {
            $html .= $field->fetchTooltip($artifact);
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    private function getReadableFields(Artifact $artifact, TooltipFields $tooltip, \PFUser $user): array
    {
        if (! $artifact->userCanView($user)) {
            return [];
        }

        $readable_fields = [];
        foreach ($tooltip->getFields() as $field) {
            if ($field->userCanRead($user)) {
                $readable_fields[] = $field;
            }
        }

        return $readable_fields;
    }
}
