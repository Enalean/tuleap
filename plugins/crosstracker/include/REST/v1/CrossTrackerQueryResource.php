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
use PFUser;
use ProjectManager;
use Tuleap\CrossTracker\Query\Advanced\ExpertQueryIsEmptyException;
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactoryBuilder;
use Tuleap\CrossTracker\Query\CrossTrackerQuery;
use Tuleap\CrossTracker\Query\CrossTrackerQueryDao;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\Query\QueryCreator;
use Tuleap\CrossTracker\Query\QueryUpdater;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerGetContentRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryContentRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryPostRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryPutRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryRepresentation;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Exceptions\InvalidJsonException;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\InvalidParameterTypeException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\QueryParameterParser;
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

final class CrossTrackerQueryResource extends AuthenticatedResource
{
    public const  ROUTE     = 'crosstracker_query';
    public const  MAX_LIMIT = 50;

    private readonly ProvideCurrentUser $current_user_provider;
    private readonly CrossTrackerArtifactQueryFactoryBuilder $factory_builder;
    private readonly QueryParameterParser $parameter_parser;

    public function __construct()
    {
        $this->current_user_provider = UserManager::instance();
        $this->factory_builder       = new CrossTrackerArtifactQueryFactoryBuilder();
        $this->parameter_parser      = new QueryParameterParser(new JsonDecoder());
    }

    /**
     * @url OPTIONS content
     */
    public function optionsGetContent(): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get results of the CrossTracker query in context of widget
     *
     * query is required. It is a json object. Example:
     * <pre>{ "widget_id": 3, "tql_query": "SELECT  @id FROM  @project = 'self' WHERE  @id >= 1" }</pre>
     *
     * @url GET content
     * @access hybrid
     *
     * @param string $query The query to execute on the widget {@from query}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getContent(string $query, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $query = new CrossTrackerGetContentRepresentation(
                $this->parameter_parser->getInt($query, 'widget_id'),
                $this->parameter_parser->getString($query, 'tql_query'),
            );

            if (! $this->getWidgetDao()->searchWidgetExistence($query->widget_id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }

            $current_user = $this->current_user_provider->getCurrentUser();
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $query->widget_id);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn() => $this->factory_builder->getArtifactFactory()->getArtifactsMatchingQuery(
                    CrossTrackerQueryFactory::fromTqlQueryAndWidgetId($query->tql_query, $query->widget_id),
                    $current_user,
                    $limit,
                    $offset,
                )
            );

            assert($artifacts instanceof CrossTrackerQueryContentRepresentation);
            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $query->widget_id));
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
        } catch (InvalidJsonException) {
            throw new I18NRestException(400, dgettext('tuleap-crosstracker', "Parameter 'query' is invalid"));
        } catch (MissingMandatoryParameterException | InvalidParameterTypeException $exception) {
            throw new I18NRestException(400, sprintf(
                dgettext('tuleap-crosstracker', "Parameter 'query' is invalid: %s"),
                $exception->getMessage(),
            ));
        }
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param string $id ID of the query {@from path}
     */
    public function optionsGetIdContent(string $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get results of the CrossTracker query
     *
     * @url GET {id}/content
     * @access hybrid
     *
     * @param string $id ID of the query {@from path}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getIdContent(string $id, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->current_user_provider->getCurrentUser();
            $query        = $this->getQuery($id, $current_user);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn() => $this->factory_builder->getArtifactFactory()->getArtifactsMatchingQuery($query, $current_user, $limit, $offset)
            );

            assert($artifacts instanceof CrossTrackerQueryContentRepresentation);
            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerQueryNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Query not found'));
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
     * @url OPTIONS {id}/forward_links
     *
     * @param string $id ID of the query {@from path}
     */
    public function optionsGetIdForwardLinks(string $id): void
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
     * @param string $id ID of the query {@from path}
     * @param int $source_artifact_id ID of the artifact {@from query}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getIdForwardLinks(string $id, int $source_artifact_id, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->current_user_provider->getCurrentUser();
            $query        = $this->getQuery($id, $current_user);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn() => $this->factory_builder->getArtifactFactory()->getForwardLinks($query, $source_artifact_id, $current_user, $limit, $offset)
            );

            assert($artifacts instanceof CrossTrackerQueryContentRepresentation);
            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerQueryNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Query not found'));
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
     * @param string $id ID of the query {@from path}
     */
    public function optionsGetIdReverseLinks(string $id): void
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
     * @param string $id ID of the query {@from path}
     * @param int $target_artifact_id ID of the artifact {@from query}
     * @param int $limit Number of elements displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from query}{@min 0}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getIdReverseLinks(string $id, int $target_artifact_id, int $limit = self::MAX_LIMIT, int $offset = 0): CrossTrackerQueryContentRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            $current_user = $this->current_user_provider->getCurrentUser();
            $query        = $this->getQuery($id, $current_user);

            $artifacts = $this->factory_builder->getInstrumentation()->updateQueryDuration(
                fn() => $this->factory_builder->getArtifactFactory()->getReverseLinks($query, $target_artifact_id, $current_user, $limit, $offset)
            );

            assert($artifacts instanceof CrossTrackerQueryContentRepresentation);
            Header::sendPaginationHeaders($limit, $offset, $artifacts->getTotalSize(), self::MAX_LIMIT);
            return $artifacts;
        } catch (CrossTrackerQueryNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Query not found'));
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
     * @url OPTIONS {id}
     *
     * @param string $id ID of the query {@from path}
     */
    public function optionsPutDeleteId(string $id): void
    {
        Header::allowOptionsPutDelete();
    }

    /**
     * Update a CrossTracker query
     *
     * @url PUT {id}
     * @access protected
     *
     * @param string $id ID of the query {@from path}
     * @param CrossTrackerQueryPutRepresentation $query_representation The query to save {@from body}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    protected function put(string $id, CrossTrackerQueryPutRepresentation $query_representation): CrossTrackerQueryRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsPutDelete();

        try {
            $current_user   = $this->current_user_provider->getCurrentUser();
            $previous_query = $this->getQuery($id, $current_user);
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToUpdateWidget($current_user, $previous_query->getWidgetId());
            if ($previous_query->getWidgetId() !== $query_representation->widget_id) {
                throw new I18NRestException(400, dgettext('tuleap-crosstracker', "Given 'widget_id' parameter is invalid"));
            }
            $new_query = CrossTrackerQueryFactory::fromQueryToEdit($previous_query, $query_representation);

            $query_dao = $this->getQueryDao();
            (new QueryUpdater(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                $query_dao,
                $query_dao
            ))
                ->updateQuery($new_query);

            return CrossTrackerQueryRepresentation::fromQuery($new_query);
        } catch (CrossTrackerQueryNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Query not found'));
        }
    }

    /**
     * Delete a query from its widget
     *
     * @url DELETE {id}
     * @access hybrid
     *
     * @param string $id ID of the query {@from path}
     * @status 204
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    protected function delete(string $id): void
    {
        $this->checkAccess();
        Header::allowOptionsPutDelete();

        try {
            $current_user = $this->current_user_provider->getCurrentUser();
            $query        = $this->getQuery($id, $current_user);
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToUpdateWidget($current_user, $query->getWidgetId());

            $this->getQueryDao()->delete($query->getUUID());
        } catch (CrossTrackerQueryNotFoundException) {
            throw new I18NRestException(404, dgettext('tuleap-crosstracker', 'Query not found'));
        }
    }

    /**
     * @url POST
     */
    public function optionsPost(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a new query in the widget
     *
     * @url POST
     * @access hybrid
     *
     * @param CrossTrackerQueryPostRepresentation $query_representation The query to create {@from body}
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 404
     */
    protected function post(CrossTrackerQueryPostRepresentation $query_representation): CrossTrackerQueryRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsPost();

        try {
            if (! $this->getWidgetDao()->searchWidgetExistence($query_representation->widget_id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }
            $current_user = $this->current_user_provider->getCurrentUser();
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToUpdateWidget($current_user, $query_representation->widget_id);

            $new_query = CrossTrackerQueryFactory::fromQueryPostRepresentation($query_representation);

            $query_dao     = $this->getQueryDao();
            $created_query = (new QueryCreator(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                $query_dao,
                $query_dao
            ))
                ->createNewQuery($new_query);

            return CrossTrackerQueryRepresentation::fromQuery($created_query);
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $query_representation->widget_id));
        }
    }

    /**
     * @throws CrossTrackerQueryNotFoundException
     * @throws RestException
     */
    private function getQuery(string $id, PFUser $current_user): CrossTrackerQuery
    {
        $factory = new CrossTrackerQueryFactory(new CrossTrackerQueryDao());
        $query   = $factory->getById($id);
        $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $query->getWidgetId());

        return $query;
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
