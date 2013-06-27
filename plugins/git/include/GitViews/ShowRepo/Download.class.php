<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class GitViews_ShowRepo_Download {
    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    public function __construct(GitViews_GitPhpViewer $gitphp_viewer) {
        $this->gitphp_viewer = $gitphp_viewer;
    }

    public function display() {
        echo $this->gitphp_viewer->getContent();
        exit;
    }
}

?>
