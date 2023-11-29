<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Mediawiki\XML;

use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\Event\Dispatchable;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Mediawiki\MediawikiDataDir;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\XML\Export\ExportOptions;
use Tuleap\Project\XML\Export\NoArchive;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use UserXMLExporter;
use function Psl\Type\bool;

final class XMLMediawikiExportabilityCheckerTest extends TestCase
{
    private \Project $project;
    private \PFUser $user;

    public function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withUsedService(\MediaWikiPlugin::SERVICE_SHORTNAME)->build();
        $this->user    = UserTestBuilder::anActiveUser()->build();
    }

    public function testItReturnsAFaultWhenExportIsPartial(): void
    {
        $checker = new XMLMediawikiExportabilityChecker(EventDispatcherStub::withIdentityCallback());
        $checker->checkMediawikiCanBeExportedToXML(
            $this->getExportXMLProjectEvent(ExportOptions::MODE_STRUCTURE),
            $this->getProjectMediawikiDataDirectory(Option::nothing(bool())),
        )->match(
            function () {
                self::fail("Expected an error");
            },
            function (Fault $fault) {
                self::assertInstanceOf(XMLPartialExportFault::class, $fault);
            }
        );
    }

    public function testItReturnsAFaultWhenServiceIsNotUsedInProject(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withoutServices()->build();

        $checker = new XMLMediawikiExportabilityChecker(EventDispatcherStub::withIdentityCallback());
        $checker->checkMediawikiCanBeExportedToXML(
            $this->getExportXMLProjectEvent(ExportOptions::MODE_ALL),
            $this->getProjectMediawikiDataDirectory(Option::nothing(bool())),
        )->match(
            function () {
                self::fail("Expected an error");
            },
            function (Fault $fault) {
                self::assertInstanceOf(XMLExportMediawikiServiceNotUsedFault::class, $fault);
            }
        );
    }

    public function testItReturnsAFaultWhenThereIsNoMediawikiDirectoryOnFileSystemForTheCurrentProject(): void
    {
        $checker = new XMLMediawikiExportabilityChecker(EventDispatcherStub::withIdentityCallback());
        $checker->checkMediawikiCanBeExportedToXML(
            $this->getExportXMLProjectEvent(ExportOptions::MODE_ALL),
            $this->getProjectMediawikiDataDirectory(Option::nothing(bool())),
        )->match(
            function () {
                self::fail("Expected an error");
            },
            function (Fault $fault) {
                self::assertInstanceOf(XMLExportMediawikiNotInstantiatedFault::class, $fault);
            }
        );
    }

    public function testItReturnsAFaultWhenMediaWikiServiceCannotBeActivated(): void
    {
        $checker = new XMLMediawikiExportabilityChecker(
            EventDispatcherStub::withCallback(
                function (Dispatchable $event) {
                    if ($event instanceof ProjectServiceBeforeActivation) {
                        $event->pluginSetAValue();
                        $event->setWarningMessage("You cannot");
                    }

                    return $event;
                }
            )
        );

        $checker->checkMediawikiCanBeExportedToXML(
            $this->getExportXMLProjectEvent(ExportOptions::MODE_ALL),
            $this->getProjectMediawikiDataDirectory(Option::fromValue(true)),
        )->match(
            function () {
                self::fail("Expected an error");
            },
            function (Fault $fault) {
                self::assertInstanceOf(XMLExportMediawikiCannotBeActivatedFault::class, $fault);
            }
        );
    }

    public function testItReturnsOk(): void
    {
        $checker = new XMLMediawikiExportabilityChecker(
            EventDispatcherStub::withCallback(
                function (Dispatchable $event) {
                    if ($event instanceof ProjectServiceBeforeActivation) {
                        $event->serviceCanBeActivated();
                    }

                    return $event;
                }
            )
        );

        self::assertTrue(
            $checker->checkMediawikiCanBeExportedToXML(
                $this->getExportXMLProjectEvent(ExportOptions::MODE_ALL),
                $this->getProjectMediawikiDataDirectory(Option::fromValue(true)),
            )->unwrapOr(false)
        );
    }

    private function getExportXMLProjectEvent(string $export_options_mode): ExportXmlProject
    {
        return new ExportXmlProject(
            $this->project,
            new ExportOptions(
                $export_options_mode,
                false,
                []
            ),
            new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />'),
            $this->user,
            $this->createStub(UserXMLExporter::class),
            new NoArchive(),
            'temporary/dump/path/on/filesystem',
            new NullLogger(),
        );
    }

    /**
     * @param Option<bool> $with_mediawiki_directory
     */
    private function getProjectMediawikiDataDirectory(Option $with_mediawiki_directory): MediawikiDataDir
    {
        $directory = $this->createStub(MediawikiDataDir::class);

        $with_mediawiki_directory->match(
            function () use ($directory) {
                $directory->method('getMediawikiDir')->willReturn(
                    vfsStream::setup("/path/to/project/mediawiki_directory")->url()
                );
            },
            function () use ($directory) {
                $directory->method('getMediawikiDir')->willReturn('');
            }
        );

        return $directory;
    }
}
