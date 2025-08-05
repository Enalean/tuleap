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

use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidoc;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class RetrieveArtidocStub implements RetrieveArtidoc
{
    /**
     * @param Ok<Artidoc>|Err<Fault> $result
     */
    private function __construct(private Ok|Err $result)
    {
    }

    public static function withDocument(Artidoc $document): self
    {
        return new self(Result::ok($document));
    }

    public static function withoutDocument(): self
    {
        return new self(Result::err(Fault::fromMessage('Not found')));
    }

    #[\Override]
    public function retrieveArtidoc(int $id): Ok|Err
    {
        return $this->result;
    }
}
