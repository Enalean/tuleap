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

namespace Tuleap\Artidoc\Stubs\Domain\Document;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class RetrieveArtidocWithContextStub implements RetrieveArtidocWithContext
{
    /**
     * @param Ok<ArtidocWithContext>|Err<Fault>|null $result
     */
    private function __construct(private Ok|Err|null $result, private bool $can_write)
    {
    }

    public static function withDocumentUserCanRead(ArtidocWithContext $document): self
    {
        return new self(Result::ok($document), false);
    }

    public static function withDocumentUserCanWrite(ArtidocWithContext $document): self
    {
        return new self(Result::ok($document), true);
    }

    public static function withoutDocument(): self
    {
        return new self(Result::err(Fault::fromMessage('Not found')), false);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null, false);
    }

    #[\Override]
    public function retrieveArtidocUserCanRead(int $id): Ok|Err
    {
        if ($this->result === null) {
            throw new \Exception('Unexpected call to retrieveArtidocUserCanRead()');
        }

        return $this->result;
    }

    #[\Override]
    public function retrieveArtidocUserCanWrite(int $id): Ok|Err
    {
        if ($this->result === null) {
            throw new \Exception('Unexpected call to retrieveArtidocUserCanWrite()');
        }

        if (! $this->can_write) {
            return Result::err(UserCannotWriteDocumentFault::build());
        }

        return $this->result;
    }
}
