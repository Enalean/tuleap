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

namespace Tuleap\Document\Tree;

final class ListOfSearchCriterionPresenterBuilder
{
    public function getCriteria(\Docman_MetadataFactory $metadata_factory, \Project $project): array
    {
        $numeric_type_to_human_readable_type = [
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT   => 'text',
            PLUGIN_DOCMAN_METADATA_TYPE_STRING => 'string',
            PLUGIN_DOCMAN_METADATA_TYPE_DATE   => 'date',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST   => 'list',
        ];

        $criteria = [
            new SearchCriterionListPresenter(
                'type',
                dgettext('tuleap-document', 'Type'),
                $this->getTypeOptions($project),
            ),
        ];

        foreach ($metadata_factory->getMetadataForGroup(true) as $metadata) {
            assert($metadata instanceof \Docman_Metadata);
            if (! $metadata->isSpecial()) {
                continue;
            }

            if (! in_array($metadata->getLabel(), \Docman_MetadataFactory::HARDCODED_METADATA_LABELS, true)) {
                continue;
            }

            if ($metadata->getLabel() === \Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL) {
                continue;
            }

            $criteria[] = new SearchCriterionPresenter(
                $metadata->getLabel(),
                $metadata->getName(),
                $numeric_type_to_human_readable_type[$metadata->getType()],
            );
        }

        return $criteria;
    }

    /**
     * @return SearchCriterionListOptionPresenter[]
     */
    private function getTypeOptions(\Project $project): array
    {
        $type_options = [
            new SearchCriterionListOptionPresenter("", dgettext("tuleap-document", "Any")),
            new SearchCriterionListOptionPresenter("folder", dgettext("tuleap-document", "Folder")),
            new SearchCriterionListOptionPresenter("file", dgettext("tuleap-document", "File")),
            new SearchCriterionListOptionPresenter("embedded", dgettext("tuleap-document", "Embedded file")),
        ];

        if ($project->usesWiki()) {
            $type_options[] = new SearchCriterionListOptionPresenter("wiki", "Wiki page");
        }

        $type_options[] = new SearchCriterionListOptionPresenter("empty", "Empty document");

        return $type_options;
    }
}
