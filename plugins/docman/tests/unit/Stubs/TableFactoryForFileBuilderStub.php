<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Tests\Stub;

use Docman_ApprovalTableFileFactory;
use Docman_File;
use Tuleap\Docman\ApprovalTable\TableFactoryForFileBuilder;

class TableFactoryForFileBuilderStub implements TableFactoryForFileBuilder
{
    private function __construct(private Docman_ApprovalTableFileFactory $factory)
    {
    }

    public static function buildWithFactory(Docman_ApprovalTableFileFactory $factory): self
    {
        return new self($factory);
    }

    #[\Override]
    public function getTableFactoryForFile(Docman_File $item): Docman_ApprovalTableFileFactory
    {
        return $this->factory;
    }
}
