<?php
/**
 * Copyright Enalean (c) 2104 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class SystemEvent_TRACKER_V3_MIGRATION extends SystemEvent
{
    public const NAME = 'TRACKER_V3_MIGRATION';

    /** @var  Tracker_Migration_MigrationManager */
    private $migration_manager;

    public function injectDependencies(Tracker_Migration_MigrationManager $migration_manager)
    {
        $this->migration_manager = $migration_manager;
    }

    #[\Override]
    public function process()
    {
        $parameters = $this->getParametersAsArray();

        if ($this->parametersAreMissing($parameters)) {
            $this->error('It appears that  some parameters are missing. The parameters of this System Event should be username, project_id, tv3_id, tracker_name, tracker_description, tracker_shortname, keep_original_artifact_ids');
            return false;
        }

        $tracker_shortname   = (string) $parameters[0];
        $tracker_name        = (string) $parameters[1];
        $tracker_description = (string) $parameters[2];
        $username            = (string) $parameters[3];
        $project_id          = (int) $parameters[4];
        $tv3_id              = (int) $parameters[5];
        $keep_original_ids   = (bool) $parameters[6];

        try {
            $this->migration_manager->migrate(
                $username,
                $project_id,
                $tv3_id,
                $tracker_name,
                $tracker_description,
                $tracker_shortname,
                $keep_original_ids
            );

            $this->done();
        } catch (Exception $exception) {
            $this->error(
                $exception->getMessage()
            );
        }
    }

    private function parametersAreMissing($parameters)
    {
        if (! isset($parameters[0])) {
            $this->error('Missing argument: shortname');
            return true;
        }

        if (! isset($parameters[1])) {
            $this->error('Missing argument: name');
            return true;
        }

        if (! isset($parameters[2])) {
            $this->error('Missing argument: description');
            return true;
        }

        if (! isset($parameters[3])) {
            $this->error('Missing argument: User');
            return true;
        }

        if (! isset($parameters[4])) {
            $this->error('Missing argument: project id');
            return true;
        }

        if (! isset($parameters[5])) {
            $this->error('Missing argument: tracker v3 id');
            return true;
        }

        if (! isset($parameters[6])) {
            $this->error('Missing argument: Keep original artifact ids');
            return true;
        }

        return false;
    }

    #[\Override]
    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}
