<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\CrossTracker\Query\Advanced\ExpertQueryIsEmptyException;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\ForwardLinkTypeSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\ReverseLinkTypeSelectFromBuilder;
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactoryBuilder;
use Tuleap\CrossTracker\Query\CrossTrackerQueryDao;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryContentRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerWidgetRepresentation;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\Option\Option;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Report\Query\Advanced\Errors\QueryErrorsTranslator;
use Tuleap\Tracker\Report\Query\Advanced\FromIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectException;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\MissingFromException;
use Tuleap\Tracker\Report\Query\Advanced\OrderByIsInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;
use Tuleap\User\ProvideCurrentUser;
use URLVerification;
use UserManager;

final class CrossTrackerWidgetResource extends AuthenticatedResource
{
    public const  ROUTE     = 'crosstracker_widget';
    public const  MAX_LIMIT = 50;

    private readonly ProvideCurrentUser $current_user_provider;
    private readonly CrossTrackerArtifactQueryFactoryBuilder $factory_builder;

    public function __construct()
    {
        $this->current_user_provider = UserManager::instance();
        $this->factory_builder       = new CrossTrackerArtifactQueryFactoryBuilder();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id ID of the widget {@from path}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get a CrossTracker widget
     *
     * It returns the queries belonging to the widget
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the widget {@from path}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getId(int $id): CrossTrackerWidgetRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }

            $factory      = new CrossTrackerQueryFactory($this->getQueryDao());
            $queries      = $factory->getByWidgetId($id);
            $current_user = $this->current_user_provider->getCurrentUser();
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $id);

            $representations = [];
            foreach ($queries as $query) {
                $representations[] = CrossTrackerQueryRepresentation::fromQuery($query);
            }

            return new CrossTrackerWidgetRepresentation($representations);
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $id));
        }
    }

    /**
     * @url OPTIONS {id}/forward_links
     *
     * @param string $id ID of the widget {@from path}
     */
    public function optionsForwardLinks(string $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get forward links
     *
     * Get the forward links of an artifact according to a given CrossTracker query
     *
     * @url GET {id}/forward_links
     * @access hybrid
     *
     * @param int $id ID of the widget {@from path}
     * @param string $tql_query TQL query {@from query}
     * @param int $source_artifact_id ID of the artifact {@from query}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getForwardLinks(int $id, string $tql_query, int $source_artifact_id, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        $current_user = $this->current_user_provider->getCurrentUser();
        try {
            if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }

            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $id);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn(): CrossTrackerQueryContentRepresentation => $this->factory_builder->getArtifactFactory(new ForwardLinkTypeSelectFromBuilder())->getForwardLinks(
                    CrossTrackerQueryFactory::fromTqlQueryAndWidgetId($tql_query, Option::fromValue($id)),
                    $source_artifact_id,
                    $current_user,
                    $limit,
                    $offset,
                )
            );

            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $id));
        } catch (SyntaxError $error) {
            throw new RestException(400, '', SyntaxErrorTranslator::fromSyntaxError($error));
        } catch (LimitSizeIsExceededException | InvalidSelectException | SelectablesMustBeUniqueException | SelectLimitExceededException | MissingFromException $exception) {
            throw new I18NRestException(400, QueryErrorsTranslator::translateException($exception));
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $exception) {
            throw new I18NRestException(400, $exception->getMessage());
        } catch (FromIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (OrderByIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (ExpertQueryIsEmptyException) {
            throw new I18NRestException(400, dgettext('tuleap-crosstracker', 'TQL query is required and cannot be empty'));
        }
    }

    /**
     * @url OPTIONS {id}/reverse_links
     *
     * @param string $id ID of the widget {@from path}
     */
    public function optionsReverseLinks(string $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get reverse links
     *
     * Get the reverse links of an artifact according to a given CrossTracker query
     *
     * @url GET {id}/reverse_links
     * @access hybrid
     *
     * @param int $id ID of the widget {@from path}
     * @param string $tql_query TQL query {@from query}
     * @param int $target_artifact_id ID of the artifact {@from query}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getReverseLinks(int $id, string $tql_query, int $target_artifact_id, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        $current_user = $this->current_user_provider->getCurrentUser();
        try {
            if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }

            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $id);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn(): CrossTrackerQueryContentRepresentation => $this->factory_builder->getArtifactFactory(new ReverseLinkTypeSelectFromBuilder())->getReverseLinks(
                    CrossTrackerQueryFactory::fromTqlQueryAndWidgetId($tql_query, Option::fromValue($id)),
                    $target_artifact_id,
                    $current_user,
                    $limit,
                    $offset,
                )
            );

            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $id));
        } catch (SyntaxError $error) {
            throw new RestException(400, '', SyntaxErrorTranslator::fromSyntaxError($error));
        } catch (LimitSizeIsExceededException | InvalidSelectException | SelectablesMustBeUniqueException | SelectLimitExceededException | MissingFromException $exception) {
            throw new I18NRestException(400, QueryErrorsTranslator::translateException($exception));
        } catch (SearchablesDoNotExistException | SelectablesDoNotExistException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (SearchablesAreInvalidException | SelectablesAreInvalidException $exception) {
            throw new I18NRestException(400, $exception->getMessage());
        } catch (FromIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (OrderByIsInvalidException $exception) {
            throw new I18NRestException(400, $exception->getI18NExceptionMessage());
        } catch (ExpertQueryIsEmptyException) {
            throw new I18NRestException(400, dgettext('tuleap-crosstracker', 'TQL query is required and cannot be empty'));
        }
    }

    private function getWidgetDao(): CrossTrackerWidgetDao
    {
        return new CrossTrackerWidgetDao();
    }

    private function getQueryDao(): CrossTrackerQueryDao
    {
        return new CrossTrackerQueryDao();
    }

    private function getUserIsAllowedToSeeWidgetChecker(): UserIsAllowedToSeeWidgetChecker
    {
        return new UserIsAllowedToSeeWidgetChecker(
            $this->getWidgetDao(),
            ProjectManager::instance(),
            new URLVerification(),
        );
    }
}
