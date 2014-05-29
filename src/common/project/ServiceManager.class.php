<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ServiceManager {

    const CUSTOM_SERVICE_SHORTNAME = '';

    /** @var ServiceDao */
    private $dao;

    /** @var string[] */
    private $list_of_plugin_based_services = array();

    /** @var string[] */
    private $list_of_core_services = array(
        self::CUSTOM_SERVICE_SHORTNAME,
        Service::SUMMARY,
        Service::ADMIN,
        Service::HOMEPAGE,
        Service::FORUM,
        Service::ML,
        Service::SURVEY,
        Service::NEWS,
        Service::CVS,
        Service::FILE,
        Service::SVN,
        Service::WIKI,
        Service::TRACKERV3,
        Service::LEGACYDOC,
    );

    /** @var ServiceManager */
    private static $instance;

    public function __construct(ServiceDao $dao) {
        $this->dao = $dao;
    }

    /**
     * ServiceManager is a singleton
     * @return ServiceManager
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c(new ServiceDao());
        }
        return self::$instance;
    }

    /**
     * Only for testing purpose
     *
     * @param ServiceManager $service_manager
     */
    public function setInstance(ServiceManager $service_manager) {
        self::$instance = $service_manager;
    }

    /**
     * Only for testing purpose
     */
    public function clearInstance() {
        self::$instance = null;
    }

    public function enablePluginBasedService(Plugin $plugin) {
        $plugin_based_service = $plugin->getServiceShortname();
        if ($plugin_based_service) {
            $this->list_of_plugin_based_services[] = $plugin_based_service;
        }

    }

    /**
     * @return Service[]
     */
    public function getListOfAllowedServicesForProject(Project $project) {
        $list_of_allowed_services = array();
        $allowed_services_dar = $this->dao->searchByProjectIdAndShortNames(
            $project->getID(),
            array_merge(
                $this->list_of_core_services,
                $this->list_of_plugin_based_services
            )
        );

        foreach ($allowed_services_dar as $row) {
            $classname = $project->getServiceClassName($row['short_name']);
            try {
                $list_of_allowed_services[$row['service_id']] = new $classname($project, $row);
            } catch (ServiceNotAllowedForProjectException $e) {
                //don't display the row for this servce
            }
        }

        return $list_of_allowed_services;
    }

    public function isServiceAllowedForProject(Project $project, $service_id) {
        $list_of_allowed_services = $this->getListOfAllowedServicesForProject($project);

        return isset($list_of_allowed_services[$service_id]);
    }

    public function isServiceAvailableAtSiteLevelByShortName($name) {
        return $this->dao->isServiceAvailableAtSiteLevelByShortName($name);
    }
}