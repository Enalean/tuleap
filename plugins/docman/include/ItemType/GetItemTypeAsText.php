<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ItemType;

use Tuleap\Event\Dispatchable;

final class GetItemTypeAsText implements Dispatchable
{
    /**
     * @var array<string, string>
     */
    private array $other_types_label = [];

    public function getLabel(int $item_type, string $other_type): string
    {
        return match ($item_type) {
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER => dgettext('tuleap-docman', 'Folder'),
            PLUGIN_DOCMAN_ITEM_TYPE_FILE => dgettext('tuleap-docman', 'File'),
            PLUGIN_DOCMAN_ITEM_TYPE_LINK => dgettext('tuleap-docman', 'Link'),
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE => dgettext('tuleap-docman', 'Embedded file'),
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI => dgettext('tuleap-docman', 'Wiki'),
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY => dgettext('tuleap-docman', 'Empty document'),
            default => $this->other_types_label[$other_type] ?? dgettext('tuleap-docman', 'Unknown item type'),
        };
    }

    public function addOtherTypeLabel(string $type, string $label): void
    {
        $this->other_types_label[$type] = $label;
    }
}
