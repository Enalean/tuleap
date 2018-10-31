<?php
/**
 * Copyright Enalean (c) 2016 - 2018. All rights reserved.
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
 *
 *
 */

use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\SVN\SVNAuthenticationCacheInvalidator;


/**
* System Event classes
*
*/
class SystemEvent_PROJECT_IS_PRIVATE extends SystemEvent
{
    /**
     * @var SVNAuthenticationCacheInvalidator
     */
    private $svn_authentication_cache_invalidator;

    public function injectDependencies(
        SVNAuthenticationCacheInvalidator $svn_authentication_cache_invalidator
    ) {
        $this->svn_authentication_cache_invalidator = $svn_authentication_cache_invalidator;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link) {
        $txt = '';
        list($group_id, $project_is_private) = $this->getParametersAsArray();
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', project is private: '. ($project_is_private ? 'true' : 'false');
        return $txt;
    }

    /**
     * Process stored event
     */
    function process() {
        list($group_id, $project_is_private) = $this->getParametersAsArray();

        if ($project = $this->getProject($group_id)) {

            if ($project->usesCVS()) {
                if (!Backend::instance('CVS')->setCVSPrivacy($project, $project_is_private)) {
                    $this->error("Could not set cvs privacy for project $group_id");
                    return false;
                }
            }

            if ($project->usesSVN()) {
                $backendSVN    = Backend::instance('SVN');
                if (!$backendSVN->setSVNPrivacy($project, $project_is_private)) {
                    $this->error("Could not set svn privacy for project $group_id");
                    return false;
                }
                if (!$backendSVN->updateSVNAccess($group_id, $project->getSVNRootPath()) ) {
                    $this->error("Could not update svn access file for project $group_id");
                    return false;
                }
            }

            $should_notify_project_members = (bool) ForgeConfig::get(
                ProjectVisibilityConfigManager::SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE
            );

            if ($should_notify_project_members) {
                $this->notifyProjectMembers($project);
            }

            //allows to link plugins to this system event
            $this->callSystemEventListeners( __CLASS__ );

            $this->done();

            return true;
        }

        return false;
    }

    private function notifyProjectMembers(Project $project)
    {
        foreach($project->getMembers() as $member) {
            $this->notifyUser($project, $member);
        }
    }

    private function notifyUser(Project $project, PFUser $user)
    {
        $user_language = $user->getLanguage();
        $purifier = Codendi_HTMLPurifier::instance();

        $title = $user_language->getText(
            'project_privacy',
            'email_visibility_change_title',
            $project->getUnixName()
        );

        $body = $user_language->getText(
            'project_privacy',
            'email_visibility_change_body_' . $project->getAccess(),
            $project->getUnconvertedPublicName()
        );

        $body_text = $purifier->purify($body, CODENDI_PURIFIER_STRIP_HTML);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($user->getEmail());
        $mail->setSubject($purifier->purify($title, CODENDI_PURIFIER_STRIP_HTML));
        $mail->setBodyHtml($body_text);
        $mail->setBodyText($body_text);

        $mail->send();
    }
}

?>
