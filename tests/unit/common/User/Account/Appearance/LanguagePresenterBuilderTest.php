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

class LanguagePresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetLanguagePresenterCollectionForUser()
    {
        $factory = Mockery::mock(\BaseLanguageFactory::class);
        $factory
            ->shouldReceive('getAvailableLanguages')
            ->once()
            ->andReturn([
                'ja_JP' => '日本語',
                'en_US' => 'English',
                'fr_FR' => 'Français'
            ]);

        $user = Mockery::mock(\PFUser::class);
        $user
            ->shouldReceive('getLocale')
            ->once()
            ->andReturn('fr_FR');

        $builder = new LanguagePresenterBuilder($factory);
        $this->assertEquals(
            [
                new LanguagePresenter('en_US', 'English', false),
                new LanguagePresenter('fr_FR', 'Français', true),
                new LanguagePresenter('ja_JP', '日本語', false),
            ],
            $builder->getLanguagePresenterCollectionForUser($user)
        );
    }
}
