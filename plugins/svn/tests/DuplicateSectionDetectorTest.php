<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\AccessControl;

use Tuleap\SVNCore\SvnAccessFileContent;
use Tuleap\Test\PHPUnit\TestCase;

final class DuplicateSectionDetectorTest extends TestCase
{
    /**
     * @dataProvider getTestWarnWhenPathIsUsedTwice
     */
    public function testWarnWhenPathIsUsedTwice(string $source, int $nb_faults): void
    {
        $svn_access_file = new SvnAccessFileContent(
            <<<EOT
            [groups]
            members = user1, user2

            [/]
            *=
            @members=rw
            EOT,
            $source,
        );

        $duplicate_detector = new DuplicateSectionDetector();
        $faults             = $duplicate_detector->inspect($svn_access_file);

        self::assertCount($nb_faults, $faults);
    }

    public static function getTestWarnWhenPathIsUsedTwice(): iterable
    {
        return [
            'default block is redefined' => [
                <<<EOT
                [/]
                * = r
                @members = r
                EOT,
                1,
            ],
            'path is defined multiple times in content block' => [
                <<<EOT
                [/trunk]
                * = r

                [/tags]
                @members = rw

                [/trunk]
                @members = r
                EOT,
                1,
            ],
            'groups is re-defined in content block' => [
                <<<EOT
                [/trunk]
                * = r

                [groups]
                developers = ana
                EOT,
                1,
            ],
            'commented paths are ignored' => [
                <<<EOT
                #[/trunk]
                #* = r

                [/tags]
                @members = rw

                [/trunk]
                @members = r
                EOT,
                0,
            ],
            'commented groups are ignored' => [
                <<<EOT
                # [groups]
                # developers = ana

                [/tags]
                @members = rw

                [/trunk]
                @members = r
                EOT,
                0,
            ],
            'mixed duplicated groups and paths are forbidden' => [
                <<<EOT
                [groups]
                developers = ana

                [/tags]
                @members = rw

                [/trunk]
                @members = r

                [/]
                foo=r
                EOT,
                2,
            ],
        ];
    }
}
