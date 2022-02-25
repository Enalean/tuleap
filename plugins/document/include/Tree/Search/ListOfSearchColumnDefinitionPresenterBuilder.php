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
    public function getColumns(\Docman_MetadataFactory $metadata_factory): array
    {
        $columns = [
            new SearchColumnDefinitionPresenter("id", dgettext('tuleap-document', 'Id')),
            new SearchColumnDefinitionPresenter("title", dgettext('tuleap-document', 'Title')),
        ];

        $all_metadata = $metadata_factory->getMetadataForGroup(true);
        foreach ($all_metadata as $metadata) {
            assert($metadata instanceof \Docman_Metadata);
            if (! $metadata->isSpecial()) {
                continue;
            }

            if ($metadata->getLabel() === \Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL) {
                continue;
            }

            $columns[] = new SearchColumnDefinitionPresenter($metadata->getLabel(), $metadata->getName());
        }

        $columns[] = new SearchColumnDefinitionPresenter("location", dgettext('tuleap-document', 'Location'));

        return $columns;
    }
}
