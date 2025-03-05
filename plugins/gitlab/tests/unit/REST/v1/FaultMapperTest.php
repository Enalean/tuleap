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
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabRequestFault;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\GitlabResponseAPIFault;
use Tuleap\Gitlab\Core\ProjectNotFoundFault;
use Tuleap\Gitlab\Group\GroupLinkNotFoundFault;
use Tuleap\Gitlab\Group\InvalidBranchPrefixFault;
use Tuleap\Gitlab\Permission\UserIsNotGitAdministratorFault;
use Tuleap\NeverThrow\Fault;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FaultMapperTest extends TestCase
{
    public static function dataProviderFaults(): iterable
    {
        yield 'Project not found' => [ProjectNotFoundFault::fromProjectId(135), 404];
        yield 'GitLab Group link not found' => [GroupLinkNotFoundFault::fromId(86), 404];
        yield 'User is not git administrator' => [UserIsNotGitAdministratorFault::build(), 403];
        yield 'Invalid branch prefix' => [InvalidBranchPrefixFault::fromBranchPrefix('dev:'), 400];
        yield 'Invalid Gitlab response' => [GitlabResponseAPIFault::fromGitlabResponseAPIException(new GitlabResponseAPIException('Bad request')), 400];
        yield 'Bad request send to Gitlab' => [GitlabRequestFault::fromGitlabRequestException(new GitlabRequestException(400, 'Bad request')), 400];
        yield 'Default to error 500 for unknown Fault' => [Fault::fromMessage('Unmapped fault'), 500];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderFaults')]
    public function testItMapsFaultsToRestExceptions(Fault $fault, int $expected_status_code): void
    {
        $this->expectException(RestException::class);
        $this->expectExceptionCode($expected_status_code);
        FaultMapper::mapToRestException($fault);
    }
}
