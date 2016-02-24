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
            $this->addHook('cssfile');
            $this->addHook('javascript_file');
            $this->addHook(REST_GIT_PULL_REQUEST_ENDPOINTS);
            $this->addHook(REST_GIT_PULL_REQUEST_GET_FOR_REPOSITORY);
            $this->addHook(GIT_ADDITIONAL_INFO);
            $this->addHook(GIT_ADDITIONAL_BODY_CLASSES);
            $this->addHook(GIT_ADDITIONAL_PERMITTED_ACTIONS);
            $this->addHook(GIT_HANDLE_ADDITIONAL_ACTION);
            $this->addHook(GIT_VIEW);
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

    public function cssfile($params) {
        if (strpos($_SERVER['REQUEST_URI'], GIT_BASE_URL . '/') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getPluginPath() . '/js/angular/bin/assets/tuleap-pullrequest.css" />';
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getThemePath() . '/css/style.css" />';
        }
    }

    public function javascript_file() {
        if (strpos($_SERVER['REQUEST_URI'], GIT_BASE_URL . '/') === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/js/angular/bin/assets/tuleap-pullrequest.js"></script>';
        }
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

    /**
     * @see GIT_ADDITIONAL_INFO
     */
    public function git_additional_info($params) {
        $repository       = $params['repository'];
        $nb_pull_requests = $this->getPullRequestFactory()->countPullRequestOfRepository($repository);

        $renderer  = $this->getTemplateRenderer();
        $presenter = new Tuleap\PullRequest\AdditionalInfoPresenter($repository, $nb_pull_requests);

        $params['info'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
    }

    /**
     * @see GIT_ADDITIONAL_BODY_CLASSES
     */
    public function git_additional_body_classes($params) {
        if ($params['request']->get('action') === 'pull-requests') {
            $params['classes'][] = 'git-pull-requests';
        }
    }

    /**
     * @see GIT_ADDITIONAL_PERMITTED_ACTIONS
     */
    public function git_additional_permitted_actions($params) {
        $repository = $params['repository'];
        $user       = $params['user'];

        if ($repository && $repository->userCanRead($user)) {
            $params['permitted_actions'][] = 'pull-requests';
        }
    }

    /**
     * @see GIT_HANDLE_ADDITIONAL_ACTION
     */
    public function git_handle_additional_action($params) {
        $git_controller = $params['git_controller'];
        $repository     = $params['repository'];

        if ($params['action'] === 'pull-requests') {
            $params['handled'] = true;

            if ($repository) {
                $git_controller->addAction('getRepositoryDetails', array($repository->getProjectId(), $repository->getId()));
                $git_controller->addView('view');

            } else {
                $git_controller->redirectNoRepositoryError();
            }
        }
    }

    /**
     * @see GIT_VIEW
     */
    public function git_view($params) {
        $repository = $params['repository'];
        $user       = $params['user'];
        $request    = $params['request'];

        if ($request->get('action') === 'pull-requests') {
            $renderer  = $this->getTemplateRenderer();
            $presenter = new Tuleap\PullRequest\PullRequestPresenter($repository->getId(), $user->getId(), $user->getShortLocale());

            $params['view'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
        }
    }

    private function getPullRequestFactory() {
        return new Tuleap\PullRequest\Factory(new Tuleap\PullRequest\Dao());
    }

    private function getTemplateRenderer() {
        return TemplateRendererFactory::build()->getRenderer(PULLREQUEST_BASE_DIR . '/templates');
    }
}
