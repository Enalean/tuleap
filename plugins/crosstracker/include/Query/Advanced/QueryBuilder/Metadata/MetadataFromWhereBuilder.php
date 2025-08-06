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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata;

use LogicException;
use PFUser;
use Tracker_FormElementFactory;
use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date\DateFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users\UsersFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo\AssignedToFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Description\DescriptionFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Status\StatusFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Semantic\Title\TitleFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Special\ForwardLinkTypeFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Tracker;

final readonly class MetadataFromWhereBuilder
{
    private const SUBMITTED_ON_ALIAS     = 'artifact.submitted_on';
    private const LAST_UPDATE_DATE_ALIAS = 'changeset.submitted_on';
    private const SUBMITTED_BY_ALIAS     = 'artifact.submitted_by';
    private const LAST_UPDATE_BY_ALIAS   = 'changeset.submitted_by';
    private const ARTIFACT_ID_ALIAS      = 'artifact.id';

    public function __construct(
        private TitleFromWhereBuilder $title_builder,
        private DescriptionFromWhereBuilder $description_builder,
        private StatusFromWhereBuilder $status_builder,
        private AssignedToFromWhereBuilder $assigned_to_builder,
        private DateFromWhereBuilder $date_builder,
        private UsersFromWhereBuilder $users_builder,
        private ArtifactIdFromWhereBuilder $artifact_id_builder,
        private ForwardLinkTypeFromWhereBuilder $forward_link_type_builder,
        private Tracker_FormElementFactory $form_element_factory,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(
        Metadata $metadata,
        Comparison $comparison,
        array $trackers,
        PFUser $user,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $parameters = new MetadataValueWrapperParameters($comparison, $trackers, $user, '');
        return match ($metadata->getName()) {
            // Semantics
            AllowedMetadata::TITLE            => $this->title_builder->getFromWhere($parameters),
            AllowedMetadata::DESCRIPTION      => $this->description_builder->getFromWhere($parameters),
            AllowedMetadata::STATUS           => $this->status_builder->getFromWhere($parameters),
            AllowedMetadata::ASSIGNED_TO      => $this->assigned_to_builder->getFromWhere($parameters),

            // Always there fields
            AllowedMetadata::SUBMITTED_ON     => $this->date_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE, $user),
                $user,
                self::SUBMITTED_ON_ALIAS
            )),
            AllowedMetadata::LAST_UPDATE_DATE => $this->date_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE, $user),
                $user,
                self::LAST_UPDATE_DATE_ALIAS
            )),
            AllowedMetadata::SUBMITTED_BY     => $this->users_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE, $user),
                $user,
                self::SUBMITTED_BY_ALIAS
            )),
            AllowedMetadata::LAST_UPDATE_BY   => $this->users_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_LAST_MODIFIED_BY, $user),
                $user,
                self::LAST_UPDATE_BY_ALIAS
            )),
            AllowedMetadata::ID               => $this->artifact_id_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE, $user),
                $user,
                self::ARTIFACT_ID_ALIAS,
            )),
            AllowedMetadata::LINK_TYPE => $this->forward_link_type_builder->getFromWhere(new MetadataValueWrapperParameters(
                $comparison,
                $this->filterTrackersOnReadableField($trackers, Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS, $user),
                $user,
                '',
            )),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @param Tracker[] $trackers
     * @return Tracker[]
     */
    private function filterTrackersOnReadableField(array $trackers, string $field_type, PFUser $user): array
    {
        $result = [];
        foreach ($trackers as $tracker) {
            $fields = $this->form_element_factory->getUsedFormElementsByType($tracker, $field_type);
            if ($fields === []) {
                $result[] = $tracker;
                continue;
            }
            foreach ($fields as $field) {
                if ($field->userCanRead($user)) {
                    $result[] = $tracker;
                    break;
                }
            }
        }

        return $result;
    }
}
