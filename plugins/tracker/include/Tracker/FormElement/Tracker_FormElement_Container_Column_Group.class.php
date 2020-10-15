<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2011. All rights reserved
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

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_FormElement_Container_Column_Group
{

    public function fetchArtifact($columns, Artifact $artifact, array $submitted_values)
    {
        return $this->fetchGroup($columns, 'fetchArtifactInGroup', [$artifact, $submitted_values]);
    }

    public function fetchArtifactForOverlay($columns, Artifact $artifact)
    {
        return $this->fetchGroupNoColumns($columns, 'fetchArtifactInGroup', [$artifact]);
    }

    public function fetchArtifactReadOnly($columns, Artifact $artifact, array $submitted_values)
    {
        return $this->fetchGroup($columns, 'fetchArtifactReadOnlyInGroup', [$artifact, $submitted_values]);
    }

    public function fetchArtifactCopyMode($columns, Artifact $artifact, array $submitted_values)
    {
        return $this->fetchGroup($columns, 'fetchArtifactCopyModeInGroup', [$artifact, $submitted_values]);
    }

    public function fetchSubmit($columns, array $submitted_values)
    {
        return $this->fetchGroup($columns, 'fetchSubmitInGroup', [$submitted_values]);
    }

    public function fetchSubmitForOverlay($columns, array $submitted_values)
    {
        return $this->fetchGroupNoColumns($columns, 'fetchSubmitInGroup', [$submitted_values]);
    }

    public function fetchSubmitMasschange($columns)
    {
        return $this->fetchGroup($columns, 'fetchSubmitMasschangeInGroup', []);
    }

    public function fetchAdmin($columns, $tracker)
    {
        return $this->fetchGroup($columns, 'fetchAdminInGroup', [$tracker]);
    }

    public function fetchMailArtifact($columns, $recipient, Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return $this->fetchMailGroup($columns, 'fetchMailArtifactInGroup', [$recipient, $artifact, $format, $ignore_perms], $format);
    }

    protected function fetchGroup($columns, $method, $params)
    {
        $output = '';
        if (is_array($columns) && $columns) {
            $cells = [];
            foreach ($columns as $c) {
                if ($content = call_user_func_array([$c, $method], $params)) {
                    $cells[] = '<td>' . $content . '</td>';
                }
            }

            if ($cells) {
                $output .= '<table width="100%"><tbody><tr valign="top">';
                $output .= implode('', $cells);
                $output .= '</tr></tbody></table>';
            }
        }
        return $output;
    }

    protected function fetchMailGroup($columns, $method, $params, $format = 'html')
    {
        $output = '';
        if (is_array($columns) && $columns) {
            foreach ($columns as $c) {
                if ($content = call_user_func_array([$c, $method], $params)) {
                    if ($format == 'html') {
                        $output .= $content;
                    } else {
                        $output .= $content . PHP_EOL;
                    }
                }
            }
        }
        return $output;
    }

    private function fetchGroupNoColumns($columns, $method, $params)
    {
        if (is_array($columns) && $columns) {
            $rows = [];
            foreach ($columns as $column) {
                $content = call_user_func_array([$column, $method], $params);
                if ($content) {
                    $rows[] = $content;
                }
            }

            if ($rows) {
                return implode(PHP_EOL, $rows);
            }
        }
        return '';
    }
}
