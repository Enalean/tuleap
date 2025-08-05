<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use Tuleap\Artidoc\Stubs\Upload\Section\File\RemoveExpirationDateStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;
use Tuleap\Upload\NextGen\FileBeingUploadedInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileUploadFinisherTest extends TestCase
{
    public function testFinishUpload(): void
    {
        $identifier       = (new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $file_information = new FileBeingUploadedInformation($identifier, 'Filename', 123, 0);

        $remove_expiration = RemoveExpirationDateStub::build();
        $finisher          = new FileUploadFinisher($remove_expiration);

        $finisher->finishUpload(new NullServerRequest(), $file_information);

        self::assertTrue($remove_expiration->isCalled());
    }
}
