<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree\Search;

final class ListOfSearchColumnDefinitionPresenterBuilder
{
    /**
     * @return SearchColumnDefinitionPresenter[]
     */
    public function getColumns(): array
    {
        return [
            new SearchColumnDefinitionPresenter("id", dgettext('tuleap-document', 'Id')),
            new SearchColumnDefinitionPresenter("title", dgettext('tuleap-document', 'Title')),
            new SearchColumnDefinitionPresenter("description", dgettext('tuleap-document', 'Description')),
            new SearchColumnDefinitionPresenter("owner", dgettext('tuleap-document', 'Owner')),
            new SearchColumnDefinitionPresenter("update_date", dgettext('tuleap-document', 'Update date')),
            new SearchColumnDefinitionPresenter("create_date", dgettext('tuleap-document', 'Create date')),
            new SearchColumnDefinitionPresenter("location", dgettext('tuleap-document', 'Location')),
        ];
    }
}
