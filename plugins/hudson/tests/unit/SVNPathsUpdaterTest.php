<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class SVNPathsUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testTtAddsSlashesAtTheBeginingOfEachPathIfNecessary(): void
    {
        $updater = new SVNPathsUpdater();

        $submitted_content = <<<EOS
folder01/
/folder02/
folder02/folder03/
EOS;
        $expected_content  = <<<EOS
/folder01/
/folder02/
/folder02/folder03/
EOS;

        self::assertEquals($updater->transformContent($submitted_content), $expected_content);
    }

    public function testItAddsSlashesAtTheEndOfEachPathIfNecessary(): void
    {
        $updater = new SVNPathsUpdater();

        $submitted_content = <<<EOS
/folder01
/folder02/
/folder02/folder03
EOS;
        $expected_content  = <<<EOS
/folder01/
/folder02/
/folder02/folder03/
EOS;

        self::assertEquals($updater->transformContent($submitted_content), $expected_content);
    }

    public function testItRemovesTrailingSpaces(): void
    {
        $updater = new SVNPathsUpdater();

        $submitted_content = <<<EOS
/folder01/
EOS;
        $expected_content  = <<<EOS
/folder01/
EOS;

        self::assertEquals($updater->transformContent($submitted_content), $expected_content);
    }

    public function testItDoesNotTranformEmptyContent(): void
    {
        $updater = new SVNPathsUpdater();

        $submitted_content = '';
        $expected_content  = '';

        self::assertEquals($updater->transformContent($submitted_content), $expected_content);
    }

    public function testItTranformsSpacesOnlyContentAsEmptyContent(): void
    {
        $updater = new SVNPathsUpdater();

        $submitted_content = '            ';
        $expected_content  = '';

        self::assertEquals($updater->transformContent($submitted_content), $expected_content);
    }
}
