<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVN\Repository\Repository;

/**
 * Read the content of a .SVNAccessFile
 */
class AccessFileReader
{

    private static $FILENAME = ".SVNAccessFile";

    public function readContentBlock(Repository $repository)
    {
        $blocks = $this->extractBlocksFromAccessFile($repository);

        return $blocks['content'];
    }

    public function readDefaultBlock(Repository $repository)
    {
        $blocks = $this->extractBlocksFromAccessFile($repository);

        return $blocks['default'];
    }

    private function extractBlocksFromAccessFile(Repository $repository)
    {
        $blocks = [
            'default' => '',
            'content' => ''
        ];

        $in_default_block = false;
        foreach (file($this->getPath($repository)) as $line) {
            if ($this->isDefaultBlockStarting($line)) {
                $in_default_block = true;
                continue;
            }

            if ($this->isDefaultBlockEnding($line)) {
                $in_default_block = false;
                continue;
            }

            if ($in_default_block) {
                $blocks['default'] .= $line;
            } else {
                $blocks['content'] .= $line;
            }
        }

        return $blocks;
    }

    private function isDefaultBlockStarting($line)
    {
        return strpos($line, $this->getBeginDefault()) !== false;
    }

    private function isDefaultBlockEnding($line)
    {
        return strpos($line, $this->getEndDefault()) !== false;
    }

    private function getPath(Repository $repository)
    {
        return $repository->getSystemPath() . '/' . self::$FILENAME;
    }

    private function getBeginDefault()
    {
        return '# BEGIN CODENDI DEFAULT';
    }

    private function getEndDefault()
    {
        return '# END CODENDI DEFAULT';
    }
}
