<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use EventManager;
use Luracast\Restler\RestException;
use ProjectHistoryDao;
use Psr\Log\LoggerInterface;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Widget\Add\AlreadyUsedWidgetFault;
use Tuleap\Dashboard\Widget\Add\WidgetDisabledInDashboardFault;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\RESTLogger;
use Tuleap\Timetracking\Admin\TimetrackingUgroupDao;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Permissions\CacheUserCanSeeAllTimesInTrackerVerifier;
use Tuleap\Timetracking\Permissions\PermissionsRetriever;
use Tuleap\Timetracking\Widget\Management\ListOfTimeSpentInArtifactFilter;
use Tuleap\Timetracking\Widget\Management\ManagementDao;
use Tuleap\Timetracking\Widget\Management\ManagerCanSeeTimetrackingOfUserVerifierDao;
use Tuleap\Timetracking\Widget\Management\TimeSpentInArtifactByUserGrouper;
use Tuleap\Timetracking\Widget\Management\UserTimesForManagerProviderDao;
use Tuleap\Timetracking\Widget\Management\UserTimesTimeframeRetriever;
use Tuleap\Timetracking\Widget\Management\VerifierChain\ManagerCanSeeTimesInTrackerVerifier;
use Tuleap\Timetracking\Widget\Management\VerifierChain\ManagerCanViewArtifactVerifier;
use Tuleap\Timetracking\Widget\Management\VerifierChain\ManagerHasRestReadOnlyAdminPermissionVerifier;
use Tuleap\Timetracking\Widget\Management\VerifierChain\ManagerIsSeeingTheirOwnTimeVerifier;
use Tuleap\Timetracking\Widget\Management\ViewableUserRetriever;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use Tuleap\Widget\WidgetFactory;
use UGroupManager;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

final class TimetrackingManagementWidgetResource extends AuthenticatedResource
{
    public const string NAME    = 'timetracking_management_widget';
    private const int MAX_LIMIT = 50;

    /**
     * @url OPTIONS /
     */
    public function allow(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create widget
     *
     * Create a widget in the given dashboard.
     *
     * <p>For now the widget can only be added in user dashboards.</p>
     *
     * @url POST /
     * @access protected
     *
     * @param int $dashboard_id The id of the dashboard {@from body}
     * @param string $dashboard_type The type of the dashboard {@choice user} {@from body}
     *
     */
    public function post(int $dashboard_id, string $dashboard_type): TimetrackingManagamentPostWidgetRepresentation
    {
        $this->checkAccess();

        $widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );

        $handler = new PostWidgetHandler(
            new \Tuleap\Dashboard\Widget\Add\WidgetAdder(
                new DashboardWidgetDao($widget_factory),
                $widget_factory,
                new WidgetCreator(new DashboardWidgetDao($widget_factory)),
                new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao()),
                new ProjectHistoryDao(),
                \ProjectManager::instance(),
            )
        );

        return $handler->handle(\UserManager::instance()->getCurrentUser(), $dashboard_id, $dashboard_type)
            ->match(
                static fn (TimetrackingManagamentPostWidgetRepresentation $representation) => $representation,
                static function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());

                    throw new RestException(
                        match ($fault::class) {
                            WidgetDisabledInDashboardFault::class => 400,
                            AlreadyUsedWidgetFault::class => 400,
                            default => 500
                        },
                        (string) $fault,
                    );
                }
            );
    }

    /**
     * @url OPTIONS {id}
     */
    public function allowQuery(int $id): void
    {
        Header::allowOptionsPut();
    }

    private function getPUTHandler(): QueryPUTHandler
    {
        $dao = new ManagementDao();

        return (new QueryPUTHandler(
            new FromPayloadPeriodBuilder(),
            new FromPayloadUserListBuilder(
                new ViewableUserRetriever(
                    \UserManager::instance(),
                    new ManagerCanSeeTimetrackingOfUserVerifierDao(),
                    new User_ForgeUserGroupPermissionsManager(
                        new User_ForgeUserGroupPermissionsDao(),
                    ),
                ),
            ),
            new TimetrackingManagementWidgetSaver($dao, $dao),
            new PermissionChecker($dao),
        ));
    }

    /**
     * Update a widget
     *
     * Update the configuration of a given Timetracking Management widget.<br>
     *
     * <br>
     * With dates:
     * <br>
     * <pre>
     * {<br>
     * &nbsp;"start_date": "2024-06-06T00:00:00z",<br>
     * &nbsp;"end_date": "2024-06-06T00:00:00z",<br>
     * &nbsp;"users": [<br>
     * &nbsp;&nbsp;{"id": 101},<br>
     * &nbsp;&nbsp;{"id": 102}<br>
     * &nbsp;]<br>
     * }
     * </pre>
     *
     * or with predefined time period:
     * <br>
     * <pre>
     * {<br>
     * &nbsp;"predefined_time_period": "yesterday",<br>
     * &nbsp;"users": [<br>
     * &nbsp;&nbsp;{"id": 101},<br>
     * &nbsp;&nbsp;{"id": 102}<br>
     * &nbsp;]<br>
     * }
     * </pre>
     *
     *
     * @url PUT {id}
     * @access protected
     *
     * @status 200
     * @param int $id Id of the timetracking management widget
     * @param QueryPUTRepresentation $item The edited query
     *
     * @throws RestException
     */
    protected function put(int $id, QueryPUTRepresentation $item): QueryPUTResultRepresentation
    {
        $this->checkAccess();

        Header::allowOptionsPut();

        return $this->getPUTHandler()
            ->handle($id, $item, \UserManager::instance()->getCurrentUser())
            ->match(
                function (UserList $users) {
                    if (count($users->invalid_user_ids) > 0) {
                        $this->getLogger()->debug(
                            sprintf(
                                'The following users do not exist or are not active nor restricted: [%s]',
                                implode(', ', $users->invalid_user_ids),
                            ),
                        );
                    }
                    return QueryPUTResultRepresentation::fromUserList(
                        $users,
                        new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash())
                    );
                },
                function (Fault $fault) {
                    FaultMapper::mapToRestException($fault);
                }
            );
    }

    /**
     * @url OPTIONS {id}/times
     */
    public function optionsTimes(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get times
     *
     * Get the times of users of a given Timetracking Management Widget
     *
     * @url GET {id}/times
     * @access protected
     *
     * @param int $id Id of the timetracking management widget
     * @param int $limit Number of users displayed per page {@from query}{@min 1}{@max 50}
     * @param int $offset Position of the first user to display {@from query}{@min 0}
     *
     * @return array {@type \Tuleap\Timetracking\REST\v1\TimetrackingManagement\UserTimesRepresentation}
     * @psalm-return UserTimesRepresentation[]
     */
    public function getTimes(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $verifier_chain = new ManagerIsSeeingTheirOwnTimeVerifier();
        $verifier_chain
            ->chain(
                new ManagerHasRestReadOnlyAdminPermissionVerifier(
                    new \User_ForgeUserGroupPermissionsManager(new \User_ForgeUserGroupPermissionsDao())
                )
            )->chain(new ManagerCanViewArtifactVerifier())
            ->chain(
                new ManagerCanSeeTimesInTrackerVerifier(
                    new CacheUserCanSeeAllTimesInTrackerVerifier(
                        new PermissionsRetriever(
                            new TimetrackingUgroupRetriever(
                                new TimetrackingUgroupDao(),
                                new UGroupManager(),
                            ),
                        ),
                    ),
                ),
            );

        $dao = new ManagementDao();

        $handler = new TimesGETHandler(
            new UserTimesForManagerProviderDao(\Tracker_ArtifactFactory::instance()),
            new ListOfTimeSpentInArtifactFilter($verifier_chain),
            new TimeSpentInArtifactByUserGrouper(),
            new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            new UserTimesTimeframeRetriever(
                $dao,
                $dao,
                new \DateTimeImmutable(),
            ),
            $dao,
            \UserManager::instance(),
        );

        return $handler->handle($id, $limit, $offset, \UserManager::instance()->getCurrentUser())
            ->match(
                function (PaginatedListOfUserTimesRepresentation $collection) use ($limit, $offset) {
                    Header::sendPaginationHeaders($limit, $offset, $collection->total_size, self::MAX_LIMIT);

                    return $collection->times;
                },
                function (Fault $fault) {
                    FaultMapper::mapToRestException($fault);
                }
            );
    }

    private function getLogger(): LoggerInterface
    {
        return new \WrapperLogger(\Tuleap\REST\RESTLogger::getLogger(), 'timetracking');
    }
}
