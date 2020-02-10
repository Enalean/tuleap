<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction;

use Tuleap\Event\Dispatchable;

class GetPostActionShortNameFromXmlTagNameEvent implements Dispatchable
{
    public const NAME = 'getPostActionShortNameFromXmlTagNameEvent';

    /**
     * @var string
     */
    private $xml_tag_name;

    /**
     * @var string
     */
    private $post_action_short_name = '';

    public function __construct(string $xml_tag_name)
    {
        $this->xml_tag_name = $xml_tag_name;
    }

    public function getXmlTagName(): string
    {
        return $this->xml_tag_name;
    }

    public function getPostActionShortName(): string
    {
        return $this->post_action_short_name;
    }

    public function setPostActionShortName(string $post_action_short_name): void
    {
        $this->post_action_short_name = $post_action_short_name;
    }
}
