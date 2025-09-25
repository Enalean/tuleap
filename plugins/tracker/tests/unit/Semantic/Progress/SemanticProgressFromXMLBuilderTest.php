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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticProgressFromXMLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticProgressFromXMLBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new SemanticProgressFromXMLBuilder(
            $this->createMock(SemanticProgressDao::class)
        );
    }

    public function testBuildsSemanticProgressBasedOnEffortFromXML(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <total_effort_field REF="F201"/>
              <remaining_effort_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
                'F202' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
            ],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticProgress::class, $semantic);

        $this->assertEquals(
            MethodBasedOnEffort::getMethodName(),
            $semantic->getComputationMethod()::getMethodName()
        );
    }

    public function testItReturnsNullWhenTotalEffortFieldHasNoREFAttribute(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <total_effort_field/>
              <remaining_effort_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
                'F202' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
            ],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNull($semantic);
    }

    public function testItReturnsNullWhenRemainingEffortFieldHasNoREFAttribute(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <total_effort_field REF="F201"/>
              <remaining_effort_field/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
                'F202' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
            ],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNull($semantic);
    }

    public function testItReturnsNullWhenTotalEffortFieldREFIsNotFoundInMapping(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <total_effort_field REF="F201"/>
              <remaining_effort_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F202' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
            ],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNull($semantic);
    }

    public function testItReturnsNullWhenRemainingEffortFieldREFIsNotFoundInMapping(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <total_effort_field REF="F201"/>
              <remaining_effort_field REF="F202"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [
                'F201' => $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class),
            ],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNull($semantic);
    }

    public function testBuildsSemanticProgressBasedOnLinksCountFromXML(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <artifact_link_type shortname="_is_child"/>
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNotNull($semantic);
        $this->assertInstanceOf(SemanticProgress::class, $semantic);

        $this->assertEquals(
            MethodBasedOnLinksCount::getMethodName(),
            $semantic->getComputationMethod()::getMethodName()
        );
    }

    public function testItReturnsNullWhenArtifactLinkTypeNodeHasNoShortnameAttribute(): void
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <semantic type="progress">
              <artifact_link_type />
            </semantic>
        '
        );

        $all_semantics_xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>');

        $semantic = $this->builder->getInstanceFromXML(
            $xml,
            $all_semantics_xml,
            [],
            TrackerTestBuilder::aTracker()->build(),
            []
        );

        $this->assertNull($semantic);
    }
}
