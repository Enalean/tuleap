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

use PFUser;
use Project;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\REST\v1\CreatedItemRepresentation;
use Tuleap\Docman\REST\v1\CreateOtherTypeItem;
use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\Docman\REST\v1\Others\DocmanOtherTypePOSTRepresentation;

final class CreateOtherTypeItemStub implements CreateOtherTypeItem
{
    private function __construct(
        private ?CreatedItemRepresentation $representation,
        private HardCodedMetadataException|CustomMetadataException|null $exception,
    ) {
    }

    public static function withCreatedItemRepresentation(CreatedItemRepresentation $representation): self
    {
        return new self($representation, null);
    }

    public static function withHardCodedMetadataException(HardCodedMetadataException $exception): self
    {
        return new self(null, $exception);
    }

    public static function withCustomMetadataException(CustomMetadataException $exception): self
    {
        return new self(null, $exception);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null, null);
    }

    #[\Override]
    public function createOtherType(
        \Docman_Folder $parent_item,
        PFUser $user,
        DocmanOtherTypePOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->representation === null) {
            throw new \Exception('Should not be called');
        }

        return $this->representation;
    }
}
