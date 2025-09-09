<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\CrossTracker\Query;

use ForgeConfig;
use PFUser;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\CrossTracker\CrossTrackerInstrumentation;
use Tuleap\CrossTracker\Query\Advanced\ExpertQueryIsEmptyException;
use Tuleap\CrossTracker\Query\Advanced\InvalidOrderByBuilder;
use Tuleap\CrossTracker\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\CrossTracker\Query\Advanced\InvalidSelectablesCollectionBuilder;
use Tuleap\CrossTracker\Query\Advanced\InvalidSelectablesCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\CrossTrackerTQLQueryDao;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilderVisitor;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilderVisitor;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryContentRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\Permission\ArtifactPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LinkArtifactCondition;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithReverseLink;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\OrderByIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Tracker;

#[ConfigKeyCategory('CrossTracker Search')]
final readonly class CrossTrackerArtifactQueryFactory
{
    #[ConfigKey('Configure the maximum quantity of tracker a cross tracker search expert query can use (default to 0 for no limit)')]
    #[ConfigKeyInt(0)]
    public const MAX_TRACKER_FROM = 'crosstracker_maximum_tracker_get_from';

    #[ConfigKey('Configure the maximum quantity of column a cross tracker search expert query can select (default to 0 for no limit)')]
    #[ConfigKeyInt(0)]
    public const MAX_SELECT = 'crosstracker_maximum_selected_columns';

    public function __construct(
        private ExpertQueryValidator $expert_query_validator,
        private QueryBuilderVisitor $query_builder,
        private SelectBuilderVisitor $select_builder,
        private OrderByBuilderVisitor $order_builder,
        private ResultBuilderVisitor $result_builder,
        private ParserCacheProxy $parser,
        private CrossTrackerTQLQueryDao $expert_query_dao,
        private InvalidTermCollectorVisitor $term_collector,
        private InvalidSelectablesCollectorVisitor $selectables_collector,
        private DuckTypedFieldChecker $field_checker,
        private MetadataChecker $metadata_checker,
        private RetrieveQueryTrackers $query_trackers_retriever,
        private CrossTrackerInstrumentation $instrumentation,
        private RetrieveUserPermissionOnArtifacts $permission_on_artifacts_retriever,
        private RetrieveArtifact $artifact_retriever,
        private ForwardLinkFromWhereBuilder $forward_link_from_where_builder,
        private ReverseLinkFromWhereBuilder $reverse_link_from_where_builder,
        private InstantiateRetrievedQueryTrackers $trackers_instantiator,
        private RetrieveUserPermissionOnTrackers $trackers_permissions,
    ) {
    }

    /**
     * @throws ExpertQueryIsEmptyException
     * @throws FromIsInvalidException
     * @throws LimitSizeIsExceededException
     * @throws MissingFromException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    public function getArtifactsMatchingQuery(
        CrossTrackerQuery $query,
        PFUser $current_user,
        int $limit,
        int $offset,
        Option $direction,
    ): CrossTrackerQueryContentRepresentation {
        if ($query->getQuery() === '') {
            throw new ExpertQueryIsEmptyException();
        }

        $parsed_query = ParsedCrossTrackerQuery::fromCrossTrackerQuery($query, $this->parser);

        $trackers = $this->query_trackers_retriever->getQueryTrackers($parsed_query, $current_user, ForgeConfig::getInt(self::MAX_TRACKER_FROM));
        $this->instrumentation->updateTrackerCount(count($trackers));

        $this->validateExpertQuery($parsed_query, $current_user, $trackers);
        if ($parsed_query->parsed_query->getOrderBy() !== null) {
            $this->instrumentation->updateOrderByUsage();
        }

        $additional_from_where = $this->query_builder->buildFromWhere($parsed_query->parsed_query->getCondition(), $trackers, $current_user);

        return $this->retrieveQueryContentRepresentation(
            $parsed_query,
            $trackers,
            $current_user,
            $additional_from_where,
            $limit,
            $offset,
            Option::nothing(\Psl\Type\int()),
            $direction,
        );
    }

    /**
     * @throws ExpertQueryIsEmptyException
     * @throws FromIsInvalidException
     * @throws LimitSizeIsExceededException
     * @throws MissingFromException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    public function getForwardLinks(
        CrossTrackerQuery $query,
        int $source_artifact_id,
        PFUser $current_user,
        int $limit,
        int $offset,
    ): CrossTrackerQueryContentRepresentation {
        if ($query->getQuery() === '') {
            throw new ExpertQueryIsEmptyException();
        }

        return $this->getArtifactLinksMatchingExpertQuery(
            ParsedCrossTrackerQuery::fromCrossTrackerQuery($query, $this->parser),
            $current_user,
            $this->expert_query_dao->getTrackersIdsForReverseLink($source_artifact_id),
            $this->reverse_link_from_where_builder->getFromWhereForWithReverseLink(
                new WithReverseLink(new LinkArtifactCondition($source_artifact_id), null),
                $current_user,
            ),
            $limit,
            $offset,
            Option::fromValue($source_artifact_id),
            Option::fromValue(TypePresenter::FORWARD_LABEL),
        );
    }

    /**
     * @throws ExpertQueryIsEmptyException
     * @throws FromIsInvalidException
     * @throws LimitSizeIsExceededException
     * @throws MissingFromException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    public function getReverseLinks(
        CrossTrackerQuery $query,
        int $target_artifact_id,
        PFUser $current_user,
        int $limit,
        int $offset,
    ): CrossTrackerQueryContentRepresentation {
        if ($query->getQuery() === '') {
            throw new ExpertQueryIsEmptyException();
        }

        return $this->getArtifactLinksMatchingExpertQuery(
            ParsedCrossTrackerQuery::fromCrossTrackerQuery($query, $this->parser),
            $current_user,
            $this->expert_query_dao->getTrackersIdsForForwardLink($target_artifact_id),
            $this->forward_link_from_where_builder->getFromWhereForWithForwardLink(
                new WithForwardLink(new LinkArtifactCondition($target_artifact_id), null),
                $current_user,
            ),
            $limit,
            $offset,
            Option::fromValue($target_artifact_id),
            Option::fromValue(TypePresenter::REVERSE_LABEL),
        );
    }

    /**
     * @param Option<int> $artifact_id_for_links
     * @throws FromIsInvalidException
     * @throws LimitSizeIsExceededException
     * @throws MissingFromException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    private function getArtifactLinksMatchingExpertQuery(
        ParsedCrossTrackerQuery $query,
        PFUser $current_user,
        array $trackers_ids,
        IProvideParametrizedFromAndWhereSQLFragments $additional_from_where,
        int $limit,
        int $offset,
        Option $artifact_id_for_links,
        Option $direction,
    ): CrossTrackerQueryContentRepresentation {
        $trackers = $this->trackers_permissions->retrieveUserPermissionOnTrackers(
            $current_user,
            $this->trackers_instantiator->getTrackers($trackers_ids),
            TrackerPermissionType::PERMISSION_VIEW,
        )->allowed;
        if (count($trackers) === 0) {
            return $this->buildQueryContentRepresentation(
                $this->result_builder->buildResult(
                    [new Metadata('artifact'), ...$query->parsed_query->getSelect()],
                    [],
                    $current_user,
                    [],
                    $direction,
                ),
                0
            );
        }
        $this->instrumentation->updateTrackerCount(count($trackers));

        $this->validateExpertQuery($query, $current_user, $trackers);
        if ($query->parsed_query->getOrderBy() !== null) {
            $this->instrumentation->updateOrderByUsage();
        }

        return $this->retrieveQueryContentRepresentation($query, $trackers, $current_user, $additional_from_where, $limit, $offset, $artifact_id_for_links, $direction);
    }

    /**
     * @param Tracker[] $trackers
     * @param Option<int> $target_artifact_id_for_reverse_links
     */
    private function retrieveQueryContentRepresentation(
        ParsedCrossTrackerQuery $query,
        array $trackers,
        PFUser $current_user,
        IProvideParametrizedFromAndWhereSQLFragments $additional_from_where,
        int $limit,
        int $offset,
        Option $target_artifact_id_for_reverse_links,
        Option $direction,
    ): CrossTrackerQueryContentRepresentation {
        $additional_from_order = $this->order_builder->buildFromOrder($query->parsed_query->getOrderBy(), $trackers, $current_user);
        $tracker_ids           = $this->getTrackersId($trackers);
        $artifact_ids          = $this->expert_query_dao->searchArtifactsIdsMatchingQuery(
            $additional_from_where,
            $additional_from_order,
            $tracker_ids,
        );
        $total_size            = count($artifact_ids);
        $artifact_ids          = array_map(
            static fn(Artifact $artifact) => $artifact->getId(),
            $this->permission_on_artifacts_retriever->retrieveUserPermissionOnArtifacts(
                $current_user,
                $this->getArtifacts(array_slice($artifact_ids, $offset, $limit)),
                ArtifactPermissionType::PERMISSION_VIEW,
            )->allowed,
        );

        if ($artifact_ids === []) {
            return new CrossTrackerQueryContentRepresentation([], [], 0);
        }

        $this->instrumentation->updateSelectCount(count($query->parsed_query->getSelect()));
        $select_from_fragments = $this->select_builder->buildSelectFrom($query->parsed_query->getSelect(), $trackers, $current_user, $target_artifact_id_for_reverse_links, $artifact_ids);
        $select_results        = $this->expert_query_dao->searchArtifactsColumnsMatchingIds(
            $select_from_fragments,
            $additional_from_order,
            array_values($artifact_ids),
        );

        $results = $this->result_builder->buildResult([new Metadata('artifact'), ...$query->parsed_query->getSelect()], $trackers, $current_user, $select_results, $direction);

        return $this->buildQueryContentRepresentation($results, $total_size);
    }

    /**
     * @param Tracker[] $trackers
     * @throws LimitSizeIsExceededException
     * @throws OrderByIsInvalidException
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     * @throws SelectLimitExceededException
     * @throws SelectablesAreInvalidException
     * @throws SelectablesDoNotExistException
     * @throws SelectablesMustBeUniqueException
     * @throws SyntaxError
     */
    private function validateExpertQuery(
        CrossTrackerQuery $query,
        PFUser $current_user,
        array $trackers,
    ): void {
        $expert_query = $query->getQuery();
        $this->expert_query_validator->validateExpertQuery(
            $expert_query,
            new InvalidSearchablesCollectionBuilder($this->term_collector, $trackers, $current_user),
            new InvalidSelectablesCollectionBuilder($this->selectables_collector, $trackers, $current_user),
            new InvalidOrderByBuilder($this->field_checker, $this->metadata_checker, $trackers, $current_user),
        );
    }

    /**
     * @param Tracker[] $trackers
     * @return int[]
     */
    private function getTrackersId(array $trackers): array
    {
        $id = [];

        foreach ($trackers as $tracker) {
            $id[] = $tracker->getId();
        }

        return $id;
    }

    /**
     * @param list<array{id: int}> $artifact_ids
     * @return list<Artifact>
     */
    private function getArtifacts(array $artifact_ids): array
    {
        $artifacts = [];
        foreach ($artifact_ids as $row) {
            $artifact = $this->artifact_retriever->getArtifactById($row['id']);
            if ($artifact !== null) {
                $artifacts[] = $artifact;
            }
        }
        return $artifacts;
    }

    /**
     * @param SelectedValuesCollection[] $results
     */
    private function buildQueryContentRepresentation(array $results, int $total_size): CrossTrackerQueryContentRepresentation
    {
        /** @var CrossTrackerSelectedRepresentation[] $selected */
        $selected = [];
        /** @var array<int, array<string, SelectedValueRepresentation>> $artifacts */
        $artifacts = [];

        foreach ($results as $result) {
            if ($result->selected === null) {
                continue;
            }
            $selected[] = $result->selected;
            foreach ($result->values as $id => $value) {
                if (! isset($artifacts[$id])) {
                    $artifacts[$id] = [];
                }
                $artifacts[$id][$value->name] = $value->value;
            }
        }

        return new CrossTrackerQueryContentRepresentation(array_values($artifacts), $selected, $total_size);
    }
}
