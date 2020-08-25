<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_Folder;

/**
 * @psalm-immutable
 */
class FolderPropertiesRepresentation
{
    /** @var int */
    public $total_size;

    /**
     * @var int
     */
    public $nb_files;

    private function __construct(int $total_size, int $nb_files)
    {
        $this->total_size = $total_size;
        $this->nb_files   = $nb_files;
    }

    public static function build(Docman_Folder $folder): self
    {
        $visitor = new ComputeFolderSizeVisitor();
        $size_collector = new FolderSizeCollector();

        $folder->accept($visitor, ['size_collector' => $size_collector]);

        return new self($size_collector->getTotalSize(), $size_collector->getNbFiles());
    }
}
