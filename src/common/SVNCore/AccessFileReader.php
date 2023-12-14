<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore;

/**
 * Read the content of a .SVNAccessFile
 */
class AccessFileReader
{
    private const FILENAME    = ".SVNAccessFile";
    public const BEGIN_MARKER = '# BEGIN CODENDI DEFAULT SETTINGS - DO NOT REMOVE';
    public const END_MARKER   = '# END CODENDI DEFAULT SETTINGS';

    public function __construct(private readonly SvnAccessFileDefaultBlockGeneratorInterface $default_block_generator)
    {
    }

    public function readContentBlock(Repository $repository): string
    {
        return $this->getAccessFileContent($repository)->project_defined;
    }

    public function readDefaultBlock(Repository $repository): string
    {
        return $this->getAccessFileContent($repository)->default;
    }

    public function getAccessFileContent(Repository $repository): SvnAccessFileContent
    {
        $content = '';

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

            if (! $in_default_block) {
                $content .= $line;
            }
        }

        return new SvnAccessFileContent($this->default_block_generator->getDefaultBlock($repository->getProject())->content, $content);
    }

    private function isDefaultBlockStarting(string $line): bool
    {
        return str_contains($line, self::BEGIN_MARKER);
    }

    private function isDefaultBlockEnding(string $line): bool
    {
        return str_contains($line, self::END_MARKER);
    }

    private function getPath(Repository $repository): string
    {
        return $repository->getSystemPath() . '/' . self::FILENAME;
    }
}
