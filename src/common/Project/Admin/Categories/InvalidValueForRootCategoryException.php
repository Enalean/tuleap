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

namespace Tuleap\Project\Admin\Categories;

final class InvalidValueForRootCategoryException extends ProjectCategoriesException
{
    private int $submitted_category_id;
    private string $tree_full_name;
    private int $tree_id;

    public function __construct(int $submitted_category_id, string $tree_full_name, int $tree_id)
    {
        $this->submitted_category_id = $submitted_category_id;
        $this->tree_full_name        = $tree_full_name;
        $this->tree_id               = $tree_id;

        parent::__construct(
            sprintf(
                '%1$d does not belong to %2$s (%3$d) category hierarchy',
                $submitted_category_id,
                $tree_full_name,
                $tree_id
            )
        );
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(
            dgettext('tuleap-core', '%1$d does not belong to %2$s (%3$d) category hierarchy'),
            $this->submitted_category_id,
            $this->tree_full_name,
            $this->tree_id,
        );
    }
}
