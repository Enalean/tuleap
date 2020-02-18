<?php
// vim: sts=4:sw=4:et
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

class SVN_AccessFile_Writer
{

    private $accessfile;
    private $err;

    public function __construct($svnroot)
    {
        $this->accessfile = "$svnroot/.SVNAccessFile";
    }

    public function filename()
    {
        return $this->accessfile;
    }

    public function hasError()
    {
        return (bool) $this->err;
    }

    public function isErrorFile()
    {
        return $this->err == 'file';
    }

    public function isErrorWrite()
    {
        return $this->err == 'write';
    }

    public function read_defaults($display = false)
    {
        $this->err = false;
        $fd = @fopen($this->accessfile, "r");
        $buffer = '';
        if ($fd) {
            $in_settings = false;
            while (!feof($fd)) {
                $line = fgets($fd, 4096);
                //if for display: don't include comment lines
                if ($display && strpos($line, '# END CODENDI DEFAULT') !== false) {
                    $in_settings = false;
                    break;
                } elseif (!$display && strpos($line, '# BEGIN CODENDI DEFAULT') !== false) {
                    $in_settings = true;
                }

                if ($in_settings) {
                    $buffer .= $line;
                }

                if ($display && strpos($line, '# BEGIN CODENDI DEFAULT') !== false) {
                    $in_settings = true;
                } elseif (!$display && strpos($line, '# END CODENDI DEFAULT') !== false) {
                    $in_settings = false;
                    break;
                }
            }
            fclose($fd);
        }
        return $buffer;
    }

    public function write($contents)
    {
        $this->err = false;
        $fd = fopen($this->accessfile, "w+");
        if ($fd) {
            if (fwrite($fd, str_replace("\r", '', $contents)) === false) {
                $this->err = 'write';
                $ret = false;
            } else {
                $ret = true;
            }
        } else {
            $this->err = 'file';
            $ret = false;
        }
        fclose($fd);
        return $ret;
    }

    public function write_with_defaults($contents)
    {
        return $this->write($this->read_defaults() . $contents);
    }
}
