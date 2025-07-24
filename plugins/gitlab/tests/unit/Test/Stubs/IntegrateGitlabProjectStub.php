<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Test\Stubs;

use Tuleap\Gitlab\Group\IntegrateRepositoriesInGroupLinkCommand;
use Tuleap\Gitlab\Repository\IntegrateGitlabProject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class IntegrateGitlabProjectStub implements IntegrateGitlabProject
{
    private function __construct(private Ok $result)
    {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    #[\Override]
    public function integrateSeveralProjects(IntegrateRepositoriesInGroupLinkCommand $command): Ok|Err
    {
        return $this->result;
    }

    public static function withOkResult(): self
    {
        return new self(Result::ok(null));
    }
}
