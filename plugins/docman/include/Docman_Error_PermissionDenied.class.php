<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
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

class Docman_Error_PermissionDenied extends Error_PermissionDenied
{
    public function buildInterface(PFUser $user, Project $project)
    {
        if ($user->isAnonymous()) {
            $event_manager = EventManager::instance();
            $redirect      = new URLRedirect($event_manager);
            $redirect->redirectToLogin();
        } else {
            $this->buildPermissionDeniedInterface($project);
        }
    }

    private function buildPermissionDeniedInterface(Project $project)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        site_header(\Tuleap\Layout\HeaderConfiguration::fromTitle($GLOBALS['Language']->getText('include_exit', 'exit_error')));
        echo "<b>" . $purifier->purify(dgettext('tuleap-docman', 'You do not have the permission to access the document')) . "</b>";
        echo '<br>';
        echo "<br>" . $purifier->purify(dgettext('tuleap-docman', 'Permission denied set on documents. You can not view this documents unless administrator grant you access.'));

        $message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
        $pm      = ProjectManager::instance();
        $dar     = $pm->getMessageToRequesterForAccessProject($project->getID());
        if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester") {
                $message = $row['msg_to_requester'];
            }
        }
        echo dgettext('tuleap-docman', '<br><a href="/my/">Back to your personal page</a><em> or you can request it to him.</em></br> Write your message below and click on the button to send your request to the project administrators.');
        echo '<br>';
        echo '<form action="' . $purifier->purify('/plugins/docman/sendmessage.php') . '" method="post" name="display_form">
              <textarea wrap="virtual" rows="5" cols="70" name="' . $purifier->purify('msg_docman_access') . '">' . $purifier->purify($message) . ' </textarea></p>
              <input type="hidden" id="func" name="func" value="' . $purifier->purify('docman_access_request') . '">
              <input type="hidden" id="groupId" name="groupId" value="' . $purifier->purify($project->getID()) . '">
              <input type="hidden" id="data" name="url_data" value="' . $purifier->purify($_SERVER['REQUEST_URI']) . '">
              <br><input name="Submit" type="submit" value="' . $purifier->purify($GLOBALS['Language']->getText('include_exit', 'send_mail')) . '"/></br>
          </form>';

        $GLOBALS['HTML']->footer([]);
    }

    /**
     * It redirects the show action pointed with the document url  to its details section
     *
     * If user requires for istance the url  "https://codendi.org/plugins/docman/?group_id=1564&action=show&id=96739"
     * the url sent to the project admin will be edited to "https://codendi.org/plugins/docman/?group_id=1564&action=details&section=permissions&id=96739"
     *
     * @parameter String $url
     *
     * @return String
     */
    public function urlTransform($url)
    {
        $query = $this->urlQueryToArray($url);
        if (! isset($query['action'])) {
            $url = $url . '&action=details&section=permissions';
        } else {
            if ($query['action'] == 'details') {
                if (! isset($query['section'])) {
                    $url = $url . '&section=permissions';
                } else {
                    // replace any existing section by 'permissions'
                    $url = preg_replace('/section=([^&]+|$)/', 'section=permissions', $url);
                }
            } else {
                $url = preg_replace('/action=show([^\d]|$)/', 'action=details&section=permissions$1', $url);
            }
        }
        return $url;
    }

    /**
     *  Returns the url after modifying it and add information about the concerned service
     *
     * @param String $urlData
     * @param BaseLanguage $language
     *
     * @return String
     */
    public function getRedirectLink($urlData, $language)
    {
        return $this->urlTransform($urlData);
    }

    /**
     * Transform query part of string URL to an hashmap indexed by variables
     *
     * @param String $url The URL
     *
     * @return Array
     */
    public function urlQueryToArray($url)
    {
        $params = [];
        $query  = explode('&', parse_url($url, PHP_URL_QUERY));
        foreach ($query as $tok) {
            [$var, $val]  = explode('=', $tok);
            $params[$var] = urldecode($val);
        }
        return $params;
    }

    /**
     * Returns the docman manager list for given item
     *
     * @param Project $project
     * @param String $url
     *
     * @return Array
     */
    public function extractReceiver($project, $url)
    {
        $query = $this->urlQueryToArray($url);
        if (isset($query['id'])) {
            $id = $query['id'];
        } else {
            if (isset($query['item'])) {
            } else {
                //if no item id is filled, we retieve the root id: the id of "Project documentation"
                if (isset($query['group_id'])) {
                    $itemFactory = $this->_getItemFactoryInstance($project->getId());
                    $res         = $itemFactory->getRoot($project->getId());
                    if ($res !== null) {
                        $row         = $res->toRow();
                        $query['id'] = $row['item_id'];
                    }
                }
            }
        }

        $pm        = $this->_getPermissionManagerInstance($project->getId());
        $adminList = [];
        if (isset($query['id'])) {
            $adminList = $pm->getDocmanManagerUsers($query['id'], $project);
        }
        if (empty($adminList)) {
            $adminList = $pm->getDocmanAdminUsers($project);
        }
        if (empty($adminList)) {
            $adminList = $pm->getProjectAdminUsers($project);
        }
        $receivers = [];
        foreach ($adminList as $mail => $language) {
            $receivers[] = $mail;
        }
        return ['admins' => $receivers, 'status' => true];
    }

    /**
     * Wrapper for Docman_PermissionManager
     *
     * @return Docman_PermissionsManager
     */
    public function _getPermissionManagerInstance($groupId)
    {
        return Docman_PermissionsManager::instance($groupId);
    }

    /**
     * Wrapper for Docman_ItemFactory
     *
     * @return Docman_ItemFactory
     */
    public function _getItemFactoryInstance($groupId)
    {
        return Docman_ItemFactory::instance($groupId);
    }

    protected function getPermissionDeniedMailBody(
        Project $project,
        PFUser $user,
        string $href_approval,
        string $message_to_admin,
        string $link,
    ): string {
        return sprintf(dgettext('tuleap-docman', 'Dear document manager,

%1$s (login: %2$s) requests access to the following document in project "%4$s":
<%3$s>

%1$s wrote a message for you:
%6$s

Someone set permissions on this item, preventing users of having access to this resource.
If you decide to accept the request, please take the appropriate actions to grant him permission and communicate that information to the requester.
Otherwise, please inform the requester (%7$s) that he will not get access to the requested data.
--
%1$s.'), $user->getRealName(), $user->getUserName(), $link, $project->getPublicName(), $href_approval, $message_to_admin, $user->getEmail());
    }

    protected function getPermissionDeniedMailSubject(Project $project, PFUser $user): string
    {
        return sprintf(dgettext('tuleap-docman', '%2$s requests access to a document in "%1$s"'), $project->getPublicName(), $user->getRealName());
    }
}
