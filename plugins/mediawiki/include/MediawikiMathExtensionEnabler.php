<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use Mediawiki_Migration_MediawikiMigrator;

class MediawikiMathExtensionEnabler
{
    const PLUGIN_MAIN_HOOK_FILE = '/extensions/Math/Math.php';

    /**
     * @var MediawikiExtensionDAO
     */
    private $dao;
    /**
     * @var Mediawiki_Migration_MediawikiMigrator
     */
    private $migrator;

    public function __construct(MediawikiExtensionDAO $dao, Mediawiki_Migration_MediawikiMigrator $migrator)
    {
        $this->dao      = $dao;
        $this->migrator = $migrator;
    }

    /**
     * @return bool
     */
    public function canPluginBeLoaded($mediawiki_install_path, $is_update_running, \Project $project)
    {
        if (! $this->isPluginInstalled($mediawiki_install_path)) {
            return false;
        }

        if (! $is_update_running && ! $this->dao->isMathActivatedForProjectID($project->getID())) {
            $this->migrator->runUpdateScript($project);
            $this->dao->saveMathActivationForProjectID($project->getID());
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isPluginInstalled($mediawiki_install_path)
    {
        return file_exists($mediawiki_install_path . self::PLUGIN_MAIN_HOOK_FILE);
    }
}
