<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Kanban\SemanticStatusNotFoundException;
use BackendLogger;
use Luracast\Restler\RestException;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Kanban\RealTimeMercure\KanbanStructureRealTimeMercure;
use Tuleap\RealTimeMercure\Client;
use Tuleap\RealTimeMercure\ClientBuilder;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\REST\Header;
use Tuleap\Kanban\KanbanPermissionsManager;
use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanNotFoundException;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanColumnDao;
use Tuleap\Kanban\KanbanColumnManager;
use Tuleap\Kanban\KanbanColumnNotFoundException;
use Tuleap\Kanban\KanbanUserNotAdminException;
use Tuleap\Kanban\KanbanColumnNotRemovableException;
use AgileDashboardStatisticsAggregator;
use TrackerFactory;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use UserManager;
use PFUser;
use Tuleap\Kanban\KanbanUserPreferences;
use Tuleap\Kanban\KanbanActionsChecker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\RealTime\NodeJSClient;
use Tracker_Permission_PermissionsSerializer;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tuleap\RealTime\MessageDataPresenter;
use Tuleap\Kanban\KanbanRightsPresenter;

class KanbanColumnsResource
{
    public const MAX_LIMIT        = 100;
    public const HTTP_CLIENT_UUID = 'HTTP_X_CLIENT_UUID';

    /** @var KanbanFactory */
    private $kanban_factory;

    /** @var KanbanColumnFactory */
    private $kanban_column_factory;

    /** @var KanbanColumnManager */
    private $kanban_column_manager;

    /** @var AgileDashboardStatisticsAggregator */
    private $statistics_aggregator;

    /** @var TrackerFactory */
    private $tracker_factory;
    /**
     * @var NodeJSClient
     */
    private $node_js_client;
    /**
     * @var Tracker_Permission_PermissionsSerializer
     */
    private $permissions_serializer;

    private Client $mercure_client;

    private KanbanStructureRealTimeMercure $kanban_structural_realtime;

    public function __construct()
    {
        $this->tracker_factory = TrackerFactory::instance();

        $this->kanban_factory = new KanbanFactory(
            $this->tracker_factory,
            new KanbanDao()
        );

        $kanban_column_dao           = new KanbanColumnDao();
        $permissions_manager         = new KanbanPermissionsManager();
        $this->kanban_column_factory = new KanbanColumnFactory(
            $kanban_column_dao,
            new KanbanUserPreferences()
        );
        $this->kanban_column_manager = new KanbanColumnManager(
            $kanban_column_dao,
            new BindStaticValueDao(),
            new KanbanActionsChecker(
                $this->tracker_factory,
                $permissions_manager,
                Tracker_FormElementFactory::instance(),
                SubmissionPermissionVerifier::instance(),
            )
        );

        $this->statistics_aggregator = new AgileDashboardStatisticsAggregator();

        $this->node_js_client         = new NodeJSClient(
            HttpClientFactory::createClientForInternalTuleapUse(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            BackendLogger::getDefaultLogger()
        );
        $this->permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );

        $this->mercure_client             = ClientBuilder::build(ClientBuilder::DEFAULTPATH);
        $this->kanban_structural_realtime = new KanbanStructureRealTimeMercure($this->mercure_client);
    }

    /**
     * @url OPTIONS
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     */
    public function options(): void
    {
        Header::allowOptionsPatchDelete();
    }

    /**
     * Update column
     *
     * Change column properties
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url PATCH {id}
     *
     * @param int                             $id        Id of the column
     * @param int                             $kanban_id Id of the Kanban {@from query}
     * @param KanbanColumnPATCHRepresentation $updated_column_properties The kanban column {@from body} {@type Tuleap\Kanban\REST\v1\KanbanColumnPATCHRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patch($id, $kanban_id, KanbanColumnPATCHRepresentation $updated_column_properties): void
    {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $kanban_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        $column = $this->kanban_column_factory->getColumnForAKanban($kanban, $id, $current_user);

        try {
            if (isset($updated_column_properties->wip_limit)) {
                $this->kanban_column_manager->updateWipLimit(
                    $current_user,
                    $kanban,
                    $column,
                    $updated_column_properties->wip_limit,
                );
            }

            if (isset($updated_column_properties->label) && ! $this->kanban_column_manager->updateLabel($current_user, $kanban, $column, $updated_column_properties->label)) {
                throw new RestException(500);
            }
        } catch (KanbanColumnNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (KanbanUserNotAdminException $exception) {
            throw new RestException(401, $exception->getMessage());
        } catch (SemanticStatusNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        $this->statistics_aggregator->addWIPModificationHit(
            $this->getProjectIdForKanban($kanban)
        );

        if (isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $data    = [
                'id'        => $id,
                'label'     => $updated_column_properties->label,
                'wip_limit' => $updated_column_properties->wip_limit,
            ];
            $message = new MessageDataPresenter(
                $current_user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban_column:edit',
                $data
            );
            $this->node_js_client->sendMessage($message);
            if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
                $this->kanban_structural_realtime->sendStructureUpdate($kanban);
            }
        }
    }

    /**
     * Delete column
     *
     * Delete a column from its Kanban
     *
     * <pre>
     * /!\ Kanban REST routes are under construction and subject to changes /!\
     * </pre>
     *
     * @url DELETE {id}
     *
     * @param int $id           Id of the column
     * @param int $kanban_id    Id of the Kanban {@from query}
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function delete($id, $kanban_id): void
    {
        $current_user = $this->getCurrentUser();
        $kanban       = $this->getKanban($current_user, $kanban_id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $this->getKanbanProject($kanban)
        );

        try {
            $column = $this->kanban_column_factory->getColumnForAKanban($kanban, $id, $current_user);
        } catch (KanbanColumnNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (SemanticStatusNotFoundException $exception) {
            throw new RestException(404, $exception->getMessage());
        }

        try {
            $this->kanban_column_manager->deleteColumn($current_user, $kanban, $column);
        } catch (KanbanColumnNotRemovableException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Tuleap\Kanban\KanbanSemanticStatusBasedOnASharedFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Tuleap\Kanban\KanbanSemanticStatusNotBoundToStaticValuesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Tuleap\Kanban\KanbanSemanticStatusNotDefinedException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Tuleap\Kanban\KanbanTrackerNotDefinedException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if (isset($_SERVER[self::HTTP_CLIENT_UUID]) && $_SERVER[self::HTTP_CLIENT_UUID]) {
            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if ($tracker === null) {
                throw new \RuntimeException('Tracker does not exist');
            }
            $rights  = new KanbanRightsPresenter($tracker, $this->permissions_serializer);
            $message = new MessageDataPresenter(
                $current_user->getId(),
                $_SERVER[self::HTTP_CLIENT_UUID],
                $kanban->getId(),
                $rights,
                'kanban_column:delete',
                $column->getId()
            );

            $this->node_js_client->sendMessage($message);
            if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
                $this->kanban_structural_realtime->sendStructureUpdate($kanban);
            }
        }
    }

    private function getKanban(PFUser $user, int $id): Kanban
    {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    private function getCurrentUser(): PFUser
    {
        return UserManager::instance()->getCurrentUser();
    }

    private function getProjectIdForKanban(Kanban $kanban): int
    {
        return (int) $this->getKanbanProject($kanban)->getGroupId();
    }

    private function getKanbanProject(Kanban $kanban): \Project
    {
        $kanban_tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($kanban_tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }

        return $kanban_tracker->getProject();
    }
}
