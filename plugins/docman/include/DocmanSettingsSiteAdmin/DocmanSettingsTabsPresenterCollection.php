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

namespace Tuleap\Docman\DocmanSettingsSiteAdmin;

use Tuleap\Event\Dispatchable;

class DocmanSettingsTabsPresenterCollection implements Dispatchable
{
    public const NAME = 'docmanSettingsTabsPresenterCollection';

    /**
     * @var DocmanSettingsTabPresenter[]
     */
    public $tabs = [];

    public function __construct()
    {
        $this->tabs = [
            new FileUploadTabPresenter()
        ];
    }

    public function add(DocmanSettingsTabPresenter $tab): void
    {
        $this->tabs[] = $tab;
    }

    /**
     * @return DocmanSettingsTabPresenter[]
     */
    public function getTabs(string $current_url): array
    {
        return array_map(
            static function (DocmanSettingsTabPresenter $tab) use ($current_url): DocmanSettingsTabPresenter {
                $presenter = clone $tab;
                if ($tab->url === $current_url) {
                    $presenter->is_active = true;
                }

                return $presenter;
            },
            $this->tabs
        );
    }
}
