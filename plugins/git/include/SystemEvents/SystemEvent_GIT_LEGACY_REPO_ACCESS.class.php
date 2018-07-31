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


class SystemEvent_GIT_LEGACY_REPO_ACCESS  extends SystemEvent {
    const NAME = 'GIT_LEGACY_REPO_ACCESS';

    public function process() {
        $parameters  = $this->getParametersAsArray();
        //repo id
        $repositoryId = '';
        if ( !empty($parameters[0]) ) {
            $repositoryId = $parameters[0];
        }
        else {
            $this->error('Missing argument repository id');
            return false;
        }
        //repo access
        $repositoryAccess = '';
        if ( !empty($parameters[1]) ) {
            $repositoryAccess = $parameters[1];
        }
        else {
            $this->error('Missing argument repository access');
            return false;
        }

        //save
        $repository = new GitRepository();
        $repository->setId($repositoryId);
        try {
            $repository->load();
            $repository->setAccess($repositoryAccess);
            $repository->changeAccess();
        } catch (GitDaoException $e) {
            $this->error( $e->getMessage() );
            return false;
        }
        $this->done();
    }

    public function verbalizeParameters($with_link) {
        return  $this->parameters;
    }


}

?>