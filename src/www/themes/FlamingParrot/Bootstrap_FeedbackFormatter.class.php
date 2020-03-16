<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Bootstrap_FeedbackFormatter extends FeedbackFormatter
{

    /**
     * @return string html
     */
    public function format(array $logs)
    {
        $hp        = Codendi_HTMLPurifier::instance();
        $html      = '';
        $old_level = null;

        $html .= '<div class="container-fluid">';
        foreach ($logs as $log) {
            if (!is_null($old_level) && $old_level != $log['level']) {
                $html .= '</div>';
            }
            if (is_null($old_level) || $old_level != $log['level']) {
                $old_level = $log['level'];
                switch ($log['level']) {
                    case 'info':
                        $additional_classname = 'alert-info';
                        $title                = 'Heads up!';
                        break;
                    case 'error':
                        $additional_classname = 'alert-error';
                        $title                = 'Oh snap!';
                        break;
                    case 'success':
                        $additional_classname = 'alert-success';
                        $title                = 'Well done!';
                        break;
                    default:
                        $additional_classname = '';
                        $title                = 'Warning!';
                }
                $html .= '<div class="alert fade in alert-block ' . $additional_classname . '">';
                $html .= '<h4 class="alert-heading">' . $title . '</h4>';
            }
            $html .= $hp->purify($log['msg'], $log['purify']) . '<br />';
        }
        if (!is_null($old_level)) {
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
