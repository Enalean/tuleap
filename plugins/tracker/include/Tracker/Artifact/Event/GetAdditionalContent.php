<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Event;

use Tracker_Artifact;
use Tuleap\Event\Dispatchable;

class GetAdditionalContent implements Dispatchable
{
    const NAME = 'tracker_view_get_additional_content';

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var string
     */
    private $content = '';

    public function __construct(Tracker_Artifact $artifact)
    {
        $this->artifact = $artifact;
    }

    public function getArtifact()
    {
        return $this->artifact;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }
}
