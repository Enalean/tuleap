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

namespace Tuleap\SeatManagement;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use CuyZ\Valinor\Mapper\Source\JsonSource;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use Tuleap\Mapper\ValinorMapperBuilderFactory;
use Tuleap\Test\PHPUnit\TestCase;
use function Psl\Filesystem\read_directory;
use function Psl\File\read;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class LicensePublicKeyTest extends TestCase
{
    /**
     * @param non-empty-string $filename
     */
    #[DataProvider('collectPublicKeys')]
    public function testAllPublicKeysAreValid(string $filename): void
    {
        $mapper = ValinorMapperBuilderFactory::mapperBuilder()->registerConstructor(Uuid::fromString(...))->mapper();

        try {
            $result = $mapper->map(LicensePublicKey::class, new JsonSource(read($filename)));
            self::assertInstanceOf(LicensePublicKey::class, $result);
        } catch (MappingError | InvalidSource $error) {
            self::fail($error->getMessage());
        }
    }

    public static function collectPublicKeys(): \Generator
    {
        foreach (read_directory(__DIR__ . '/../../../../src/common/SeatManagement/keys') as $file) {
            if (! is_file($file)) {
                continue;
            }

            if (! str_ends_with($file, '.key')) {
                continue;
            }

            yield [$file];
        }
    }
}
