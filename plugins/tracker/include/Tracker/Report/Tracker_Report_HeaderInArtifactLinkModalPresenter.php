<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Report_HeaderInArtifactLinkModalPresenter
{

    private $tracker_switcher;
    private $reports_selector;
    private $select_report_url;
    private $title;


    public function __construct($title, $tracker_switcher, $select_report_url, $reports_selector)
    {
        $this->title             = $title;
        $this->reports_selector  = $reports_selector;
        $this->select_report_url = $select_report_url;
        $this->tracker_switcher  = $tracker_switcher;
    }

    public function tracker_switcher()
    {
        return $this->tracker_switcher;
    }

    public function title()
    {
        return $this->title;
    }

    public function select_report_url()
    {
        return $this->select_report_url;
    }

    public function reports_selector()
    {
        return $this->reports_selector;
    }
}
