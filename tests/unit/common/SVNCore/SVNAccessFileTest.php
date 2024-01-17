<?php
/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

use Tuleap\SVNCore\SVNAccessFile;
use Tuleap\SVNCore\SvnAccessFileContent;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class SVNAccessFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @dataProvider getRenameData
     */
    public function testRenameGroup(string $old_group, string $new_group, string $source_access_file, string $expected_access_file): void
    {
        $saf = new SVNAccessFile();

        $access_file_content = new SvnAccessFileContent(
            <<<EOT
            [groups]
            members = user1, user2
            ugroup1 = user1
            ugroup2 = user2
            ugroup3 = user1, user2

            [/]
            * =
            @members = rw
            EOT,
            $source_access_file,
        );

        $result = $saf->parseGroupLines($access_file_content, $new_group, $old_group);
        self::assertEquals($expected_access_file, $result->contents);
    }

    public static function getRenameData(): iterable
    {
        return [
            'ugroup not redefined is renamed' => [
                'ugroup1',
                'ugroup11',
                <<<EOT
                [groups]
                ugroup3 = user1

                [/path]
                @ugroup1 = rw
                @ugroup2 = rw
                EOT,
                <<<EOT
                [groups]
                ugroup3 = user1

                [/path]
                @ugroup11 = rw
                @ugroup2 = rw
                EOT,
            ],
            'ugroup redefined by admin is not renamed' => [
                'ugroup3',
                'ugroup33',
                <<<EOT
                [groups]
                ugroup3 = user1

                [/path]
                @ugroup3 = rw
                @ugroup2 = rw
                EOT,
                <<<EOT
                [groups]
                ugroup3 = user1

                [/path]
                @ugroup3 = rw
                @ugroup2 = rw
                EOT,
            ],
        ];
    }

    /**
     * @dataProvider getSvnAccessFileSamples
     */
    public function testParseGroupLines(string $source, string $expected): void
    {
        $saf = new SVNAccessFile();

        $access_file_content = new SvnAccessFileContent(
            <<<EOT
            [groups]
            members = user1, user2
            uGroup1 = user3

            [/]
            *=
            @members=rw
            EOT,
            $source,
        );

        $this->assertEquals($expected, $saf->parseGroupLines($access_file_content)->contents);
    }

    public static function getSvnAccessFileSamples(): iterable
    {
        return [
            'invalid ugroup syntax (was testisGroupDefinedInvalidSyntax)' => [
                <<<EOT
                [/path]
                @uGroup1  rw
                @ uGroup1 = rw
                @@uGroup1 = rw
                EOT,
                <<<EOT
                [/path]
                # @uGroup1  rw
                # @ uGroup1 = rw
                # @@uGroup1 = rw
                EOT,
            ],
            'permission uses a group not defined (was testisGroupDefinedNoUGroup)' => [
                <<<EOT
                [/path]
                @uGroup3 = rw
                EOT,
                <<<EOT
                [/path]
                # @uGroup3 = rw
                EOT,
            ],
            'comment line with ugroup that do not have permissions (was testCommentInvalidLine & testisGroupDefined)' => [
                <<<EOT
                [/path]
                @ugroup1
                EOT,
                <<<EOT
                [/path]
                # @ugroup1
                EOT,
            ],
            'comment ugroup line that do not respect case' => [
                <<<EOT
                [/]
                @members = rw
                @ugroup1 = r
                EOT,
                <<<EOT
                [/]
                @members = rw
                # @ugroup1 = r
                EOT,
            ],
            'groups are redefined in one user controlled section' => [
                <<<EOT
                [/]
                @members=rw
                @group1 = r

                [Groups]
                group1=user1, user2

                [/trunk]
                @group1=r
                user1=rw
                EOT,
                <<<EOT
                [/]
                @members=rw
                # @group1 = r

                [Groups]
                group1=user1, user2

                [/trunk]
                @group1=r
                user1=rw
                EOT,
            ],
            'groups are redefined in several user controlled sections' => [
                <<<EOT
                [/]
                @members=rw
                @group1 = r

                [Groups]
                group1=user1, user2

                [groups]
                group2=user3

                [/trunk]
                @group1=r
                user1=rw
                @group2=rw
                EOT,
                <<<EOT
                [/]
                @members=rw
                # @group1 = r

                [Groups]
                group1=user1, user2

                [groups]
                group2=user3

                [/trunk]
                @group1=r
                user1=rw
                @group2=rw
                EOT,
            ],
        ];
    }
}
