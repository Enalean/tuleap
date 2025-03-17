<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EditorWithReverseLinksBuilderTest extends TestCase
{
    private const CURRENT_ARTIFACT_ID = 891;

    private function build(): EditorWithReverseLinksPresenter
    {
        $builder = new EditorWithReverseLinksBuilder();
        return $builder->build(
            ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)->build()
        );
    }

    public function testItBuilds(): void
    {
        $presenter = $this->build();
        self::assertSame(self::CURRENT_ARTIFACT_ID, $presenter->current_artifact_id);
    }
}
