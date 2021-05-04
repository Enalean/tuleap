<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

class MethodBasedOnLinksCountTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_ArtifactLink
     */
    private $links_field;
    /**
     * @var MethodBasedOnLinksCount
     */
    private $method;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticProgressDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao         = \Mockery::mock(SemanticProgressDao::class);
        $this->links_field = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class, ['getId' => 1003]);
        $this->method      = new MethodBasedOnLinksCount(
            $this->dao,
            $this->links_field,
            '_is_child'
        );
    }

    public function testItReturnsTrueIfFieldIsUsed(): void
    {
        $this->assertTrue($this->method->isFieldUsedInComputation($this->links_field));
    }

    public function testItReturnsFalseIfFieldIsNotUsed(): void
    {
        $random_field = \Mockery::mock(\Tracker_FormElement_Field_Date::class, ['getId' => 1007]);

        $this->assertFalse($this->method->isFieldUsedInComputation($random_field));
    }

    public function testItDoesNotComputeProgressionYet(): void
    {
        $progression_result = $this->method->computeProgression(
            \Mockery::mock(Artifact::class),
            \Mockery::mock(\PFUser::class)
        );

        $this->assertEquals(
            new ProgressionResult(
                null,
                'Implementation of child count based semantic progress is ongoing. You cannot use it yet.'
            ),
            $progression_result
        );
    }

    public function testItIsConfigured(): void
    {
        $this->assertTrue($this->method->isConfigured());
    }

    public function testItDoesNotExportsToRESTYet(): void
    {
        self::assertNull(
            $this->method->exportToREST(\Mockery::mock(\PFUser::class)),
        );
    }
    public function testItDoesNotExportToXMLYet(): void
    {
        $xml_data = '<?xml version="1.0" encoding="UTF-8"?><semantics/>';
        $root     = new \SimpleXMLElement($xml_data);

        $this->method->exportToXMl($root, [
            'F201' => 1001
        ]);

        $this->assertCount(0, $root->children());
    }

    public function testDoesNotSaveItsConfigurationYet(): void
    {
        $tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);

        $this->dao->shouldReceive('save')->never();

        $this->assertFalse($this->method->saveSemanticForTracker($tracker));
    }

    public function testItDoesNotDeleteItsConfigurationYet(): void
    {
        $tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);

        $this->dao->shouldReceive('delete')->never();

        $this->assertFalse(
            $this->method->deleteSemanticForTracker($tracker)
        );
    }
}
