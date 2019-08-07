<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Service
{
    public const SUMMARY   = 'summary';
    public const ADMIN     = 'admin';
    public const FORUM     = 'forum';
    public const HOMEPAGE  = 'homepage';
    public const ML        = 'mail';
    public const NEWS      = 'news';
    public const CVS       = 'cvs';
    public const FILE      = 'file';
    public const SVN       = 'svn';
    public const WIKI      = 'wiki';
    public const TRACKERV3 = 'tracker';

    public const SCOPE_SYSTEM  = 'system';
    public const SCOPE_PROJECT = 'project';

    private const ICONS = [
        self::ADMIN     => 'fa-cogs',
        self::FORUM     => 'fa-users',
        self::HOMEPAGE  => 'fa-home',
        self::ML        => 'fa-envelope',
        self::NEWS      => 'fa-rss',
        self::CVS       => 'fa-tlp-versioning-cvs',
        self::WIKI      => 'fa-tlp-wiki',
        self::TRACKERV3 => 'fa-list-ol',
    ];

    /**
     * @var array{
     *          service_id: int,
     *          group_id: int,
     *          label: string,
     *          description: string,
     *          short_name: string,
     *          link: string,
     *          is_active: int,
     *          is_used: int,
     *          scope: string,
     *          rank: int,
     *          location: string,
     *          server_id: ?int,
     *          is_in_iframe: int,
     *          is_in_new_tab: bool,
     *          icon: string
     *       }
     */
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

    public function getProject() {
        return $this->project;
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
    function getUrl($url = null) {
        if (is_null($url)) {
            $url = $this->data['link'];
        }
        return $url;
    }

    public function getScope() {
        return $this->data['scope'];
    }

    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    function isAbsolute($url) {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i', $url, $components);
        return isset($components[1]) && $components[1] ? true : false;
    }

    function getPublicArea() {
    }
    function isRequestedPageDistributed(&$request) {
        return false;
    }

    public function displayHeader($title, $breadcrumbs, $toolbar, $params = array()) {
        \Tuleap\Project\ServiceInstrumentation::increment(strtolower($this->getShortName()));

        $GLOBALS['HTML']->setRenderedThroughService(true);
        $GLOBALS['HTML']->addBreadcrumbs($breadcrumbs);

        foreach($toolbar as $t) {
            $class = isset($t['class']) ? 'class="'. $t['class'] .'"' : '';
            $item_title = isset($t['short_title']) ? $t['short_title'] :$t['title'];
            $GLOBALS['HTML']->addToolbarItem('<a href="'. $t['url'] .'" '. $class .'>'. $item_title .'</a>');
        }
        $params['title']  = $title;
        $params['group']  = $this->project->group_id;
        $params['toptab'] = $this->getId();

        if (! isset($params['body_class'])) {
            $params['body_class'] = array();
        }
        $params['body_class'][] = 'service-'. $this->getShortName();

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
            'group' => $this->project->group_id,
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
     * @return bool
     */
    public function isInheritedOnDuplicate() {
        return false;
    }

    public function getInternationalizedName()
    {
        $label      = $this->getLabel();
        $short_name = $this->getShortName();

        return $this->getInternationalizedText($label, "service_{$short_name}_lbl_key");
    }

    public function getInternationalizedDescription()
    {
        $description = $this->getDescription();
        $short_name  = $this->getShortName();

        return $this->getInternationalizedText($description, "service_{$short_name}_desc_key");
    }

    private function getInternationalizedText($text, $key)
    {
        if ($text === $key) {
            return $GLOBALS['Language']->getText('project_admin_editservice', $key);
        }

        if (preg_match('/(.*):(.*)/', $text, $matches)) {
            if ($GLOBALS['Language']->hasText($matches[1], $matches[2])) {
                $text = $GLOBALS['Language']->getText($matches[1], $matches[2]);
            }
        }

        return $text;
    }

    public function getIcon() : string
    {
        if ($this->data['icon'] !== '') {
            return $this->getFontAwesomeIcon($this->data['icon']);
        }
        if (isset(self::ICONS[$this->getShortName()])) {
            return $this->getFontAwesomeIcon(self::ICONS[$this->getShortName()]);
        }
        return $this->getFontAwesomeIcon('fa-angle-double-right');
    }

    protected function getFontAwesomeIcon(string $icon) : string
    {
        return 'fa fa-fw '.$icon;
    }
}
