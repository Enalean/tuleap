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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ArtifactLink\Type\RetrieveSystemTypePresenterStub;

#[DisableReturnValueGenerationForTestDoubles]
final class TypePresenterFactoryTest extends TestCase
{
    private TypeDao&MockObject $type_dao;
    private ArtifactLinksUsageDao&MockObject $artifact_link_usage_dao;
    private TypePresenterFactory $type_presenter_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->type_dao                = $this->createMock(TypeDao::class);
        $this->artifact_link_usage_dao = $this->createMock(ArtifactLinksUsageDao::class);

        $this->type_presenter_factory = new TypePresenterFactory($this->type_dao, $this->artifact_link_usage_dao, RetrieveSystemTypePresenterStub::build());
    }

    public function testGetsAnEnabledTypeInAProjectFromItsShortname(): void
    {
        $this->artifact_link_usage_dao->method('isTypeDisabledInProject')->willReturn(false);
        $this->type_dao->method('getFromShortname')->willReturn(
            ['shortname' => 'some_shortname', 'forward_label' => 'Label', 'reverse_label' => 'Label R']
        );

        $type = $this->type_presenter_factory->getTypeEnabledInProjectFromShortname(ProjectTestBuilder::aProject()->build(), 'some_shortname');

        self::assertEquals(new TypePresenter('some_shortname', 'Label', 'Label R', true), $type);
    }

    public function testGetsNothingWhenSearchingADisabledTypeFromItsShortnameAndOnlyWantingEnabledOnes(): void
    {
        $this->artifact_link_usage_dao->method('isTypeDisabledInProject')->willReturn(true);

        $type = $this->type_presenter_factory->getTypeEnabledInProjectFromShortname(
            ProjectTestBuilder::aProject()->build(),
            'some_disabled_shortname'
        );

        self::assertNull($type);
    }
}
