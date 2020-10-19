<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_ArtifactNodeTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $artifact;
    private $data;
    private $node;

    protected function setUp(): void
    {
        $this->artifact = new Artifact(9787, 123, null, 10, null);
        $this->data     = ['somekey' => 'somevalue'];
        $this->node     = new ArtifactNode($this->artifact, $this->data);
    }

    public function testItHoldsTheArtifact(): void
    {
        $this->assertSame($this->artifact, $this->node->getArtifact());
        $this->assertSame($this->artifact, $this->node->getObject());
    }

    public function testItCanHoldData(): void
    {
        $this->assertSame($this->data, $this->node->getData());
    }

    public function testItUsesTheIdOfTheArtifact(): void
    {
        $this->assertEquals($this->artifact->getId(), $this->node->getId());
    }

    public function testItCallsTheSuperConstructor(): void
    {
        $this->assertIsArray($this->node->getChildren(), 'getChildren should have been initialized to array()');
    }
}
