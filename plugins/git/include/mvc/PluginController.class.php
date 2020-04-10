<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
/**
 * Link datas, views and actions.
 *
 * This is a part of lite Model/View/Controler design pattern.
 *
 */

abstract class PluginController
{

    /**
     * List of PluginViews method name to execute
     * @var Array
     */
    protected $views = array('header' => array(), 'footer' => array());
    /**
     * List of PluginActions method name to execute
     * @var Array
     */
    protected $actions = array();
    /**
     * This array allows data storage and sharing between Actions and Views
     * @var Array
     */
    protected $actionResultData = array('dummy' => 'dummy');
    /**
     * Logical actions, they allow one to control execution of user stories which usually call several PluginActions at one time
     * @var Array
     */
    protected $permittedActions;

    /**
     * Boolean to choose to add default page header and footer in the response.
     *
     * @var bool
     */
    protected $default_page_rendering = true;

    public function __construct(UserManager $user_manager, Codendi_Request $request)
    {
        $this->user             = $user_manager->getCurrentUser();
        $this->request          = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getUser()
    {
        return $this->user;
    }
    /**
     * Function called by process method
     */
    public function request()
    {
    }

    /**
     * Wrapper of global redirect method
     * @param String url
     */
    public function redirect($url)
    {
        $GLOBALS['HTML']->redirect($url);
    }

    /**
     * Wrapper
     * @param String $msg
     */
    public function addError($msg)
    {
        $GLOBALS['Response']->addFeedback('error', $msg);
    }

    /**
     * Wrapper
     * @param String $msg
     */
    public function addWarn($msg)
    {
        $GLOBALS['Response']->addFeedback('warning', $msg);
    }

    /**
     * Wrapper
     * @param String $msg
     */
    public function addInfo($msg)
    {
        $GLOBALS['Response']->addFeedback('info', $msg);
    }

    /**
     * This function allows one to add action to control their execution
     * @see isAPermittedAction
     * @param Array $actions a list of action name
     */
    public function setPermittedActions($actions)
    {
        $this->permittedActions = $actions;
    }

    /**
     * Returns the array of actions
     * @return Array
     */
    public function getPermittedActions()
    {
        return $this->permittedActions;
    }
    public function addPermittedAction($action)
    {
        $this->permittedActions[] = $action;
    }
    /**
     * This function is useful to control action execution in the controller, this kind of action is a logical view not a method of PluginAction class
     * One should use this to filter the 'action' parameter in the HTTP request (add, clone, del, help etc...)
     * @param String $actionName
     * @return bool
     */
    public function isAPermittedAction($actionName)
    {
        return in_array($actionName, $this->permittedActions);
    }

    /**
     * Add actions result data
     * @see getData()
     */
    public function addData($data)
    {
        if (!empty($data) && is_array($data)) {
            $this->actionResultData = array_merge($this->actionResultData, $data);
        }
    }

    /**
     * Gives data added during PluginAction methods (actions)
     * @return Array data
     */
    public function getData()
    {
        return $this->actionResultData;
    }

    public function addView($viewName, $params = array())
    {
        $this->views[$viewName] = $params;
    }

    public function addAction($actionName, $params = array())
    {
        $this->actions[$actionName] = $params;
    }

    /**
     * This functions execute all views added to the actions class array ($this->views)
     * An action is a method of PluginViews class child, several can be added for one request
     * @TODO associate an action and a view in order to skip action call to provide data to a given view.(like Symfony framework component)
     * @return null
     */
    public function executeViews()
    {
        $wv = $this->instantiateView();
        //this allow to skip header
        if (! $this->isADownload() && $this->default_page_rendering) {
            $wv->display('header', $this->views['header']);
        }
        foreach ($this->views as $viewName => $viewParams) {
            if ($viewName != 'header' && $viewName != 'footer') {
                $wv->display($viewName, $viewParams);
            }
        }

        if ($this->default_page_rendering) {
            $wv->display('footer', $this->views['footer']);
        }
    }

    abstract protected function instantiateView();

    /**
     * This functions execute all methods added to the actions class array ($this->actions)
     * An action is a method of PluginAction class child, several can be added for one request
     * @TODO associate an action and a view in order to skip action call to provide data to a given view.(like Symfony framework component)
     * @return null
     */
    public function executeActions()
    {
        if (empty($this->actions)) {
            return false;
        }
        $results       = array();
        $className     = static::class . 'Actions';
        if (! class_exists($className)) {
            throw new LogicException("$className does not exist");
        }
        $wa            = $this->instantiateAction($className);
        foreach ($this->actions as $name => $params) {
            $wa->process($name, $params);
        }
    }

    /**
     * Instantiate an action based on a given name.
     *
     * Can be overriden to pass additionnal parameters to the action
     *
     * @param string $action The name of the action
     *
     * @psalm-param class-string $action
     *
     * @return PluginActions
     */
    protected function instantiateAction($action)
    {
        return new $action($this);
    }

    /**
     * Render everything
     */
    public function process()
    {
        $this->request();
        $this->executeActions();
        $this->executeViews();
    }

    /**
     * Sets whether the default page header and footer are added to the response
     *
     * @param bool $bool
     */
    protected function setDefaultPageRendering($bool)
    {
        $this->default_page_rendering = $bool;
    }

    /**
     * @return bool
     */
    public function isADownload()
    {
        $action_type = $this->request->get('a');
        return $this->request->get('noheader') == 1 ||
                $action_type === 'snapshot' ||
                $action_type === 'atom' ||
                $action_type === 'rss' ||
                $action_type === 'commitdiff_plain' ||
                $action_type === 'blob_plain';
    }
}
