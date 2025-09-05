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

final class NotRootCategoryException extends ProjectCategoriesException
{
    private int $submitted_category_id;

    public function __construct(int $submitted_category_id)
    {
        $this->submitted_category_id = $submitted_category_id;

        parent::__construct(
            sprintf(
                'The category id %d is not a valid root category',
                $submitted_category_id
            )
        );
    }

    #[\Override]
    public function getI18NMessage(): string
    {
        return sprintf(
            dgettext('tuleap-core', 'The category id %d is not a valid root category'),
            $this->submitted_category_id
        );
    }
}
