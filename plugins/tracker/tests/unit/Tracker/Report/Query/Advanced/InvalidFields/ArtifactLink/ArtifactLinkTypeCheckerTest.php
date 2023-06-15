<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\VisibleTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\WithForwardLink;

final class ArtifactLinkTypeCheckerTest extends TestCase
{
    public function testValidType(): void
    {
        $checker = new ArtifactLinkTypeChecker(new class implements VisibleTypesRetriever {
            public function getOnlyVisibleTypes(): array
            {
                return [
                    new TypeIsChildPresenter(),
                ];
            }
        });

        $this->expectNotToPerformAssertions();

        $checker->checkArtifactLinkTypeIsValid(new WithForwardLink(null, '_is_child'));
    }

    public function testInvalidType(): void
    {
        $checker = new ArtifactLinkTypeChecker(new class implements VisibleTypesRetriever {
            public function getOnlyVisibleTypes(): array
            {
                return [];
            }
        });

        $this->expectException(InvalidArtifactLinkTypeException::class);

        $checker->checkArtifactLinkTypeIsValid(new WithForwardLink(null, '_is_child'));
    }

    public function testNoTypeIsValidType(): void
    {
        $checker = new ArtifactLinkTypeChecker(new class implements VisibleTypesRetriever {
            public function getOnlyVisibleTypes(): array
            {
                return [];
            }
        });

        $this->expectNotToPerformAssertions();

        $checker->checkArtifactLinkTypeIsValid(new WithForwardLink(null, null));
    }
}
