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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LocalSettingsInstantiatorTest extends TestCase
{
    public function testInstantiatesLocalSettings(): void
    {
        $local_settings_persistor = new LocalSettingsPersistStub();

        $instantiator = new LocalSettingsInstantiator(
            new LocalSettingsRepresentationForTestBuilder(),
            $local_settings_persistor,
            new DBTransactionExecutorPassthrough()
        );

        $instantiator->instantiateLocalSettings();

        self::assertTrue($local_settings_persistor->has_persisted);
    }
}
