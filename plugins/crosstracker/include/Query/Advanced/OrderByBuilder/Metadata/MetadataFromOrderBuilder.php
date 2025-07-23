<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Metadata;

use LogicException;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\StaticList\StaticListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\Text\TextFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList\UserListFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UserList\UserOrderByBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\OrderByBuilderParameters;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Contributor\RetrieveContributorField;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Tracker;

final readonly class MetadataFromOrderBuilder
{
    public function __construct(
        private RetrieveSemanticTitleField $retrieve_title_field,
        private RetrieveSemanticDescriptionField $retrieve_description_field,
        private RetrieveSemanticStatusField $status_field_retriever,
        private RetrieveContributorField $contributor_field_retriever,
        private TextFromOrderBuilder $text_builder,
        private StaticListFromOrderBuilder $static_list_builder,
        private UserListFromOrderBuilder $user_list_builder,
        private UserOrderByBuilder $user_order_by_builder,
        private EasyDB $easy_db,
    ) {
    }

    public function getFromOrder(Metadata $metadata, OrderByBuilderParameters $parameters): ParametrizedFromOrder
    {
        $order      = $parameters->direction->value;
        $user_alias = $this->easy_db->escapeIdentifier('user_' . $order, false);

        return match ($metadata->getName()) {
            AllowedMetadata::TITLE            => $this->text_builder->getFromOrder($this->getTitleFieldIds($parameters->trackers), $parameters->direction),
            AllowedMetadata::DESCRIPTION      => $this->text_builder->getFromOrder($this->getDescriptionFieldIds($parameters->trackers), $parameters->direction),
            AllowedMetadata::STATUS           => $this->static_list_builder->getFromOrder($this->getStatusFieldIds($parameters->trackers), $parameters->direction),
            AllowedMetadata::ASSIGNED_TO      => $this->user_list_builder->getFromOrder($this->getAssignedToFieldIds($parameters->trackers), $parameters->direction),

            AllowedMetadata::SUBMITTED_BY     => new ParametrizedFromOrder("LEFT JOIN user AS $user_alias ON $user_alias.user_id = artifact.submitted_by", [], $this->user_order_by_builder->getOrderByForUsers($user_alias, $parameters->direction)),
            AllowedMetadata::LAST_UPDATE_BY   => new ParametrizedFromOrder("LEFT JOIN user AS $user_alias ON $user_alias.user_id = changeset.submitted_by", [], $this->user_order_by_builder->getOrderByForUsers($user_alias, $parameters->direction)),
            AllowedMetadata::SUBMITTED_ON     => new ParametrizedFromOrder('', [], 'artifact.submitted_on ' . $order),
            AllowedMetadata::LAST_UPDATE_DATE => new ParametrizedFromOrder('', [], 'changeset.submitted_on ' . $order),
            AllowedMetadata::ID               => new ParametrizedFromOrder('', [], 'artifact.id ' . $order),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getTitleFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $title_field = $this->retrieve_title_field->fromTracker($tracker);
            if ($title_field !== null) {
                $field_ids[] = $title_field->getId();
            }
        }

        return $field_ids;
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getDescriptionFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $description_field = $this->retrieve_description_field->fromTracker($tracker);
            if ($description_field !== null) {
                $field_ids[] = $description_field->getId();
            }
        }
        return $field_ids;
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getStatusFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $field = $this->status_field_retriever->fromTracker($tracker);
            if ($field !== null) {
                $field_ids[] = $field->getId();
            }
        }
        return $field_ids;
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getAssignedToFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $field = $this->contributor_field_retriever->getContributorField($tracker);
            if ($field !== null) {
                $field_ids[] = $field->getId();
            }
        }
        return $field_ids;
    }
}
