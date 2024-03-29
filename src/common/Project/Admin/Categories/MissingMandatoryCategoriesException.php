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

final class MissingMandatoryCategoriesException extends ProjectCategoriesException
{
    private string $category_list;

    public function __construct(string $category_list)
    {
        $this->category_list = $category_list;

        parent::__construct(
            sprintf(
                'Mandatory categories where missing: %s',
                $category_list
            )
        );
    }

    public function getI18NMessage(): string
    {
        return sprintf(
            dgettext('tuleap-core', 'Mandatory categories where missing: %s'),
            $this->category_list
        );
    }
}
