<?php
/**
 * Copyright (c) Enalean 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */


require_once 'MediawikiLanguageDao.php';
require_once 'MediawikiLanguageManager.php';
require_once 'MediawikiVersionDao.php';
require_once 'MediawikiVersionManager.php';
require_once 'MediawikiMLEBExtensionDao.php';
require_once 'Migration/MediawikiMigrator.php';
require_once 'MediawikiMLEBExtensionManager.php';

class MediawikiMLEBExtensionManagerLoader {

    public function getMediawikiMLEBExtensionManager() {

        return new MediawikiMLEBExtensionManager(
            new Mediawiki_Migration_MediawikiMigrator(),
            new MediawikiMLEBExtensionDao(),
            ProjectManager::instance(),
            new MediawikiVersionManager(
                new MediawikiVersionDao()
            ),
            new MediawikiLanguageManager(
                new MediawikiLanguageDao()
            )
        );
    }
}
