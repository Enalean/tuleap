<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\Semantic\SemanticNotSupportedFault;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FaultMapperTest extends TestCase
{
    public static function dataProviderFaults(): iterable
    {
        yield 'Artifact does not exists' => [ArtifactDoesNotExistFault::build(10), 400];
        yield 'Artifact link field does not exist' => [ArtifactLinkFieldDoesNotExistFault::build(15), 400];
        yield 'Semantic is not supported' => [SemanticNotSupportedFault::fromSemanticName('status'), 400];
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
