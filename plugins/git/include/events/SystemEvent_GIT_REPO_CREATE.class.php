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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */
require_once('common/system_event/SystemEvent.class.php');
require_once('common/backend/Backend.class.php');
require_once('common/user/UserManager.class.php');

/**
 * Description of SystemEvent_DVCS_REPO_CREATE
 *
 * @author gstorchi
 */
class SystemEvent_GIT_REPO_CREATE extends SystemEvent {

    public function process() {

        global $sys_allow_restricted_users;

        $parameters  = $this->getParametersAsArray();
        $project     = null;
        if ( !empty($parameters[0]) ) {
            $project = $this->getProject( $parameters[0] );
        }
        else {
            $this->error('Missing argument project id');
            return false;
        }

        $repositoryName = '';
        if ( !empty($parameters[1]) ) {
            $repositoryName = $parameters[1];
        }
        else {
            $this->error('Missing argument repository name');
            return false;
        }

        $userId = 0;
        if ( !empty($parameters[2]) ) {
            $userId = $parameters[2];
        }
        else {
            $this->error('Missing argument user id');
            return false;
        }

        try {
            $repository = new GitRepository();
            $repository->setBackend(Backend::instance('Git','GitBackend'));
            $repository->setDescription(GitRepository::DEFAULT_DESCRIPTION);
            //default access is private when restricted users are allowed
            if ( $sys_allow_restricted_users == 1) {
                $repository->setAccess( GitRepository::PRIVATE_ACCESS );
            } else {
                $repository->setAccess( GitRepository::PUBLIC_ACCESS );
            }
            $user = null;
            if ( !empty($userId) ) {
                $user = UserManager::instance()->getUserById($userId);
            }
            if ( !empty($user) ) {
                $repository->setCreator($user);
            }
            $repository->setProject($project);
            $repository->setName($repositoryName);            
            $repository->create();
            $this->done();
        }
        catch (GitDaoException $e) {
            $this->error( $e->getMessage() );
            return false;
        }
        catch (GitDriverException $e) {
            $this->error( $e->getMessage() );
            return false;
        }
        catch (GitBackendException $e) {
            $this->error( $e->getMessage() );
            return false;
        }
        catch (Exception $e) {
            $this->error( $e->getMessage() );
            return false;
        }

    }

    public function verbalizeParameters($with_link) {

        return  $this->parameters;
    }

}

?>
