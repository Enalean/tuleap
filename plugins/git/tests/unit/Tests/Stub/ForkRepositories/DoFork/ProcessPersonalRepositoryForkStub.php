<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\Tests\Stub\ForkRepositories\DoFork;

use PFUser;
use Project;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Git\ForkRepositories\DoFork\ProcessPersonalRepositoryFork;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class ProcessPersonalRepositoryForkStub implements ProcessPersonalRepositoryFork
{
    public function __construct(private Ok|Err $result)
    {
    }

    public static function withSuccess(): self
    {
        return new self(Result::ok([]));
    }

    /**
     * @no-named-arguments
     */
    public static function withWarnings(Fault $warning, Fault ...$other_warnings): self
    {
        return new self(Result::ok([$warning, ...$other_warnings]));
    }

    public static function withError(Fault $fault): self
    {
        return new self(Result::err($fault));
    }

    public static function willNotBeCalled(): self
    {
        return new self(Result::err('Unexpected ProcessPersonalRepositoryForkStub::processPersonalFork call.'));
    }

    #[\Override]
    public function processPersonalFork(PFUser $user, Project $project, ServerRequestInterface $request): Ok|Err
    {
        return $this->result;
    }
}
