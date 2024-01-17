<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\SVNCore;

use ForgeConfig;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Webimpress\SafeWriter\FileWriter;

class SVNAccessFileWriter
{
    /**
     * @psalm-return Ok<null>|Err<SVNAccessFileWriteFault>
     */
    public function writeWithDefaults(Repository $repository, SVNAccessFileContent $svn_contents): Ok|Err
    {
        $accessfile = $repository->getSystemPath() . '/' . SVNAccessFileReader::FILENAME;
        try {
            FileWriter::writeFile($accessfile, $svn_contents->formatForSave(), 0644);
            chown($accessfile, ForgeConfig::getApplicationUserLogin());
            chgrp($accessfile, ForgeConfig::getApplicationUserLogin());

            return Result::ok(null);
        } catch (\Throwable $e) {
            return Result::err(SVNAccessFileWriteFault::fromWriteError($accessfile, $e));
        }
    }
}
