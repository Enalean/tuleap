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
/**
 * Description of SystemEvent_GIT_REPO_DELETE
 *
 * @author gstorchi
 */
class SystemEvent_GIT_REPO_DELETE extends SystemEvent {
    //put your code here

    public function process() {
        $parameters   = $this->getParametersAsArray();
        //project id
        $projectId    = 0;
        if ( !empty($parameters[0]) ) {
            $projectId = (int) $parameters[0];
        } else {
            $this->error('Missing argument project id');
            return false;
        }
        //repo id
        $repositoryId = 0;
        if ( !empty($parameters[1]) ) {
            $repositoryId = (int) $parameters[1];
        } else {
            $this->error('Missing argument repository id');
            return false;
        }
        
        $repository = $this->getRepositoryFactory()->getDeletedRepository($repositoryId);
        if ($repository->getProjectId() != $projectId) {
            $this->error('Bad project id');
            return false;
        }

        return $this->deleteRepo($repository, $projectId, $parameters);
    }

    private function deleteRepo(GitRepository $repository) {
        try {
            $repository->delete();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
        $this->done();
        return true;
    }

    public function verbalizeParameters($with_link) {
        return $this->parameters;
    }

    protected function getRepositoryFactory() {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    }

}

?>
