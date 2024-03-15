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

use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Document\Config\Project\IRetrieveCriteria;

final class ListOfSearchCriterionPresenterBuilder
{
    public function __construct(private IRetrieveCriteria $criteria_dao)
    {
    }

    public function getSelectedCriteria(\Docman_MetadataFactory $metadata_factory, ItemStatusMapper $status_mapper, \Project $project): array
    {
        $selectable_criteria = $this->getAllCriteria($metadata_factory, $status_mapper, $project);

        $selected_criteria_names = $this->criteria_dao->searchByProjectId((int) $project->getID());
        if (! $selected_criteria_names) {
            return $selectable_criteria;
        }

        return array_values(
            array_filter(
                $selectable_criteria,
                static fn(SearchCriterionPresenter|SearchCriterionListPresenter $criterion) => in_array(
                    $criterion->name,
                    $selected_criteria_names,
                    true
                ),
            ),
        );
    }

    public function getAllCriteria(\Docman_MetadataFactory $metadata_factory, ItemStatusMapper $status_mapper, \Project $project): array
    {
        $numeric_type_to_human_readable_type = [
            PLUGIN_DOCMAN_METADATA_TYPE_TEXT   => 'text',
            PLUGIN_DOCMAN_METADATA_TYPE_STRING => 'string',
            PLUGIN_DOCMAN_METADATA_TYPE_DATE   => 'date',
            PLUGIN_DOCMAN_METADATA_TYPE_LIST   => 'list',
        ];

        $criteria = [
            'hardcoded' => [
                new SearchCriterionPresenter(
                    'id',
                    dgettext('tuleap-document', 'Id'),
                    'number',
                ),
                new SearchCriterionListPresenter(
                    'type',
                    dgettext('tuleap-document', 'Type'),
                    $this->getTypeOptions($project),
                ),
                new SearchCriterionPresenter(
                    'filename',
                    dgettext('tuleap-document', 'Filename'),
                    'text',
                ),
            ],
            'custom' => [],
        ];

        $all_metadata = $metadata_factory->getMetadataForGroup(true);
        $metadata_factory->appendAllListOfValues($all_metadata);
        foreach ($all_metadata as $metadata) {
            assert($metadata instanceof \Docman_Metadata);
            $shard = $metadata->isSpecial() ? 'hardcoded' : 'custom';

            if ($metadata instanceof \Docman_ListMetadata) {
                $criteria[$shard][] = $metadata->getLabel() === \Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL
                    ? $this->getStatusCriterion($metadata, $status_mapper)
                    : $this->getListCriterion($metadata);
                continue;
            }

            if ($metadata->getLabel() === \Docman_MetadataFactory::HARDCODED_METADATA_OWNER_LABEL) {
                $criteria[$shard][] = new SearchCriterionPresenter(
                    $metadata->getLabel(),
                    $metadata->getName(),
                    'owner',
                );
                continue;
            }

            $criteria[$shard][] = new SearchCriterionPresenter(
                $metadata->getLabel(),
                $metadata->getName(),
                $numeric_type_to_human_readable_type[$metadata->getType()],
            );
        }

        usort(
            $criteria['custom'],
            static fn (
                SearchCriterionPresenter|SearchCriterionListPresenter $a,
                SearchCriterionPresenter|SearchCriterionListPresenter $b,
            ): int => strnatcasecmp($a->label, $b->label)
        );

        return array_merge(
            $criteria['hardcoded'],
            $criteria['custom']
        );
    }

    private function getStatusCriterion(\Docman_ListMetadata $metadata, ItemStatusMapper $status_mapper): SearchCriterionListPresenter
    {
        $options = [];

        foreach ($metadata->getListOfValueIterator() as $value) {
            assert($value instanceof \Docman_MetadataListOfValuesElement);
            if (! in_array($value->getStatus(), ['A', 'P'], true)) {
                continue;
            }

            $status_id = (int) $value->getId();

            $options[] = new SearchCriterionListOptionPresenter(
                $status_mapper->getItemStatusFromItemStatusNumber($status_id),
                $status_id === 100 ?
                    \dgettext('tuleap-document', 'None') :
                    $value->getName()
            );
        }

        return new SearchCriterionListPresenter($metadata->getLabel(), $metadata->getName(), $options);
    }

    private function getListCriterion(\Docman_ListMetadata $metadata): SearchCriterionListPresenter
    {
        $options = [];

        foreach ($metadata->getListOfValueIterator() as $value) {
            assert($value instanceof \Docman_MetadataListOfValuesElement);
            if (! in_array($value->getStatus(), ['A', 'P'], true)) {
                continue;
            }

            $options[] = new SearchCriterionListOptionPresenter(
                (string) $value->getId(),
                (int) $value->getId() === 100 ?
                    \dgettext('tuleap-document', 'None') :
                    $value->getName()
            );
        }

        return new SearchCriterionListPresenter($metadata->getLabel(), $metadata->getName(), $options);
    }

    /**
     * @return SearchCriterionListOptionPresenter[]
     */
    private function getTypeOptions(\Project $project): array
    {
        $type_options = [
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
