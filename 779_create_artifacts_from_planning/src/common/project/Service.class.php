<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

define('SERVICE_MASTER',    'master');
define('SERVICE_SAME',      'same');
define('SERVICE_SATELLITE', 'satellite');
require_once('common/server/ServerFactory.class.php');

require_once('ServiceNotAllowedForProjectException.class.php');

/**
* Service
*/
class Service {
    
    public $data;
    
    /**
     * @var Project
     */
    public $project;
    
    /**
     * Create an instance of Service
     *
     * @param Project $project The project the service belongs to
     * @param array   $data    The service data coming from the db
     *
     * @throws ServiceNotAllowedForProjectException if the Service is not allowed for the project (mainly for plugins)
     */
    public function __construct($project, $data) {
        if (!$this->isAllowed($project)) {
            throw new ServiceNotAllowedForProjectException();
        }
        $this->project = $project;
        $this->data    = $data;
    }
    
    function getGroupId() {
        return $this->data['group_id'];
    }
    function getId() {
        return $this->data['service_id'];
    }
    function getDescription() {
        return $this->data['description'];
    }
    function getShortName() {
        return $this->data['short_name'];
    }
    function getLabel() {
        return $this->data['label'];
    }
    function getRank() {
        return $this->data['rank'];
    }
    function isUsed() {
        return $this->data['is_used'];
    }
    function isActive() {
        return $this->data['is_active'];
    }
    function isIFrame() {
    	return $this->data['is_in_iframe'];
    }
    function getServerId() {
        return $this->data['server_id'];
    }
    function getLocation() {
        return $this->data['location'];
    }
    function getUrl($url = null) {
        if (is_null($url)) {
            $url = $this->data['link'];
        }
        if (!$this->isAbsolute($url) && $this->getLocation() != SERVICE_SAME) {
            $sf =& $this->_getServerFactory();
            if ($s =& $sf->getServerById($this->getServerId())) {
                $url = $s->getUrl($this->_sessionIsSecure()) . $url;
            }
        }
        return $url;
    }
    function &_getServerFactory() {
        return new ServerFactory();
    }
    
    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    function isAbsolute($url) {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i', $url, $components);
        return isset($components[1]) && $components[1] ? true : false;
    }
    function _sessionIsSecure() {
        return session_issecure();
    }
    function getPublicArea() {
    }
    function isRequestedPageDistributed(&$request) {
        return false;
    }
    
    public function displayHeader($title, $breadcrumbs, $toolbar) {
        $GLOBALS['HTML']->setRenderedThroughService(true);
        
        $breadcrumbs = array_merge(
            array(
                array(
                    'title' => $this->project->getPublicName(),
                    'url'   => '/projects/'. $this->project->getUnixName() .'/',
                )
            ),
           $breadcrumbs
        );
        foreach($breadcrumbs as $b) {
            $classname = '';
            if (isset($b['classname'])) {
                $classname = 'class="breadcrumb-step-'. $b['classname'] .'"';
            }
            $GLOBALS['HTML']->addBreadcrumb('<a href="'. $b['url'] .'" '. $classname .'>'. $b['title'] .'</a>');
        }
        foreach($toolbar as $t) {
            $class = isset($t['class']) ? 'class="'. $t['class'] .'"' : '';
            $item_title = isset($t['short_title']) ? $t['short_title'] :$t['title'];
            $GLOBALS['HTML']->addToolbarItem('<a href="'. $t['url'] .'" '. $class .'>'. $item_title .'</a>');
        }
        $params = array(
            'title' => $title, 
            'group' => $this->project->group_id, 
            'toptab' => $this->getId()
        );
        if ($pv = (int)HTTPRequest::instance()->get('pv')) {
            $params['pv'] = (int)$pv;
        }
        
        $this->displayDuplicateInheritanceWarning();
        
        site_project_header($params);
    }
    
    /**
     * Display a warning if the service configuration is not inherited on project creation
     */
    public function displayDuplicateInheritanceWarning() {
        if ($this->project->isTemplate() && !$this->isInheritedOnDuplicate()) {
            $GLOBALS['HTML']->addFeedback('warning', $GLOBALS['Language']->getText('global', 'service_conf_not_inherited'));
        }
    }
    
    public function displayFooter() {
        $params = array(
        );
        if ($pv = (int)HTTPRequest::instance()->get('pv')) {
            $params['pv'] = (int)$pv;
        }
        site_project_footer($params);
    }
    
    public function duplicate($to_project_id, $ugroup_mapping) {
    }
    
    /**
     * Say if the service is allowed for the project
     *
     * @param Project $project
     *
     * @return bool
     */
    protected function isAllowed($project) {
        return true;
    }
    
     /**
     * Say if the service is restricted
     *
     * @param Project $project
     *
     * @return bool
     */
    public function isRestricted() {
        return false;
    }

    /**
     * Return true if service configuration is inherited on clone
     * 
     * @return Boolean
     */
    public function isInheritedOnDuplicate() {
        return false;
    }
}

?>
