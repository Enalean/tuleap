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

namespace Tuleap\Date;

use DateTimeImmutable;
use PFUser;
use Tuleap\GlobalLanguageMock;

class TlpRelativeDatePresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->willReturnCallback(static fn ($key1, $key2) => match ($key2) {
                'datefmt' => 'd/m/Y H:i',
                'datefmt_short' => 'd/m/Y',
            });
    }

    /**
     * @testWith ["relative_first-absolute_shown", "top", "relative"]
     *           ["absolute_first-relative_shown", "top", "absolute"]
     *           ["relative_first-absolute_tooltip", "tooltip", "relative"]
     *           ["absolute_first-relative_tooltip", "tooltip", "absolute"]
     */
    public function testBlockContext(
        string $preference_value,
        string $expected_placement,
        string $expected_preference,
    ): void {
        $builder = new TlpRelativeDatePresenterBuilder();

        $user = $this->buildUserMock($preference_value);

        $presenter = $builder->getTlpRelativeDatePresenterInBlockContext(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $user,
        );

        self::assertEquals('2009-02-14T00:31:30+01:00', $presenter->date);
        self::assertEquals('14/02/2009 00:31', $presenter->absolute_date);
        self::assertEquals($expected_placement, $presenter->placement);
        self::assertEquals($expected_preference, $presenter->preference);
        self::assertEquals('fr_FR', $presenter->locale);
    }

    /**
     * @testWith ["relative_first-absolute_shown", "right", "relative"]
     *           ["absolute_first-relative_shown", "right", "absolute"]
     *           ["relative_first-absolute_tooltip", "tooltip", "relative"]
     *           ["absolute_first-relative_tooltip", "tooltip", "absolute"]
     */
    public function testInlineContext(
        string $preference_value,
        string $expected_placement,
        string $expected_preference,
    ): void {
        $builder = new TlpRelativeDatePresenterBuilder();

        $user = $this->buildUserMock($preference_value);

        $presenter = $builder->getTlpRelativeDatePresenterInInlineContext(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $user,
        );

        self::assertEquals('2009-02-14T00:31:30+01:00', $presenter->date);
        self::assertEquals('14/02/2009 00:31', $presenter->absolute_date);
        self::assertEquals($expected_placement, $presenter->placement);
        self::assertEquals($expected_preference, $presenter->preference);
        self::assertEquals('fr_FR', $presenter->locale);
    }

    /**
     * @testWith ["relative_first-absolute_shown", "right", "relative"]
     *           ["absolute_first-relative_shown", "right", "absolute"]
     *           ["relative_first-absolute_tooltip", "tooltip", "relative"]
     *           ["absolute_first-relative_tooltip", "tooltip", "absolute"]
     */
    public function testInlineContextWithoutTime(
        string $preference_value,
        string $expected_placement,
        string $expected_preference,
    ): void {
        $builder = new TlpRelativeDatePresenterBuilder();

        $user = $this->buildUserMock($preference_value);

        $presenter = $builder->getTlpRelativeDatePresenterInInlineContextWithoutTime(
            (new DateTimeImmutable())->setTimestamp(1234567890),
            $user,
        );

        self::assertEquals('2009-02-14T00:31:30+01:00', $presenter->date);
        self::assertEquals('14/02/2009', $presenter->absolute_date);
        self::assertEquals($expected_placement, $presenter->placement);
        self::assertEquals($expected_preference, $presenter->preference);
        self::assertEquals('fr_FR', $presenter->locale);
    }

    private function buildUserMock(string $preference_value): PFUser&\PHPUnit\Framework\MockObject\MockObject
    {
        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')->willReturn($preference_value);
        $user->method('getLocale')->willReturn('fr_FR');

        return $user;
    }
}
