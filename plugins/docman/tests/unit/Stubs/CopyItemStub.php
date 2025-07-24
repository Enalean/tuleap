<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Stubs;

use DateTimeImmutable;
use Docman_Folder;
use PFUser;
use Tuleap\Docman\REST\v1\CopyItem\CopyItem;
use Tuleap\Docman\REST\v1\CopyItem\DocmanCopyItemRepresentation;
use Tuleap\Docman\REST\v1\CreatedItemRepresentation;

final class CopyItemStub implements CopyItem
{
    private function __construct(private ?CreatedItemRepresentation $representation)
    {
    }

    public static function withCreatedItemRepresentation(CreatedItemRepresentation $representation): self
    {
        return new self($representation);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    #[\Override]
    public function copyItem(
        DateTimeImmutable $current_time,
        Docman_Folder $destination_folder,
        PFUser $user,
        DocmanCopyItemRepresentation $representation,
    ): CreatedItemRepresentation {
        if ($this->representation === null) {
            throw new \Exception('Should not be called');
        }

        return $this->representation;
    }
}
