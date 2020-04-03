<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

class Link
{
    /**
     * @var string
     */
    public $external_url;
    /**
     * @var string
     */
    public $button_title;
    /**
     * @var string
     */
    public $button_label;

    public function __construct(\Project $project, int $folder_id)
    {
        $this->external_url = $this->buildUrl($project, $folder_id);
        $this->button_title = dgettext('tuleap-docman', 'Switch to the new user interface');
        $this->button_label = dgettext('tuleap-docman', 'Switch to the new user interface');
    }

    private function buildUrl(\Project $project, int $folder_id): string
    {
        $url = "/plugins/document/" . urlencode($project->getUnixName()) . "/";
        if ($folder_id === 0) {
            return $url;
        }
        return $url . "folder/" . $folder_id . "/";
    }
}
