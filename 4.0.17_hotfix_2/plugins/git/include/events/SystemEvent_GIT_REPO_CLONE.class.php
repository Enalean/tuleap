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
require_once(dirname(__FILE__).'/../GitRepository.class.php');
require_once('common/backend/Backend.class.php');
require_once('common/user/UserManager.class.php');

/**
 * Description of SystemEvent_DVCS_REPO_CREATE
 *
 * @author gstorchi
 */
class SystemEvent_GIT_REPO_CLONE extends SystemEvent {

    public function process() {        

        $parameters  = $this->getParametersAsArray();
        //print_r($parameters);
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

        $parentId     = '';
        if ( !empty($parameters[2]) ) {
            $parentId = $parameters[2];
        }
        else {
            $this->error('Missing argument parent id');
            return false;
        }

        $userId = 0;
        if ( !empty($parameters[3]) ) {
            $userId = $parameters[3];
        }
        else {
            $this->error('Missing argument user id');
            return false;
        }

        try {
            $repository = new GitRepository();            
            $repository->setProject($project);
            $repository->setId($parentId);
            //load before setting creator            
            $repository->load();
            
            $user = null;
            if ( !empty($userId) ) {
                $user = UserManager::instance()->getUserById($userId);
            }
            if ( !empty($user) ) {
                $repository->setCreator($user);
            }            
            $repository->fork($repositoryName);
            $this->done();
        }
        catch (GitDaoException $e1) {
            $this->error( $e1->getMessage() );
            return false;
        }
        catch (GitDriverException $e2) {
            $this->error( $e2->getMessage() );
            return false;
        }
        catch (GitBackendException $e3) {
            $this->error( $e3->getMessage() );
            return false;
        }
        catch (Exception $e4) {
            $this->error( $e4->getMessage() );
            return false;
        }

    }

    public function verbalizeParameters($with_link) {

        return  $this->parameters;
    }

}

?>
