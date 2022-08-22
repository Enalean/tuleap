<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\ReferenceAliasSVN;

use Project;
use Psr\Log\NullLogger;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Test\Builders\ProjectTestBuilder;

include __DIR__ . '/bootstrap.php';

final class ReferencesImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    private ReferencesImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao      = $this->createMock(\Tuleap\ReferenceAliasSVN\Dao::class);
        $this->importer = new ReferencesImporter($this->dao, new NullLogger());
    }

    public function testItShouldAddSVNLinks(): void
    {
        $xml        = <<<XML
            <references>
                <reference source="cmmt12" target="2"/>
            </references>
XML;
        $simple_xml = new \SimpleXMLElement($xml);

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::once())->method('insertRef')->with('cmmt12', 123, 2);

        $project = ProjectTestBuilder::aProject()->build();

        $this->importer->importCompatRefXML(new ImportConfig(), $project, $simple_xml, $this->buildRepository($project));
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml        = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml = new \SimpleXMLElement($xml);

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $project = ProjectTestBuilder::aProject()->build();

        $this->importer->importCompatRefXML(new ImportConfig(), $project, $simple_xml, $this->buildRepository($project));
    }

    private function buildRepository(Project $project): Repository
    {
        return new class ($project) implements Repository
        {
            private Project $project;

            public function __construct(Project $project)
            {
                $this->project = $project;
            }

            public function getSettingUrl(): string
            {
                return '';
            }

            public function setId(int $id): void
            {
                // TODO: Implement setId() method.
            }

            public function getId(): int
            {
                return 123;
            }

            public function getName(): string
            {
                return '';
            }

            public function getProject(): \Project
            {
                return $this->project;
            }

            public function getPublicPath(): string
            {
                return '';
            }

            public function getFullName(): string
            {
                return '';
            }

            public function getSystemPath(): string
            {
                return '';
            }

            public function isRepositoryCreated(): bool
            {
                return true;
            }

            public function getSvnUrl(): string
            {
                return '';
            }

            public function getSvnDomain(): string
            {
                return '';
            }

            public function getHtmlPath(): string
            {
                return '';
            }

            public function canBeDeleted(): bool
            {
                return true;
            }

            public function getBackupPath(): ?string
            {
                return '';
            }

            public function getSystemBackupPath(): string
            {
                return '';
            }

            public function getBackupFileName(): string
            {
                return '';
            }

            public function getDeletionDate(): ?int
            {
                return 0;
            }

            public function setDeletionDate(int $deletion_date): void
            {
                // TODO: Implement setDeletionDate() method.
            }

            public function isDeleted(): bool
            {
                return false;
            }
        };
    }
}
