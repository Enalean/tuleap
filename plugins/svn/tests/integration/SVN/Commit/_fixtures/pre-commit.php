#!/opt/remi/php80/root/usr/bin/php
<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

require_once __DIR__ . '/../../../../../../../src/vendor/autoload.php';
require_once __DIR__ . '/../../../../../include/svnPlugin.php';

$repository_path = $argv[1];
$transaction     = $argv[2];

$svnlook = new \Tuleap\SVN\Commit\Svnlook(new System_Command());

ForgeConfig::set('sys_data_dir', dirname($repository_path, 3));

$repository = SvnRepository::buildActiveRepository(2, basename($repository_path), ProjectTestBuilder::aProject()->build());

$filesize = $svnlook->getFileSize($repository, $transaction, 'trunk/README');
file_put_contents($repository_path . '/filesize', $filesize);
