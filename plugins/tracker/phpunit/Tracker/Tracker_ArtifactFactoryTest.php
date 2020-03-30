<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MissingNamespace
class Tracker_ArtifactFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::spy(\Tracker_ArtifactDao::class);
        $this->artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact_factory->shouldReceive('getDao')->andReturns($this->dao);
    }

    public function testItFetchArtifactsTitlesFromDb(): void
    {
        $art12 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art12->shouldReceive('getId')->andReturn(12);

        $art30 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art30->shouldReceive('getId')->andReturn(30);

        $artifacts = [$art12, $art30];

        $this->dao->shouldReceive('getTitles')->with(array(12, 30))->once()->andReturns(\TestHelper::emptyDar());

        $this->artifact_factory->setTitles($artifacts);
    }

    public function testItSetTheTitlesToTheArtifact(): void
    {
        $art24 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art24->shouldReceive('getId')->andReturn(24);

        $artifacts = array($art24);

        $this->dao->shouldReceive('getTitles')->andReturns(\TestHelper::arrayToDar(array('id' => 24, 'title' => 'Zoum')));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24->getTitle());
    }

    public function testItSetTheTitlesWhenThereAreSeveralArtifacts(): void
    {
        $art24 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art24->shouldReceive('getId')->andReturn(24);

        $art32 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art32->shouldReceive('getId')->andReturn(32);

        $artifacts = array($art24, $art32);

        $this->dao->shouldReceive('getTitles')->andReturns(\TestHelper::arrayToDar(array('id' => 24, 'title' => 'Zoum'), array('id' => 32, 'title' => 'Zen')));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24->getTitle());
        $this->assertEquals('Zen', $art32->getTitle());
    }

    public function testItSetTheTitlesWhenThereAreSeveralTimeTheSameArtifact(): void
    {
        $art24_1 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art24_1->shouldReceive('getId')->andReturn(24);
        $art24_2 = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $art24_2->shouldReceive('getId')->andReturn(24);

        $artifacts = array($art24_1, $art24_2);

        $this->dao->shouldReceive('getTitles')->andReturns(\TestHelper::arrayToDar(array('id' => 24, 'title' => 'Zoum')));

        $this->artifact_factory->setTitles($artifacts);

        $this->assertEquals('Zoum', $art24_1->getTitle());
        $this->assertEquals('Zoum', $art24_2->getTitle());
    }
}
