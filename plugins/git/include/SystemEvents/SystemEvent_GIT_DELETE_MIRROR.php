<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class SystemEvent_GIT_DELETE_MIRROR extends SystemEvent
{
    public const NAME = 'GIT_DELETE_MIRROR';

    /** @var Git_GitoliteDriver */
    private $gitolite_driver;

    public function injectDependencies(
        Git_GitoliteDriver $gitolite_driver
    ) {
        $this->gitolite_driver  = $gitolite_driver;
    }

    public function process()
    {
        $deletion_is_done = $this->gitolite_driver->deleteMirror(
            $this->getMirrorOldHostnameFromParameters()
        );

        if ($deletion_is_done) {
            $this->done();
            return true;
        }

        $this->error("Something went wrong while deleting mirror");
    }

    private function getMirrorIdFromParameters()
    {
        $parameters    = $this->getParametersAsArray();

        return $parameters[0];
    }

    private function getMirrorOldHostnameFromParameters()
    {
        $parameters    = $this->getParametersAsArray();

        return $parameters[1];
    }

    public function verbalizeParameters($with_link)
    {
        return 'Mirror: ' . $this->getMirrorIdFromParameters();
    }
}
