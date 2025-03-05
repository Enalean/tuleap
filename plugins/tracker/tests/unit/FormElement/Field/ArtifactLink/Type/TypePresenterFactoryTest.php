<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TypePresenterFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TypeDao
     */
    private $type_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinksUsageDao
     */
    private $artifact_link_usage_dao;
    /**
     * @var TypePresenterFactory
     */
    private $type_presenter_factory;

    protected function setUp(): void
    {
        $this->type_dao                = \Mockery::mock(TypeDao::class);
        $this->artifact_link_usage_dao = \Mockery::mock(ArtifactLinksUsageDao::class);

        $this->type_presenter_factory = new TypePresenterFactory($this->type_dao, $this->artifact_link_usage_dao);
    }

    public function testGetsAnEnabledTypeInAProjectFromItsShortname(): void
    {
        $this->artifact_link_usage_dao->shouldReceive('isTypeDisabledInProject')->andReturn(false);
        $this->type_dao->shouldReceive('getFromShortname')->andReturn(
            ['shortname' => 'some_shortname', 'forward_label' => 'Label', 'reverse_label' => 'Label R']
        );

        $type = $this->type_presenter_factory->getTypeEnabledInProjectFromShortname(ProjectTestBuilder::aProject()->build(), 'some_shortname');

        $this->assertEquals(new TypePresenter('some_shortname', 'Label', 'Label R', true), $type);
    }

    public function testGetsNothingWhenSearchingADisabledTypeFromItsShortnameAndOnlyWantingEnabledOnes(): void
    {
        $this->artifact_link_usage_dao->shouldReceive('isTypeDisabledInProject')->andReturn(true);

        $type = $this->type_presenter_factory->getTypeEnabledInProjectFromShortname(
            ProjectTestBuilder::aProject()->build(),
            'some_disabled_shortname'
        );

        $this->assertNull($type);
    }
}
