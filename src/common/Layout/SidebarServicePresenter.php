<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Layout;

use Service;
use Tuleap\Project\Service\ProjectDefinedService;

/**
 * @psalm-immutable
 */
final class SidebarServicePresenter
{
    /**
     * @param list<SidebarPromotedItemPresenter> $promoted_items
     */
    private function __construct(
        public string $href,
        public string $label,
        public string $description,
        public string $icon,
        public bool $open_in_new_tab,
        public bool $is_active,
        public string $shortcut_id,
        public array $promoted_items,
    ) {
    }

    public static function fromProjectDefinedService(ProjectDefinedService $service, string $href, bool $is_enabled, ?string $active_promoted_item_id, \PFUser $user): self
    {
        $description          = $service->getInternationalizedDescription();
        $is_opened_in_new_tab = $service->isOpenedInNewTab();
        if ($is_opened_in_new_tab) {
            $description = sprintf(_('%s (opens in a new tab)'), $description);
        }

        return new self(
            $href,
            $service->getInternationalizedName(),
            $description,
            $service->getIcon(),
            $is_opened_in_new_tab,
            $is_enabled && $service->isIFrame(),
            '',
            $service->getPromotedItemPresenters($user, $active_promoted_item_id),
        );
    }

    public static function fromService(Service $service, string $href, bool $is_enabled, ?string $active_promoted_item_id, \PFUser $user): self
    {
        return new self(
            $href,
            $service->getInternationalizedName(),
            $service->getInternationalizedDescription(),
            $service->getIcon(),
            $service->isOpenedInNewTab(),
            $is_enabled,
            $service->getShortName(),
            $service->getPromotedItemPresenters($user, $active_promoted_item_id),
        );
    }
}
