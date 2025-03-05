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

namespace Tuleap\Project\Registration\Template\Upload;

use Psr\Log\NullLogger;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArchiveWithoutDataCheckerTest extends TestCase
{
    public function testArchiveContainsUsers(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withIdentityCallback(),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(
            new \SimpleXMLElement(
                <<<EOS
                    <project>
                        <ugroups>
                            <ugroup>
                                <members />
                            </ugroup>
                            <ugroup>
                                <members>
                                    <member />
                                </members>
                            </ugroup>
                        </ugroups>
                    </project>
                EOS,
            ),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testArchiveDoesNotContainUsers(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withIdentityCallback(),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(
            new \SimpleXMLElement(
                <<<EOS
                    <project>
                        <ugroups>
                            <ugroup>
                                <members />
                            </ugroup>
                            <ugroup>
                                <members />
                            </ugroup>
                        </ugroups>
                    </project>
                EOS,
            ),
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testArchiveContainsFrsPackages(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withIdentityCallback(),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(
            new \SimpleXMLElement(
                <<<EOS
                    <project>
                        <frs>
                            <package />
                        </frs>
                    </project>
                EOS,
            ),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testArchiveDoesNotContainFrsPackages(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withIdentityCallback(),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(
            new \SimpleXMLElement(
                <<<EOS
                    <project>
                        <frs />
                    </project>
                EOS,
            ),
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testPluginContainsData(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withCallback(static function (object $event) {
                if ($event instanceof ArchiveWithoutDataCheckerErrorCollection) {
                    $event->addError('Data in plugin');
                }

                return $event;
            }),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(new \SimpleXMLElement('<project />'));

        self::assertTrue(Result::isErr($result));
        self::assertSame('Data in plugin', (string) $result->error);
    }

    public function testItConcatenatesAllErrorsSoThatUsersCanFixThemAllBeforeRetryingTheImport(): void
    {
        $checker = new ArchiveWithoutDataChecker(
            EventDispatcherStub::withCallback(static function (object $event) {
                if ($event instanceof ArchiveWithoutDataCheckerErrorCollection) {
                    $event->addError('Data in plugin A');
                    $event->addError('Data in plugin B');
                }

                return $event;
            }),
            new NullLogger(),
        );

        $result = $checker->checkArchiveContent(new \SimpleXMLElement(
            <<<EOS
                    <project>
                        <ugroups>
                            <ugroup>
                                <members />
                            </ugroup>
                            <ugroup>
                                <members>
                                    <member />
                                </members>
                            </ugroup>
                        </ugroups>
                        <frs>
                            <package />
                        </frs>
                    </project>
                EOS,
        ));

        self::assertTrue(Result::isErr($result));
        self::assertSame(
            <<<EOS
            Archive should not contain users.
            Archive should not contain FRS packages.
            Data in plugin A
            Data in plugin B
            EOS,
            (string) $result->error
        );
    }
}
