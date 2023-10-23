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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Git\Driver\GerritHTTPClientFactory;

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

    /**
     * @var GerritHTTPClientFactory
     */
    private $client_factory;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(
        GerritHTTPClientFactory $client_factory,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory,
        \Psr\Log\LoggerInterface $logger,
    ) {
        $this->client_factory  = $client_factory;
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
        $this->logger          = $logger;
    }

    public function createProject(
        Git_RemoteServer_GerritServer $server,
        GitRepository $repository,
        $parent_project_name,
    ) {
        $gerrit_project_name = $this->getGerritProjectName($repository);
        $this->logger->info("Gerrit REST driver: Create project $gerrit_project_name");
        $request  = $this->request_factory->createRequest(
            'PUT',
            $this->getGerritURL($server, '/projects/' . urlencode($gerrit_project_name))
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'description' => "Migration of $gerrit_project_name from Tuleap",
                            'parent' => $parent_project_name,
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            );
        $response = $this->sendRequest($server, $request);

        if ($response->getStatusCode() !== 201) {
            $this->throwGerritException("Gerrit: Project $gerrit_project_name not created", $response);
        }

        $this->logger->info("Gerrit: Project $gerrit_project_name successfully initialized");
        return $gerrit_project_name;
    }

    public function createProjectWithPermissionsOnly(
        Git_RemoteServer_GerritServer $server,
        Project $project,
        $admin_group_name,
    ) {
        $parent_project_name = $project->getUnixName();

        $this->logger->info("Gerrit REST driver: Create parent project $parent_project_name");

        $request = $this->request_factory->createRequest(
            'PUT',
            $this->getGerritURL($server, '/projects/' . urlencode($parent_project_name))
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'description' => "Migration of $parent_project_name from Tuleap",
                            'permissions_only' => true,
                            'owners' => [$admin_group_name],
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            );

        $response = $this->sendRequest($server, $request);

        if ($response->getStatusCode() !== 201) {
            $this->throwGerritException("Gerrit: Permissions-only project $parent_project_name not created: ", $response);
        }

        $this->logger->info("Gerrit: Permissions-only project $parent_project_name successfully initialized");
    }

    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool
    {
        return $this->doesTheProjectExist($server, $project_name);
    }

    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name): bool
    {
        $this->logger->info("Gerrit REST driver: Check if project $project_name already exists");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'GET',
                $this->getGerritURL($server, '/projects/' . urlencode($project_name))
            )
        );

        $response_status_code = $response->getStatusCode();

        if ($response_status_code === 200) {
            $this->logger->info("Gerrit REST driver: project $project_name exists");
            return true;
        }
        if ($response_status_code === 404) {
            $this->logger->info("Gerrit REST driver: project $project_name does not exist");
            return false;
        }

        $this->throwGerritException('Gerrit REST driver: an error occurred while checking existence of project', $response);
    }

    public function ping(Git_RemoteServer_GerritServer $server)
    {
        $this->logger->info("Gerrit REST driver: Check if server is up");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/config/server/version'))
        );

        if ($response->getStatusCode() === 200) {
            $this->logger->info("Gerrit REST driver: server is up!");
            return true;
        }

        $this->logger->info("Gerrit REST driver: server is down");
        return false;
    }

    public function listParentProjects(Git_RemoteServer_GerritServer $server)
    {
        return [];
    }

    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner)
    {
        $this->logger->info("Gerrit REST driver: Create group $group_name");
        if ($owner == $group_name) {
            $owner = self::DEFAULT_GROUP_OWNER;
        }

        $request = $this->request_factory->createRequest(
            'PUT',
            $this->getGerritURL($server, '/groups/' . urlencode($group_name))
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody(
                $this->stream_factory->createStream(json_encode(['owner_id' => $this->getGroupUUID($server, $owner)], JSON_THROW_ON_ERROR))
            );

        $response = $this->sendRequest(
            $server,
            $request
        );

        $response_status_code = $response->getStatusCode();

        if ($response_status_code === 201) {
            $this->logger->info("Gerrit REST driver: Group $group_name successfully created");
            return;
        }
        if ($response_status_code === 409) {
            $this->logger->info("Gerrit REST driver: Group $group_name already exists");
            return;
        }

        $this->throwGerritException("Gerrit REST driver: Unable to create group $group_name", $response);
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
        $this->logger->info("Gerrit REST driver: Get all groups");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/groups/'))
        );

        if ($response->getStatusCode() !== 200) {
            $this->throwGerritException('Gerrit REST driver: an error occurred while fetching all groups', $response);
        }

        $groups = [];
        foreach ($this->decodeGerritResponse($response->getBody()->getContents()) as $name => $group) {
            $groups[$name] = $group['id'];
        }
        return $groups;
    }

    public function getGerritProjectName(GitRepository $repository)
    {
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        return $name_builder->getGerritProjectName($repository);
    }

    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name)
    {
        $this->logger->info("Gerrit REST driver: Add user " . $user->getSSHUserName() . " in group $group_name");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'PUT',
                $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/members/' . urlencode($user->getSSHUserName()))
            )
        );

        $status_code = $response->getStatusCode();
        if ($status_code !== 200 && $status_code !== 201) {
            $this->throwGerritException("Gerrit REST driver: Cannot add user", $response);
        }

        $this->logger->info("Gerrit REST driver: User successfully added");
    }

    public function removeUserFromGroup(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $group_name,
    ) {
        $this->logger->info("Gerrit REST driver: Remove user " . $user->getSSHUserName() . " from group $group_name");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'DELETE',
                $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/members/' . urlencode($user->getSSHUserName()))
            )
        );

        if ($response->getStatusCode() !== 204) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove user", $response);
        }

        $this->logger->info("Gerrit REST driver: User successfully removed");
    }

    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $existing_members = $this->getAllMembers($server, $group_name);
        if (! $existing_members) {
            return;
        }

        $this->logger->info("Gerrit REST driver: Remove all group members from $group_name");
        $request = $this->request_factory->createRequest(
            'POST',
            $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/members.delete')
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody(
                $this->stream_factory->createStream(json_encode(['members' => $existing_members], JSON_THROW_ON_ERROR))
            );

        $response = $this->sendRequest(
            $server,
            $request
        );

        if ($response->getStatusCode() !== 204) {
            $this->throwGerritException('Gerrit REST driver: An error occurred while removing all groups members', $response);
        }
    }

    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name)
    {
        $this->logger->info("Gerrit REST driver: Add included group $included_group_name in group $group_name");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'PUT',
                $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/groups/' . urlencode($included_group_name))
            )
        );

        $status_code = $response->getStatusCode();
        if ($status_code !== 200 && $status_code !== 201) {
            $this->throwGerritException("Gerrit REST driver: Cannot include group", $response);
        }

        $this->logger->info("Gerrit REST driver: Group successfully included");
    }

    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $exiting_groups = $this->getAllIncludedGroups($server, $group_name);
        if (! $exiting_groups) {
            return true;
        }

        $this->logger->info("Gerrit REST driver: Remove all included groups from group $group_name");

        $request = $this->request_factory->createRequest(
            'POST',
            $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/groups.delete')
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody(
                $this->stream_factory->createStream(json_encode(['groups' => $exiting_groups], JSON_THROW_ON_ERROR))
            );

        $response = $this->sendRequest($server, $request);

        if ($response->getStatusCode() !== 204) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove included group", $response);
        }

        $this->logger->info("Gerrit REST driver: included groups successfully removed");
    }

    public function flushGerritCacheAccounts($server)
    {
        return;
    }

    public function addSSHKeyToAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        string $ssh_key,
    ): void {
        $this->logger->info("Gerrit REST driver: Add ssh key for user " . $user->getSSHUserName());
        $response             = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'POST',
                $this->getGerritURL($server, '/accounts/' . urlencode($user->getSSHUserName()) . '/sshkeys')
            )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_TEXT)
                ->withBody($this->stream_factory->createStream($ssh_key))
        );
        $response_status_code = $response->getStatusCode();
        if ($response_status_code !== 200 && $response_status_code !== 201) {
            $this->throwGerritException("Gerrit REST driver: Cannot add ssh key", $response);
        }
        $this->logger->info("Gerrit REST driver: ssh key successfully added");
    }

    public function removeSSHKeyFromAccount(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $ssh_key,
    ) {
        $this->logger->info("Gerrit REST driver: Remove ssh key for user " . $user->getSSHUserName());

        $ssh_keys           = $this->getAllSSHKeysForUser($server, $user);
        $gerrit_ssh_key_ids = $this->getUserSSHKeyId($ssh_keys, $ssh_key);

        $this->logger->info("Gerrit REST driver: Found this ssh key " . count($gerrit_ssh_key_ids) . " time(s)");

        foreach ($gerrit_ssh_key_ids as $gerrit_key_id) {
            $this->actionRemoveSSHKey($server, $user, $gerrit_key_id);
        }
    }

    /**
     *
     *
     * @return array
     */
    private function getAllSSHKeysForUser(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
    ) {
        $this->logger->info("Gerrit REST driver: Get all ssh keys for user " . $user->getSSHUserName());
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'GET',
                $this->getGerritURL($server, '/accounts/' . urlencode($user->getSSHUserName()) . '/sshkeys')
            )
        );

        if ($response->getStatusCode() === 404) {
            return [];
        }

        if ($response->getStatusCode() === 200) {
            $this->logger->info("Gerrit REST driver: Successfully got all ssh keys for user");
            return $this->decodeGerritResponse($response->getBody()->getContents());
        }

        $this->throwGerritException("Gerrit REST driver: Cannot get ssh keys for user", $response);
    }

    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name)
    {
        $this->logger->info("Gerrit REST driver: Set project $parent_project_name as parent of $project_name");
        $request = $this->request_factory->createRequest(
            'PUT',
            $this->getGerritURL($server, '/projects/' . urlencode($project_name) . '/parent')
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody($this->stream_factory->createStream(json_encode(['parent' => $parent_project_name], JSON_THROW_ON_ERROR)));

        $response = $this->sendRequest($server, $request);

        if ($response->getStatusCode() !== 200) {
            $this->throwGerritException("Gerrit REST driver: Cannot set parent project", $response);
        }

        $this->logger->info("Gerrit REST driver: parent project successfully added");
    }

    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name)
    {
        $this->setProjectInheritance($server, $project_name, self::DEFAULT_PARENT_PROJECT);
    }

    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server)
    {
        $this->logger->info("Gerrit REST driver: Check if delete plugin is activated");

        try {
            $response = $this->sendRequest(
                $server,
                $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/plugins/'))
            );
        } catch (Git_Driver_Gerrit_Exception $e) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $plugins = $this->decodeGerritResponse($response->getBody()->getContents());

        $activated = isset($plugins['deleteproject']) || isset($plugins['delete-project']);
        $this->logger->info("Gerrit REST driver: delete plugin is activated : $activated");

        return $activated;
    }

    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        $this->logger->info("Gerrit REST driver: Delete project $gerrit_project_full_name");
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'POST',
                $this->getGerritURL($server, '/projects/' . urlencode($gerrit_project_full_name) . '/delete-project~delete')
            )
        );

        $response_status_code = $response->getStatusCode();

        if ($response_status_code === 404) {
            $this->logger->info('Gerrit REST driver: Project does not exist maybe it has already been deleted?');
            return;
        }

        if ($response_status_code >= 400 && $response_status_code < 500) {
            $this->logger->error('Gerrit REST driver: Cannot delete project ' . $gerrit_project_full_name . ': There are open changes');
            throw new ProjectDeletionException(
                dgettext('tuleap-git', 'There are open changes for this project.')
            );
        }

        if ($response_status_code === 204) {
            $this->logger->info("Gerrit REST driver: Project successfully deleted");
            return;
        }

        $this->throwGerritException("Gerrit REST driver: Cannot delete project $gerrit_project_full_name", $response);
    }

    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name)
    {
        $this->logger->info("Gerrit REST driver: Set $gerrit_project_full_name Read-Only");

        $request = $this->request_factory->createRequest(
            'PUT',
            $this->getGerritURL($server, '/projects/' . urlencode($gerrit_project_full_name) . '/config')
        )->withHeader(self::HEADER_CONTENT_TYPE, self::MIME_JSON)
            ->withBody($this->stream_factory->createStream(json_encode(['state' => 'READ_ONLY'], JSON_THROW_ON_ERROR)));

        $response = $this->sendRequest($server, $request);

        if ($response->getStatusCode() !== 200) {
            $this->throwGerritException('Gerrit REST driver: An error occurred while setting project read-only', $response);
        }

        $this->logger->info("Gerrit REST driver: Project successfully set Read-Only");
    }

    private function getAllMembers(
        Git_RemoteServer_GerritServer $server,
        $group_name,
    ) {
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/members'))
        );

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $members = $this->decodeGerritResponse($response->getBody()->getContents());
        return array_map(
            static function ($member) {
                return $member['username'];
            },
            $members
        );
    }

    private function getAllIncludedGroups(
        Git_RemoteServer_GerritServer $server,
        $group_name,
    ) {
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/groups/' . urlencode($group_name) . '/groups'))
        );

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $members = $this->decodeGerritResponse($response->getBody()->getContents());

        return array_map(
            function ($member) {
                return $member['name'];
            },
            $members
        );
    }

    private function getGroupInfoFromGerrit(Git_RemoteServer_GerritServer $server, $group_name)
    {
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('GET', $this->getGerritURL($server, '/groups/' . urlencode($group_name)))
        );

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        return $this->decodeGerritResponse($response->getBody()->getContents());
    }

    private function getUserSSHKeyId(array $ssh_keys, $expected_ssh_key)
    {
        $matching_keys = [];

        foreach ($ssh_keys as $ssh_key_info) {
            if ($ssh_key_info['encoded_key'] === $this->getKeyPartFromSSHKey($expected_ssh_key)) {
                $gerrit_ssh_key_id = $ssh_key_info['seq'];
                $this->logger->info("Gerrit REST driver: Key found ($gerrit_ssh_key_id)");
                $matching_keys[] = $gerrit_ssh_key_id;
            }
        }

        return $matching_keys;
    }

    private function actionRemoveSSHKey(
        Git_RemoteServer_GerritServer $server,
        Git_Driver_Gerrit_User $user,
        $gerrit_key_id,
    ) {
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest(
                'DELETE',
                $this->getGerritURL($server, '/accounts/' . urlencode($user->getSSHUserName()) . '/sshkeys/' . urlencode($gerrit_key_id))
            )
        );
        if ($response->getStatusCode() !== 204) {
            $this->throwGerritException("Gerrit REST driver: Cannot remove ssh key ($gerrit_key_id)", $response);
        }

        $this->logger->info("Gerrit REST driver: Successfully deleted ssh key ($gerrit_key_id)");
    }

    private function getKeyPartFromSSHKey($expected_ssh_key)
    {
        $key_parts = explode(' ', $expected_ssh_key);

        return $key_parts[1];
    }

    /**
     * @psalm-return never-return
     */
    private function throwGerritException(string $base_message, ResponseInterface $response): void
    {
        $message = $base_message . sprintf(': HTTP status %d %s', $response->getStatusCode(), $response->getReasonPhrase());
        $this->logger->error($message);
        throw new Git_Driver_Gerrit_Exception($message);
    }

    /**
     * Strip magic prefix
     *
     * @see https://gerrit-documentation.storage.googleapis.com/Documentation/2.8.3/rest-api.html#output
     *
     * @param string $gerrit_response
     * @return mixed
     */
    private function decodeGerritResponse($gerrit_response)
    {
        return json_decode(substr($gerrit_response, 5), true, 512, JSON_THROW_ON_ERROR);
    }

    private function getGerritURL(Git_RemoteServer_GerritServer $server, $url)
    {
        $full_url = $server->getBaseUrl() . '/a' . $url;

        return $full_url;
    }

    private function sendRequest(Git_RemoteServer_GerritServer $server, RequestInterface $request): ResponseInterface
    {
        $client = $this->client_factory->buildHTTPClient($server);

        try {
            return $client->sendRequest($request);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            $message = sprintf('Unable to send HTTP query %s %s', $request->getMethod(), (string) $request->getUri());
            $this->logger->error($message, ['exception' => $e]);
            throw new Git_Driver_Gerrit_Exception('Gerrit REST driver: ' . $message);
        }
    }

    public function setUserAccountInactive(
        Git_RemoteServer_GerritServer $server,
        PFUser $user,
    ) {
        $response = $this->sendRequest(
            $server,
            $this->request_factory->createRequest('DELETE', $this->getGerritURL($server, '/accounts/' . urlencode($user->getUserName()) . '/active'))
        );

        $response_status_code = $response->getStatusCode();

        if ($response_status_code === 204) {
            $this->logger->info(sprintf(dgettext('tuleap-git', 'Gerrit: (%1$s) %2$s has been successfully suspended from gerrit server %3$s'), $user->getId(), $user->getUserName(), (string) $server));
            return;
        }

        if ($response_status_code === 409) {
            $this->logger->info(sprintf('Gerrit: (%s) %s was already suspended on gerrit server %s', $user->getId(), $user->getUserName(), (string) $server));
            return;
        }

        $this->logger->error(sprintf(dgettext('tuleap-git', 'Error when suspending (%1$s) %2$s from gerrit server %3$s, more details : %4$s'), $user->getId(), $user->getUserName(), (string) $server, $response_status_code . ' ' . $response->getReasonPhrase()));
    }
}
