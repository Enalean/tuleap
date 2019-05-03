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

namespace Tuleap\Event\Events;

use Tuleap\Event\Dispatchable;

final class ArchiveDeletedItemEvent implements Dispatchable
{
    public const NAME = 'archiveDeletedItem';

    private $status = true;
    private $skip_duplicated;
    /**
     * @var ArchiveDeletedItemProvider
     */
    private $file_provider;

    public function __construct(ArchiveDeletedItemProvider $file_provider, bool $skip_duplicated = false)
    {
        $this->file_provider = $file_provider;
        $this->skip_duplicated = $skip_duplicated;
    }

    public function isSuccessful(): bool
    {
        return $this->status === true;
    }

    public function setFailure(): void
    {
        $this->status = false;
    }

    public function getSourcePath(): string
    {
        return $this->file_provider->getArchivePath();
    }

    public function getArchivePrefix(): string
    {
        return $this->file_provider->getPrefix();
    }

    public function mustSkipDuplicated(): bool
    {
        return $this->skip_duplicated;
    }
}
