<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I know how to speak to a Gerrit 2.8+ remote server
 */
class Git_Driver_GerritREST implements Git_Driver_Gerrit
{


    /**
     * When one create a group when no owners, set Administrators as default
     * @see: https://groups.google.com/d/msg/repo-discuss/kVDkj7Ds970/xzLP1WQI2BAJ
     */
    public const DEFAULT_GROUP_OWNER = 'Administrators';

    public const HEADER_CONTENT_TYPE = 'Content-type';
    public const MIME_JSON           = 'application/json;charset=UTF-8';
    public const MIME_TEXT           = 'plain/text';

    /** @var Logger */
    private $logger;

    /** @var Guzzle\Http\Client */
    private $guzzle_client;

    /** @var String */
    private $auth_type;

    public function __construct(
        $guzzle_client,
        Logger $logger,
        $auth_type
    ) {
        $this->guzzle_client = $guzzle_client;
        $this->logger        = $logger;
        $this->auth_type     = $auth_type;
    }

    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name
    ) {
        $gerrit_project_name = $this->getGerritProjectName($repository);
        try {
            $this->logger->info("Gerrit REST driver: Create project $gerrit_project_name");

            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/projects/'. urlencode($gerrit_project_name)),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'description' => "Migration of $gerrit_project_name from Tuleap",
                            'parent'      => $parent_project_name
                        )
                    )
                )
            );

            $this->logger->info("Gerrit: Project $gerrit_project_name successfully initialized");
            return $gerrit_project_name;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit: Project $gerrit_project_name not created: " . $exception->getMessage());
        }
    }

    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name
    ) {
        try {
            $parent_project_name = $project->getUnixName();

            $this->logger->info("Gerrit REST driver: Create parent project $parent_project_name");

            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/projects/'. urlencode($parent_project_name)),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'description'      => "Migration of $parent_project_name from Tuleap",
                            'permissions_only' => true,
                            'owners'           => array(
                                $admin_group_name
                            )
                        )
                    )
                )
            );

            $this->logger->info("Gerrit: Permissions-only project $parent_project_name successfully initialized");
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit: Permissions-only project $parent_project_name not created: " . $exception->getMessage());
        }
    }

    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name)
    {
        return $this->doesTheProjectExist($server, $project_name);
    }

    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Check if project $project_name already exists");
            $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/projects/'. urlencode($project_name)),
                    $this->getRequestOptions()
                )
            );

            $this->logger->info("Gerrit REST driver: project $project_name exists");

            return true;
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            $this->logger->info("Gerrit REST driver: project $project_name does not exist");
            return false;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: an error occured while checking existance of project (".$exception->getMessage().")");
        }
    }

    public function ping(Git_RemoteServer_GerritServer $server)
    {
        try {
            $this->logger->info("Gerrit REST driver: Check if server is up");
            $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/config/server/version'),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info("Gerrit REST driver: server is up!");
            return true;
        } catch (Exception $exception) {
            $this->logger->info("Gerrit REST driver: server is down");
            return false;
        }
    }

    public function listParentProjects(Git_RemoteServer_GerritServer $server)
    {
        return;
    }

    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner)
    {
        if ($this->doesTheGroupExist($server, $group_name)) {
            return;
        }

        try {
            $this->logger->info("Gerrit REST driver: Create group $group_name");

            if ($owner == $group_name) {
                $owner = self::DEFAULT_GROUP_OWNER;
            }

            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name)),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'owner_id' => $this->getGroupUUID($server, $owner)
                        )
                    )
                )
            );

            $this->logger->info("Gerrit REST driver: Group $group_name successfully created");
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Unable to create group $group_name: ". $exception->getMessage());
        }
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name)
    {
        $group_info = $this->getGroupInfoFromGerrit($server, $group_full_name);
        if (! $group_info) {
            return;
        }

        return $group_info['id'];
    }

    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name)
    {
        $group_info = $this->getGroupInfoFromGerrit($server, $group_full_name);
        if (! $group_info) {
            return;
        }

        return $group_info['group_id'];
    }

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $this->logger->info("Gerrit REST driver: Check if the group $group_name exists");

        return $this->getGroupInfoFromGerrit($server, $group_name) !== false;
    }

    public function listGroups(Git_RemoteServer_GerritServer $server)
    {
        return;
    }

    public function getAllGroups(Git_RemoteServer_GerritServer $server)
    {
        try {
            $this->logger->info("Gerrit REST driver: Get all groups");
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/groups/'),
                    $this->getRequestOptions()
                )
            );

            $groups = array();
            foreach ($this->decodeGerritResponse($response->getBody(true)) as $name => $group) {
                $groups[$name] = $group['id'];
            }

            return $groups;
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            return array();
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: an error occured while fetching all groups (".$exception->getMessage().")");
        }
    }

    public function getGerritProjectName(GitRepository $repository)
    {
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        return $name_builder->getGerritProjectName($repository);
    }

    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Add user " . $user->getSSHUserName() . " in group $group_name");

            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/members/'. urlencode($user->getSSHUserName())),
                    $this->getRequestOptions()
                )
            );

            $this->logger->info("Gerrit REST driver: User successfully added");
            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot add user: " . $exception->getMessage());
        }
    }

    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name
    ) {
        try {
            $this->logger->info("Gerrit REST driver: Remove user " . $user->getSSHUserName() . " from group $group_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->delete(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/members/'. urlencode($user->getSSHUserName())),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info("Gerrit REST driver: User successfully removed");
            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove user: " . $exception->getMessage());
        }
    }

    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $exiting_members = $this->getAllMembers($server, $group_name);
        if (! $exiting_members) {
            return true;
        }

        try {
            $this->logger->info("Gerrit REST driver: Remove all group members from $group_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->post(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/members.delete'),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'members' => $exiting_members
                        )
                    )
                )
            );
            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: An error occured while removing all groups members: " . $exception->getMessage());
        }
    }

    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Add included group $included_group_name in group $group_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/groups/'. urlencode($included_group_name)),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info("Gerrit REST driver: Group successfully included");
            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot include group: ". $exception->getMessage());
        }
    }

    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $exiting_groups = $this->getAllIncludedGroups($server, $group_name);
        if (! $exiting_groups) {
            return true;
        }

        try {
            $this->logger->info("Gerrit REST driver: Remove all included groups from group $group_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->post(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/groups.delete'),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'groups' => $exiting_groups
                        )
                    )
                )
            );
            $this->logger->info("Gerrit REST driver: included groups successfully removed");

            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove included group: ". $exception->getMessage());
        }
    }

    public function flushGerritCacheAccounts($server)
    {
        return;
    }

    public function addSSHKeyToAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key
    ) {
        try {
            $this->logger->info("Gerrit REST driver: Add ssh key for user ". $user->getSSHUserName());
            $this->sendRequest(
                $server,
                $this->guzzle_client->post(
                    $this->getGerritURL($server, '/accounts/'. urlencode($user->getSSHUserName()) .'/sshkeys'),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_TEXT)),
                    $this->escapeSSHKey($ssh_key)
                )
            );
            $this->logger->info("Gerrit REST driver: ssh key successfully added");

            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot add ssh key: ". $exception->getMessage());
        }
    }

    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key
    ) {
        $this->logger->info("Gerrit REST driver: Remove ssh key for user ". $user->getSSHUserName());

        $ssh_keys           = $this->getAllSSHKeysForUser($server, $user);
        $gerrit_ssh_key_ids = $this->getUserSSHKeyId($ssh_keys, $ssh_key);

        $this->logger->info("Gerrit REST driver: Found this ssh key ". count($gerrit_ssh_key_ids). " time(s)");

        foreach ($gerrit_ssh_key_ids as $gerrit_key_id) {
            $this->actionRemoveSSHKey($server, $user, $gerrit_key_id);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param Git_Driver_Gerrit_User $user
     *
     * @return array
     */
    private function getAllSSHKeysForUser(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user
    ) {
        try {
            $this->logger->info("Gerrit REST driver: Get all ssh keys for user ". $user->getSSHUserName());
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/accounts/'. urlencode($user->getSSHUserName()) .'/sshkeys'),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info("Gerrit REST driver: Successfully got all ssh keys for user");

            return $this->decodeGerritResponse($response->getBody(true));
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            return array();
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot get ssh keys for user: ". $exception->getMessage());
        }
    }

    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Set project $parent_project_name as parent of $project_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/projects/'. urlencode($project_name) .'/parent'),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'parent' => $parent_project_name
                        )
                    )
                )
            );
            $this->logger->info("Gerrit REST driver: parent project successfully added");

            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot set parent project: ". $exception->getMessage());
        }
    }

    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name)
    {
        return $this->setProjectInheritance($server, $project_name, self::DEFAULT_PARENT_PROJECT);
    }

    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server)
    {
        try {
            $this->logger->info("Gerrit REST driver: Check if delete plugin is activated");
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/plugins/'),
                    $this->getRequestOptions()
                )
            );
            $plugins = $this->decodeGerritResponse($response->getBody(true));

            $activated = isset($plugins['deleteproject']) || isset($plugins['delete-project']);

            $this->logger->info("Gerrit REST driver: delete plugin is activated : $activated");

            return $activated;
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            return false;
        } catch (Guzzle\Http\Exception\CurlException $exception) {
            return false;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: An error occured while checking if deleted plugins is available: ". $exception->getMessage());
        }
    }

    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Delete project $gerrit_project_full_name");
            $this->sendRequest(
                $server,
                $this->guzzle_client->delete(
                    $this->getGerritURL($server, '/projects/'. urlencode($gerrit_project_full_name)),
                    $this->getRequestOptions()
                )
            );

            $this->logger->info("Gerrit REST driver: Project successfully deleted");
            return true;
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            $this->logger->error('Gerrit REST driver: Cannot delete project ' . $gerrit_project_full_name . ': There are open changes');
            throw new ProjectDeletionException(
                dgettext('tuleap-git', 'There are open changes for this project.')
            );
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot delete project $gerrit_project_full_name. (".$exception->getMessage().")");
        }
    }

    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        try {
            $this->logger->info("Gerrit REST driver: Set $gerrit_project_full_name Read-Only");
            $this->sendRequest(
                $server,
                $this->guzzle_client->put(
                    $this->getGerritURL($server, '/projects/'. urlencode($gerrit_project_full_name) .'/config'),
                    $this->getRequestOptions(array(self::HEADER_CONTENT_TYPE => self::MIME_JSON)),
                    json_encode(
                        array(
                            'state' => 'READ_ONLY'
                        )
                    )
                )
            );
            $this->logger->info("Gerrit REST driver: Project successfully set Read-Only");
            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: An error occured while setting project read-only: ".$exception->getMessage());
        }
    }

    private function getAllMembers(
        Git_RemoteServer_GerritServer $server,
        $group_name
    ) {
        try {
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/members'),
                    $this->getRequestOptions()
                )
            );
            $members = $this->decodeGerritResponse($response->getBody(true));

            return array_map(array($this, 'pluckUsername'), $members);
        } catch (Exception $exception) {
            return array();
        }
    }

    private function getAllIncludedGroups(
        Git_RemoteServer_GerritServer $server,
        $group_name
    ) {
        try {
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name) .'/groups'),
                    $this->getRequestOptions()
                )
            );
            $members = $this->decodeGerritResponse($response->getBody(true));

            return array_map(array($this, 'pluckGroupname'), $members);
        } catch (Exception $exception) {
            return array();
        }
    }

    private function getGroupInfoFromGerrit(Git_RemoteServer_GerritServer $server, $group_name)
    {
        try {
            $response = $this->sendRequest(
                $server,
                $this->guzzle_client->get(
                    $this->getGerritURL($server, '/groups/'. urlencode($group_name)),
                    $this->getRequestOptions()
                )
            );

            return $this->decodeGerritResponse($response->getBody(true));
        } catch (Exception $exception) {
            return false;
        }
    }

    private function getUserSSHKeyId(array $ssh_keys, $expected_ssh_key)
    {
        $matching_keys = array();

        foreach ($ssh_keys as $ssh_key_info) {
            if ($ssh_key_info['encoded_key'] === $this->getKeyPartFromSSHKey($expected_ssh_key)) {
                $gerrit_ssh_key_id = $ssh_key_info['seq'];
                $this->logger->info("Gerrit REST driver: Key found ($gerrit_ssh_key_id)");
                $matching_keys[] = $gerrit_ssh_key_id ;
            }
        }

        return $matching_keys;
    }

    private function actionRemoveSSHKey(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $gerrit_key_id
    ) {
        try {
            /** @psalm-suppress UndefinedDocblockClass Guzzle client class is not autoloaded by Composer */
            $this->sendRequest(
                $server,
                $this->guzzle_client->delete(
                    $this->getGerritURL($server, '/accounts/'. urlencode($user->getSSHUserName()) .'/sshkeys/'. urlencode($gerrit_key_id)),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info("Gerrit REST driver: Successfully deleted ssh key ($gerrit_key_id)");

            return true;
        } catch (Exception $exception) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove ssh key ($gerrit_key_id): ". $exception->getMessage());
        }
    }

    private function escapeSSHKey($ssh_key)
    {
        return str_replace('=', '\u003d', $ssh_key);
    }

    private function getKeyPartFromSSHKey($expected_ssh_key)
    {
        $key_parts = explode(' ', $expected_ssh_key);

        return $key_parts[1];
    }

    private function throwGerritException($message)
    {
        $this->logger->error($message);
        throw new Git_Driver_Gerrit_Exception($message);
    }


    private function pluckUsername($member)
    {
        return $member['username'];
    }

    private function pluckGroupname($member)
    {
        return $member['name'];
    }

    /**
     * Strip magic prefix
     *
     * @see https://gerrit-documentation.storage.googleapis.com/Documentation/2.8.3/rest-api.html#output
     *
     * @param string $gerrit_response
     * @return string
     */
    private function decodeGerritResponse($gerrit_response)
    {
        return json_decode(substr($gerrit_response, 5), true);
    }

    private function getGerritURL(Git_RemoteServer_GerritServer $server, $url)
    {
        $full_url = $server->getBaseUrl().'/a'. $url;

        return $full_url;
    }

    /**
     * @param Git_RemoteServer_GerritServer        $server
     * @param Guzzle\Http\Message\RequestInterface $request
     *
     * @return Guzzle\Http\Message\Response
     */
    private function sendRequest(Git_RemoteServer_GerritServer $server, Guzzle\Http\Message\RequestInterface $request)
    {
        $request->setAuth($server->getLogin(), $server->getHTTPPassword(), $this->auth_type);
        return $request->send();
    }

    private function getRequestOptions(array $custom_options = array())
    {
        return $custom_options + array(
            'verify' => false,
        );
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @param PFUser $user
     *
     * @return Guzzle\Http\Message\Response
     */
    public function setUserAccountInactive(
        Git_RemoteServer_GerritServer $server,
        PFUser $user
    ) {
        try {
            /** @psalm-suppress UndefinedDocblockClass Guzzle client class is not autoloaded by Composer */
            $this->sendRequest(
                $server,
                $this->guzzle_client->delete(
                    $this->getGerritURL($server, '/accounts/'. urlencode($user->getUserName()) .'/active'),
                    $this->getRequestOptions()
                )
            );
            $this->logger->info(sprintf(dgettext('tuleap-git', 'Gerrit: (%1$s) %2$s has been successfully suspended from gerrit server %3$s'), $user->getId(), $user->getUserName(), $server));

            return true;
        } catch (Exception $exception) {
            $this->logger->error(sprintf(dgettext('tuleap-git', 'Error when suspending (%1$s) %2$s from gerrit server %3$s, more details : %4$s'), $user->getId(), $user->getUserName(), $server, $exception->getMessage()));
        }
    }
}
