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
use Tuleap\Artidoc\Domain\Document\CheckCurrentUserHasArtidocPermissions;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CheckCurrentUserHasArtidocPermissionsStub implements CheckCurrentUserHasArtidocPermissions
{
    private function __construct(private ?bool $can_read, private ?bool $can_write)
    {
    }

    public static function withCurrentUserCanRead(): self
    {
        return new self(true, false);
    }

    public static function withCurrentUserCanWrite(): self
    {
        return new self(true, true);
    }

    public static function withCurrentUserCannotReadNorWrite(): self
    {
        return new self(false, false);
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null, null);
    }

    #[\Override]
    public function checkUserCanRead(Artidoc $artidoc): Ok|Err
    {
        if ($this->can_read === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        return $this->can_read ? Result::ok($artidoc) : Result::err(Fault::fromMessage('cannot read'));
    }

    #[\Override]
    public function checkUserCanWrite(Artidoc $artidoc): Ok|Err
    {
        if ($this->can_write === null) {
            throw new \Exception('Unexpected call to ' . __METHOD__);
        }

        return $this->can_write ? Result::ok($artidoc) : Result::err(Fault::fromMessage('cannot write'));
    }
}
