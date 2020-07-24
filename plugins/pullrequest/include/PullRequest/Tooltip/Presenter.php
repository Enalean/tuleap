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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\PullRequest\Tooltip;

use Tuleap\PullRequest\PullRequest;

class Presenter
{
    public $title_label;
    public $title;
    public $status_label;
    public $status;
    public $creation_date_label;
    public $creation_date;

    public function __construct($title, $status, $creation_date)
    {
        $this->title_label         = $GLOBALS['Language']->getText('plugin_pullrequest', 'tooltip_title_label');
        $this->title               = $title;
        $this->status_label        = $GLOBALS['Language']->getText('plugin_pullrequest', 'tooltip_status_label');
        $this->status              = $this->getTranslatedStatus($status);
        $this->creation_date_label = $GLOBALS['Language']->getText('plugin_pullrequest', 'tooltip_creation_date_label');
        $this->creation_date       = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $creation_date);
    }

    public function getTemplateName()
    {
        return 'tooltip';
    }

    private function getTranslatedStatus($status_acronym)
    {
        $status_name = [
            PullRequest::STATUS_MERGED    => $GLOBALS['Language']->getText('plugin_pullrequest', 'status_merged'),
            PullRequest::STATUS_ABANDONED => $GLOBALS['Language']->getText('plugin_pullrequest', 'status_abandoned'),
            PullRequest::STATUS_REVIEW    => $GLOBALS['Language']->getText('plugin_pullrequest', 'status_in_review'),
        ];

        return $status_name[$status_acronym];
    }
}
