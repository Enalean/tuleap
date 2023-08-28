<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Widget;

use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
use Tuleap\Widget\Event\GetProjectWidgetList;
use Tuleap\Widget\Event\GetUserWidgetList;
use Tuleap\Widget\Event\GetWidget;
use Tuleap\Widget\ProjectMembers\ProjectMembers;
use User_ForgeUserGroupPermission_ProjectApproval;
use User_ForgeUserGroupPermissionsManager;
use UserManager;
use Widget;
use Widget_MyAdmin;
use Widget_MyArtifacts;
use Widget_MyBookmarks;
use Widget_MyImageViewer;
use Widget_MyLatestSvnCommits;
use Widget_MyMonitoredForums;
use Widget_MyMonitoredFp;
use Widget_MyRss;
use Widget_MySystemEvent;
use Widget_ProjectDescription;
use Widget_ProjectImageViewer;
use Widget_ProjectLatestFileReleases;
use Widget_ProjectLatestSvnCommits;
use Widget_ProjectPublicAreas;
use Widget_ProjectRss;
use Widget_ProjectSvnStats;

class WidgetFactory implements IBuildInstanceOfWidgets
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_ugroup_permissions_manager;

    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;

    public function __construct(
        UserManager $user_manager,
        User_ForgeUserGroupPermissionsManager $forge_ugroup_permissions_manager,
        EventDispatcherInterface $event_manager,
    ) {
        $this->user_manager                     = $user_manager;
        $this->forge_ugroup_permissions_manager = $forge_ugroup_permissions_manager;
        $this->event_manager                    = $event_manager;
    }

    public function getInstanceByWidgetName(string $widget_name): ?\Widget
    {
        $widget             = null;
        $user               = $this->user_manager->getCurrentUser();
        $user_is_super_user = $user->isSuperUser();

        switch ($widget_name) {
            case 'myprojects':
                $widget = new MyProjects();
                break;
            case 'mybookmarks':
                $widget = new Widget_MyBookmarks();
                break;
            case 'mymonitoredforums':
                $widget = new Widget_MyMonitoredForums();
                break;
            case 'mymonitoredfp':
                $widget = new Widget_MyMonitoredFp();
                break;
            case 'mylatestsvncommits':
                $widget = new Widget_MyLatestSvnCommits();
                break;
            case 'myartifacts':
                $widget = new Widget_MyArtifacts();
                break;
            case 'myrss':
                $widget = new Widget_MyRss();
                break;
            case 'myimageviewer':
                $widget = new Widget_MyImageViewer();
                break;
            case 'myadmin':
                if (! $user_is_super_user) {
                    $can_access = $this->forge_ugroup_permissions_manager->doesUserHavePermission(
                        $user,
                        new User_ForgeUserGroupPermission_ProjectApproval()
                    );
                }

                if ($user_is_super_user || $can_access) {
                    $widget = new Widget_MyAdmin($user_is_super_user);
                }
                break;
            case 'mysystemevent':
                if ($user_is_super_user) {
                    $widget = new Widget_MySystemEvent();
                }
                break;
            case MyWelcomeMessage::NAME:
                $widget = new MyWelcomeMessage($user);
                break;
            case 'projectdescription':
                $widget = new Widget_ProjectDescription();
                break;
            case ProjectHeartbeat::NAME:
                $widget = new ProjectHeartbeat();
                break;
            case 'projectmembers':
                $widget = new ProjectMembers();
                break;
            case 'projectlatestfilereleases':
                $widget = new Widget_ProjectLatestFileReleases();
                break;
            case 'projectlatestnews':
                $widget = new Widget_ProjectLatestNews();
                break;
            case 'projectpublicareas':
                $widget = new Widget_ProjectPublicAreas();
                break;
            case 'projectrss':
                $widget = new Widget_ProjectRss();
                break;
            case 'projectsvnstats':
                $widget = new Widget_ProjectSvnStats();
                break;
            case 'projectlatestsvncommits':
                $widget = new Widget_ProjectLatestSvnCommits();
                break;
            case 'projectimageviewer':
                $widget = new Widget_ProjectImageViewer();
                break;
            case 'projectcontacts':
                $widget = new ProjectContacts();
                break;
            case Note\ProjectNote::NAME:
                $widget = new Note\ProjectNote(
                    new Note\NoteDao(),
                    TemplateRendererFactory::build()->getRenderer(
                        __DIR__ . '/../../templates/widgets'
                    )
                );
                break;
            case Note\UserNote::NAME:
                $widget = new Note\UserNote(
                    new Note\NoteDao(),
                    TemplateRendererFactory::build()->getRenderer(
                        __DIR__ . '/../../templates/widgets'
                    )
                );
                break;
            default:
                $get_widget_event = $this->event_manager->dispatch(new GetWidget($widget_name));
                $widget           = $get_widget_event->getWidget();
                break;
        }

        if (! $widget || ! $widget instanceof Widget) {
            $widget = null;
        }

        return $widget;
    }

    /**
     * @param string $dashboard_type
     * @return Widget[]
     */
    public function getWidgetsForOwnerType($dashboard_type): array
    {
        if ($dashboard_type === UserDashboardController::DASHBOARD_TYPE) {
            $event = new GetUserWidgetList();
        } else {
            $event = new GetProjectWidgetList();
        }

        $this->event_manager->dispatch($event);
        $widget_names = $event->getWidgets();

        $widgets = [];
        foreach ($widget_names as $widget_name) {
            $widgets[] = $this->getInstanceByWidgetName($widget_name);
        }

        return array_filter($widgets);
    }

    /**
     * @param $dashboard_type
     * @return string
     */
    public function getOwnerTypeByDashboardType($dashboard_type)
    {
        return $dashboard_type === UserDashboardController::DASHBOARD_TYPE ?
            UserDashboardController::LEGACY_DASHBOARD_TYPE :
            ProjectDashboardController::LEGACY_DASHBOARD_TYPE;
    }
}
