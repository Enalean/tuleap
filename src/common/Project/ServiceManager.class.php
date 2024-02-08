<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Event\ProjectServiceBeforeDeactivation;
use Tuleap\Project\Service\ListOfAllowedServicesForProjectRetriever;
use Tuleap\Project\Service\ServiceCannotBeUpdatedException;
use Tuleap\Project\Service\ServiceClassnameRetriever;
use Tuleap\Project\Service\ServiceNotFoundException;
use Tuleap\Project\ServiceCanBeUpdated;

class ServiceManager implements ListOfAllowedServicesForProjectRetriever, ServiceCanBeUpdated //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const CUSTOM_SERVICE_SHORTNAME = '';

    /** @var string[] */
    private $list_of_core_services = [
        self::CUSTOM_SERVICE_SHORTNAME,
        Service::SUMMARY,
        Service::ADMIN,
        Service::HOMEPAGE,
        Service::FORUM,
        Service::NEWS,
        Service::FILE,
        Service::SVN,
        Service::WIKI,
        Service::TRACKERV3,
    ];

    private $list_of_services_per_project = [];

    /** @var ServiceManager */
    private static $instance;

    private function __construct(
        private readonly ServiceDao $dao,
        private readonly ProjectManager $project_manager,
        private readonly ServiceClassnameRetriever $service_classname_retriever,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    /**
     * ServiceManager is a singleton
     * @return ServiceManager
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(
                new ServiceDao(),
                ProjectManager::instance(),
                new ServiceClassnameRetriever(EventManager::instance()),
                EventManager::instance(),
            );
        }
        return self::$instance;
    }

    /**
     * Only for testing purpose
     *
     */
    public static function setInstance(ServiceManager $service_manager)
    {
        self::$instance = $service_manager;
    }

    /**
     * Only for testing purpose
     */
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * @return Service[]
     */
    public function getListOfAllowedServicesForProject(Project $project): array
    {
        if (! isset($this->list_of_services_per_project[$project->getID()])) {
            $this->list_of_services_per_project[$project->getID()] = [];
            $allowed_services_dar                                  = $this->dao->searchByProjectIdAndShortNames(
                $project->getID(),
                array_merge(
                    $this->list_of_core_services,
                    $this->getListOfPluginBasedServices($project)
                )
            );

            foreach ($allowed_services_dar as $row) {
                try {
                    $service                                                                   = $this->instantiateFromRow($project, $row);
                    $this->list_of_services_per_project[$project->getID()][$row['service_id']] = $service;
                } catch (ServiceNotAllowedForProjectException $e) {
                    //don't display the row for this servce
                }
            }
        }

        return $this->list_of_services_per_project[$project->getID()];
    }

    private function getListOfPluginBasedServices(Project $project)
    {
        $services = [];
        EventManager::instance()->processEvent(Event::SERVICES_ALLOWED_FOR_PROJECT, ['project' => $project, 'services' => &$services]);
        return $services;
    }

    public function isServiceAllowedForProject(Project $project, $service_id)
    {
        $list_of_allowed_services = $this->getListOfAllowedServicesForProject($project);

        return isset($list_of_allowed_services[$service_id]);
    }

    public function isServiceAvailableAtSiteLevelByShortName($name)
    {
        return $this->dao->isServiceAvailableAtSiteLevelByShortName($name);
    }

    private function isServiceActiveInProject($project, $name)
    {
        $project_id = $project->getId();
        return $this->dao->isServiceActiveInProjectByShortName($project_id, $name);
    }

    public function toggleServiceUsage(Project $project, $short_name, $is_used)
    {
        if ($this->isServiceAvailableAtSiteLevelByShortName($short_name) || $this->isServiceActiveInProject($project, $short_name)) {
            if ($this->doesServiceUsageChange($project, $short_name, $is_used)) {
                $this->updateServiceUsage($project, $short_name, $is_used);
            }
        }
    }

    private function updateServiceUsage(Project $project, $short_name, $is_used)
    {
        $this->dao->updateServiceUsageByShortName($project->getID(), $short_name, $is_used);
        ProjectManager::instance()->clearProjectFromCache($project->getID());

        $reference_manager = ReferenceManager::instance();
        $reference_manager->updateReferenceForService($project->getID(), $short_name, ($is_used ? "1" : "0"));

        $event_manager = EventManager::instance();
        $event_manager->processEvent(
            Event::SERVICE_IS_USED,
            [
                'shortname' => $short_name,
                'is_used'   => $is_used ? true : false,
                'group_id'  => $project->getID(),
            ]
        );
    }

    /**
     * @throws ServiceCannotBeUpdatedException
     */
    public function checkServiceCanBeUpdated(Project $project, string $short_name, bool $is_used, PFUser $user): void
    {
        if ($short_name === 'admin' && ! $is_used) {
            throw new ServiceCannotBeUpdatedException(_('Admin service cannot be disabled.'));
        }

        if ($is_used === false) {
            $event = $this->event_dispatcher->dispatch(new ProjectServiceBeforeDeactivation($project, $short_name));

            if ($event->doesPluginSetAValue() && ! $event->canServiceBeDeactivated()) {
                throw new ServiceCannotBeUpdatedException($event->getWarningMessage());
            }

            return;
        }

        if (! $this->doesServiceUsageChange($project, $short_name, $is_used)) {
            return;
        }

        $event = new ProjectServiceBeforeActivation($project, $short_name, $user);
        $this->event_dispatcher->dispatch($event);

        if ($event->doesPluginSetAValue() && ! $event->canServiceBeActivated()) {
            throw new ServiceCannotBeUpdatedException($event->getWarningMessage());
        }
    }

    private function doesServiceUsageChange(Project $project, string $short_name, bool $new_is_used): bool
    {
        $previous_is_used = $project->usesService($short_name);

        return $previous_is_used !== $new_is_used;
    }

    /**
     * @throws ServiceNotAllowedForProjectException
     */
    public function getService(int $id): Service
    {
        $row = $this->dao->searchById($id)->getRow();
        if (! $row) {
            throw new ServiceNotFoundException();
        }

        $project = $this->project_manager->getProject($row['group_id']);
        if ($project->isError()) {
            throw new ServiceNotFoundException();
        }

        return $this->instantiateFromRow($project, $row);
    }

    /**
     * @return Service
     */
    private function instantiateFromRow(Project $project, array $row)
    {
        $classname = $this->service_classname_retriever->getServiceClassName($row['short_name']);

        return new $classname($project, $row);
    }
}
