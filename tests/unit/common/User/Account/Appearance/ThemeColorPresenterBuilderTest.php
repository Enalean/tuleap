<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Appearance;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ThemeColorPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetColorPresenterCollection()
    {
        $user = Mockery::mock(\PFUser::class);
        $theme_variant = Mockery::mock(\ThemeVariant::class);

        $theme_variant
            ->shouldReceive('getAllowedVariants')
            ->once()
            ->andReturn(['FlamingParrot_Purple', 'FlamingParrot_Green', 'FlamingParrot_Blue']);

        $theme_variant
            ->shouldReceive('getVariantForUser')
            ->with($user)
            ->once()
            ->andReturn('FlamingParrot_Green');

        $builder = new ThemeColorPresenterBuilder($theme_variant);
        $this->assertEquals(
            [
                new ThemeColorPresenter(\ThemeVariantColor::buildFromName('blue'), false),
                new ThemeColorPresenter(\ThemeVariantColor::buildFromName('green'), true),
                new ThemeColorPresenter(\ThemeVariantColor::buildFromName('purple'), false),
            ],
            $builder->getColorPresenterCollection($user)
        );
    }
}
