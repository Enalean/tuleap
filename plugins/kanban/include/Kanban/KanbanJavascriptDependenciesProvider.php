<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeAssetsGeneric;

final readonly class KanbanJavascriptDependenciesProvider
{
    public function __construct(private IncludeAssets $kanban_include_assets, private IncludeAssetsGeneric $assets_ckeditor4)
    {
    }

    public function getDependencies(): array
    {
        return [
            ['file' => $this->assets_ckeditor4->getFileURL('ckeditor.js')],
            ['file' => $this->kanban_include_assets->getFileURL('kanban.js')],
        ];
    }
}
