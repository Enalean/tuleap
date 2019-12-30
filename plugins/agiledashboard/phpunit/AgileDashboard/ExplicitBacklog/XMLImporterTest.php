<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class XMLImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLImporter
     */
    private $importer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao      = Mockery::mock(ExplicitBacklogDao::class);
        $this->importer = new XMLImporter($this->dao);
    }

    public function testItDoesNothingIfAdminNodeIsNotInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard><plannings/></agiledashboard>');

        $this->dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');

        $this->importer->importConfiguration($xml, 101);
    }

    public function testItDoesNothingIfExplicitBacklogIsFalseInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard>
                <admin>
                    <scrum>
                        <explicit_backlog is_used="false"/>
                    </scrum>
                </admin>
                <plannings/>
            </agiledashboard>
        ');

        $this->dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');

        $this->importer->importConfiguration($xml, 101);
    }

    public function testItSetsExplicitBacklogInXMLImport(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard>
                <admin>
                    <scrum>
                        <explicit_backlog is_used="1"/>
                    </scrum>
                </admin>
                <plannings/>
            </agiledashboard>
        ');

        $this->dao->shouldReceive('setProjectIsUsingExplicitBacklog')->with(101)->once();

        $this->importer->importConfiguration($xml, 101);
    }
}
