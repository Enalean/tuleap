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

namespace Tuleap\Platform\Banner;

use PFUser;

class BannerRetriever
{
    /**
     * @var BannerDao
     */
    private $banner_dao;

    public function __construct(BannerDao $banner_dao)
    {
        $this->banner_dao = $banner_dao;
    }

    public function getBanner(): ?Banner
    {
        $row = $this->banner_dao->searchBanner();

        if (! $row) {
            return null;
        }

        return new Banner($row['message'], $row['importance']);
    }

    public function getBannerForDisplayPurpose(PFUser $user): ?BannerDisplay
    {
        $banner_with_visibility_row = $this->banner_dao->searchBannerWithVisibility((int) $user->getId());

        if ($banner_with_visibility_row === null) {
            return null;
        }

        if ($banner_with_visibility_row['preference_value'] === 'hidden') {
            return BannerDisplay::buildHiddenBanner(
                $banner_with_visibility_row['message'],
                $banner_with_visibility_row['importance']
            );
        }

        return BannerDisplay::buildVisibleBanner(
            $banner_with_visibility_row['message'],
            $banner_with_visibility_row['importance']
        );
    }
}
