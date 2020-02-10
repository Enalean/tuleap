<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

class AdditionalButtonAction
{
    /**
     * @var AdditionalButtonLinkPresenter
     */
    private $link_presenter;

    /**
     * @var string
     */
    private $asset_link;

    public function __construct(AdditionalButtonLinkPresenter $link_presenter, string $asset_link)
    {
        $this->link_presenter = $link_presenter;
        $this->asset_link     = $asset_link;
    }

    public function getLinkPresenter(): AdditionalButtonLinkPresenter
    {
        return $this->link_presenter;
    }

    public function getAssetLink(): string
    {
        return $this->asset_link;
    }
}
