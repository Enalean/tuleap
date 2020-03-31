<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Version;

use Docman_Empty;
use Docman_Link;

class LinkVersionDataUpdator
{
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;

    public function __construct(\Docman_ItemFactory $item_factory)
    {
        $this->item_factory = $item_factory;
    }

    public function updateLinkFromEmptyVersionData(Docman_Empty $empty, array $version_data): Docman_Link
    {
        $this->item_factory->update(
            [
                'id'        => $empty->getId(),
                'group_id'  => $empty->getGroupId(),
                'title'     => $empty->getTitle(),
                'user_id'   => $empty->getOwnerId(),
                'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'link_url'  => $version_data['link_url'],
            ]
        );

        $link = $this->item_factory->getItemFromDb($empty->getId());
        \assert($link instanceof Docman_Link);

        $this->item_factory->createNewLinkVersion($link, $version_data);

        return $link;
    }
}
