<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Git\GitViews;

use EventManager;
use GitPermissionsManager;
use HTTPRequest;
use PFUser;
use Project;
use Tuleap\Layout\BaseLayout;

class GitViewHeader
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    /**
     * GitViewHeader constructor.
     * @param EventManager $event_manager
     */
    public function __construct(EventManager $event_manager, GitPermissionsManager $permissions_manager)
    {
        $this->event_manager       = $event_manager;
        $this->permissions_manager = $permissions_manager;
    }

    public function header(HTTPRequest $request, PFUser $user, BaseLayout $layout, Project $project)
    {
        $this->getToolbar($user, $layout, $project);

        $layout->header(array(
            'title'      => $GLOBALS['Language']->getText('plugin_git', 'title'),
            'group'      => $project->getID(),
            'toptab'     => $GLOBALS['Language']->getText('plugin_git', 'title'),
            'body_class' => $this->getAdditionalBodyClasses($request)
        ));
    }

    private function getAdditionalBodyClasses(HTTPRequest $request)
    {
        $classes = array();
        $params  = array(
            'request' => $request,
            'classes' => &$classes
        );

        $this->event_manager->processEvent(GIT_ADDITIONAL_BODY_CLASSES, $params);

        return $classes;
    }

    private function getToolbar(PFUser $user, BaseLayout $layout, Project $project)
    {
        $layout->addToolbarItem($this->linkTo($GLOBALS['Language']->getText('plugin_git', 'bread_crumb_home'), '/plugins/git/' . urlencode($project->getUnixNameLowerCase()) . '/'));
        $layout->addToolbarItem($this->linkTo($GLOBALS['Language']->getText('plugin_git', 'fork_repositories'), '/plugins/git/?group_id='.$project->getID() .'&action=fork_repositories'));
        $layout->addToolbarItem($this->linkTo($GLOBALS['Language']->getText('plugin_git', 'bread_crumb_help'), 'javascript:help_window(\'/doc/'.$user->getShortLocale().'/user-guide/git.html\')'));

        if ($this->permissions_manager->userIsGitAdmin($user, $project)) {
            $layout->addToolbarItem($this->linkTo($GLOBALS['Language']->getText('plugin_git', 'bread_crumb_admin'), '/plugins/git/?group_id='.$project->getID() .'&action=admin'));
        }
    }


    private function linkTo($link, $href, $options = '')
    {
        return '<a href="'.$href.'" '.$options.' >'.$link.'</a>';
    }
}
