<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'autoload.php';
require_once 'constants.php';

class pullrequestPlugin extends Plugin {

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::REST_RESOURCES);

        if (defined('GIT_BASE_URL')) {
            $this->addHook(REST_GIT_PULL_REQUEST_ENDPOINTS);
            $this->addHook(REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY);
        }
    }

    public function getServiceShortname() {
        return 'plugin_pullrequest';
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
        return array('git');
    }

    public function service_classnames($params) {
        $params['classnames'][$this->getServiceShortname()] = 'PullRequest\\Service';
    }

    /**
     * @return Tuleap\PullRequest\PluginInfo
     */
    public function getPluginInfo() {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\PullRequest\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources(array $params) {
        $injector = new Tuleap\PullRequest\REST\ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see REST_GIT_PULL_REQUEST_ENDPOINTS
     */
    public function rest_git_pull_request_endpoints($params) {
        $params['available'] = true;
    }

    /**
     * @see REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY
     */
    public function rest_git_pull_request_get_for_repository($params) {
        $version = $params['version'];
        $class   = "\\Tuleap\\PullRequest\\REST\\$version\\RepositoryResource";
        $repository_resource = new $class;

        $params['result'] = $repository_resource->getPaginatedPullRequests(
            $params['repository'],
            $params['limit'],
            $params['offset']
        );
    }
}
