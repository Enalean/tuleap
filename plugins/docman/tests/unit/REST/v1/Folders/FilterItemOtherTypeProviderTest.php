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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FilterItemType;
use Luracast\Restler\RestException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilterItemOtherTypeProviderTest extends TestCase
{
    public function testSettingAValueSetTheTypeToOther(): void
    {
        $filter = new Docman_FilterItemType(new \Docman_Metadata());
        $filter->setValue(0);

        $provider = new FilterItemOtherTypeProvider($filter, 'whatever');
        $provider->setValue('my-internal-value');

        self::assertSame(\Docman_Item::TYPE_OTHER, $provider->getExternalFilter()->getValue());
        self::assertSame('my-internal-value', $provider->getExternalFilter()->getAlternateValue());
    }

    public function testNotSettingAValueRaiseAnExceptionBecauseWeAreDealingWithAnUnknownType(): void
    {
        $filter = new Docman_FilterItemType(new \Docman_Metadata());
        $filter->setValue(0);

        $provider = new FilterItemOtherTypeProvider($filter, 'whatever');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $provider->getExternalFilter();
    }
}
