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


class SystemEvent_PHPWIKI_SWITCH_TO_PLUGIN extends SystemEvent {
    const NAME = 'PHPWIKI_SWITCH_TO_PLUGIN';

    private $phpwiki_migrator;

    public function injectDependencies(PHPWikiMigratorDao $phpwiki_migrator) {
        $this->phpwiki_migrator = $phpwiki_migrator;
    }

    public function process() {
        try {
            $this->phpwiki_migrator->importWikiIntoPlugin($this->getProjectIdFromParameters());
            $this->done();
        } catch (DataAccessException $e) {
            $this->error($e->getMessage());
        }
    }

    private function getProjectIdFromParameters() {
        $parameters = $this->getParametersAsArray();
        return intval($parameters[0]);
    }

    public function verbalizeParameters($with_link) {
        return 'Project: '. $this->getProjectIdFromParameters();
    }
}