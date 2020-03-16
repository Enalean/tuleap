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

class SystemEvent_GIT_DUMP_ALL_MIRRORED_REPOSITORIES extends SystemEvent
{
    public const NAME = 'GIT_DUMP_ALL_MIRRORED_REPOSITORIES';

    /** @var Git_GitoliteDriver */
    private $gitolite_driver;

    public function injectDependencies(
        Git_GitoliteDriver $gitolite_driver
    ) {
        $this->gitolite_driver  = $gitolite_driver;
    }

    public function process()
    {
        $dump_is_done = $this->gitolite_driver->dumpAllMirroredRepositories();

        if ($dump_is_done) {
            $this->done();
            return true;
        }

        $this->error('Something went wrong while dumping gitolite configuration.');
    }

    public function verbalizeParameters($with_link)
    {
        return '';
    }
}
