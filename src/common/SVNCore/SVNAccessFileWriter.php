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

class SVNAccessFileWriter
{
    private string $accessfile;
    private string $err;

    public function __construct(string $svnroot)
    {
        $this->accessfile = "$svnroot/.SVNAccessFile";
    }

    public function filename(): string
    {
        return $this->accessfile;
    }

    public function isErrorFile(): bool
    {
        return $this->err === 'file';
    }

    public function write(string $contents): bool
    {
        $fd = fopen($this->accessfile, "w+");
        if ($fd) {
            if (fwrite($fd, str_replace("\r", '', $contents)) === false) {
                $this->err = 'write';
                $ret       = false;
            } else {
                $ret = true;
            }
        } else {
            $this->err = 'file';
            $ret       = false;
        }
        fclose($fd);
        return $ret;
    }

    public function writeWithDefaults(SvnAccessFileDefaultBlock $default_block, string $contents): bool
    {
        return $this->write($default_block->formatForSave() . $contents);
    }
}
