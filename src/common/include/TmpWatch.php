<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap;

class TmpWatch
{
    /**
     * @var string
     */
    private $target_directory;
    /**
     * @var int
     */
    private $nb_hours;

    public function __construct(string $target_directory, int $nb_hours)
    {
        $this->target_directory = $target_directory;
        $this->nb_hours = $nb_hours;
    }

    /**
     * @throws InvalidDirectoryException
     * @throws \Exception
     */
    public function run(): void
    {
        if (! is_dir($this->target_directory)) {
            throw new InvalidDirectoryException('Cannot delete content of invalid directory: `' . $this->target_directory . '`');
        }
        $now = new \DateTimeImmutable();
        $some_hours_ago = $now->sub(new \DateInterval(sprintf('PT%dH', $this->nb_hours)));
        foreach (new \DirectoryIterator($this->target_directory) as $file) {
            assert($file instanceof \DirectoryIterator);
            if ($file->isDir()) {
                continue;
            }
            if ($file->getMTime() <= $some_hours_ago->getTimestamp()) {
                unlink($file->getPathname());
            }
        }
    }
}
