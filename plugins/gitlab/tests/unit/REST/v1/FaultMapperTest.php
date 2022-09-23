<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Gitlab\Core\ProjectNotFoundFault;
use Tuleap\Gitlab\Permission\UserIsNotGitAdministratorFault;
use Tuleap\NeverThrow\Fault;

final class FaultMapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function dataProviderFaults(): iterable
    {
        yield 'Project not found' => [ProjectNotFoundFault::fromProjectId(135), 404];
        yield 'User is not git administrator' => [UserIsNotGitAdministratorFault::build(), 403];
        yield 'Default to error 500 for unknown Fault' => [Fault::fromMessage('Unmapped fault'), 500];
    }

    /**
     * @dataProvider dataProviderFaults
     */
    public function testItMapsFaultsToRestExceptions(Fault $fault, int $expected_status_code): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        FaultMapper::mapToRestException($fault);
    }
}
