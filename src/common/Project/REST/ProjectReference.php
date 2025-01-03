<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Project;
use Tuleap\Project\Icons\EmojiCodepointConverter;

/**
 * @psalm-immutable
 */
class ProjectReference
{
    /**
     * @var int ID of the project
     */
    public $id;

    /**
     * @var string URI of the project
     */
    public $uri;

    /**
     * @var string The public name of the project
     */
    public $label = null;

    /**
     * The icon's project
     */
    public string $icon = '';

    public function __construct($project)
    {
        if ($project instanceof Project) {
            $this->id    = (int) $project->getId();
            $this->label = (string) $project->getPublicName();
            $this->icon  = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());
        } else {
            $this->id = (int) $project;
        }

        $this->uri = ProjectRepresentation::ROUTE . '/' . $this->id;
    }
}
