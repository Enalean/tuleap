<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TypeSelectorRendererTest extends TestCase
{
    use TemporaryTestDirectory;

    public function testItAddsParentOptionWhenThereIsNoParentSelectorAtAll(): void
    {
        $renderer = new TypeSelectorRenderer(
            new class implements IRetrieveAllUsableTypesInProject {
                /** @return TypePresenter[] */
                public function getAllUsableTypesInProject(\Project $project): array
                {
                    return [
                        new TypeIsChildPresenter(),
                        TypePresenter::buildVisibleType('fixed_by', 'Fixed by', 'Fix'),
                    ];
                }
            },
            TemplateRendererFactoryBuilder::get()
                ->withPath($this->getTmpDir())
                ->build()
                ->getRenderer(TRACKER_TEMPLATE_DIR)
        );

        $artifact = ArtifactTestBuilder::anArtifact(101)
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $parent_selector = null;

        $html = $renderer->renderToString(
            $artifact,
            '_is_child',
            'artifact',
            $parent_selector
        );

        self::assertStringContainsString('<option selected value="_is_child">', $html);
        self::assertStringContainsString('<option  value="_is_parent">', $html);
        self::assertStringContainsString('<option  value="fixed_by">', $html);
    }

    public function testItAddsParentOptionWhenTheParentSelectorIsNotDisplayed(): void
    {
        $renderer = new TypeSelectorRenderer(
            new class implements IRetrieveAllUsableTypesInProject {
                /** @return TypePresenter[] */
                public function getAllUsableTypesInProject(\Project $project): array
                {
                    return [
                        new TypeIsChildPresenter(),
                        TypePresenter::buildVisibleType('fixed_by', 'Fixed by', 'Fix'),
                    ];
                }
            },
            TemplateRendererFactoryBuilder::get()
                ->withPath($this->getTmpDir())
                ->build()
                ->getRenderer(TRACKER_TEMPLATE_DIR)
        );

        $artifact = ArtifactTestBuilder::anArtifact(101)
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $parent_selector = new PossibleParentSelector(
            UserTestBuilder::anAnonymousUser()->build(),
            $artifact->getTracker(),
            0,
            0
        );
        $parent_selector->disableSelector();

        $html = $renderer->renderToString(
            $artifact,
            '_is_child',
            'artifact',
            $parent_selector
        );

        self::assertStringContainsString('<option selected value="_is_child">', $html);
        self::assertStringContainsString('<option  value="_is_parent">', $html);
        self::assertStringContainsString('<option  value="fixed_by">', $html);
    }

    public function testItDoesNotAddParentOptionWhenThereIsADisplayedParentSelector(): void
    {
        $renderer = new TypeSelectorRenderer(
            new class implements IRetrieveAllUsableTypesInProject {
                /** @return TypePresenter[] */
                public function getAllUsableTypesInProject(\Project $project): array
                {
                    return [
                        new TypeIsChildPresenter(),
                        TypePresenter::buildVisibleType('fixed_by', 'Fixed by', 'Fix'),
                    ];
                }
            },
            TemplateRendererFactoryBuilder::get()
                ->withPath($this->getTmpDir())
                ->build()
                ->getRenderer(TRACKER_TEMPLATE_DIR)
        );

        $artifact = ArtifactTestBuilder::anArtifact(101)
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $parent_selector = new PossibleParentSelector(
            UserTestBuilder::anAnonymousUser()->build(),
            $artifact->getTracker(),
            0,
            0
        );

        $html = $renderer->renderToString(
            $artifact,
            '_is_child',
            'artifact',
            $parent_selector
        );

        self::assertStringContainsString('<option selected value="_is_child">', $html);
        self::assertStringNotContainsString('<option  value="_is_parent">', $html);
        self::assertStringContainsString('<option  value="fixed_by">', $html);
    }

    public function testItDoesNotAddParentOptionWhenIsChildIsNotPresent(): void
    {
        $renderer = new TypeSelectorRenderer(
            new class implements IRetrieveAllUsableTypesInProject {
                /** @return TypePresenter[] */
                public function getAllUsableTypesInProject(\Project $project): array
                {
                    return [
                        TypePresenter::buildVisibleType('fixed_by', 'Fixed by', 'Fix'),
                    ];
                }
            },
            TemplateRendererFactoryBuilder::get()
                ->withPath($this->getTmpDir())
                ->build()
                ->getRenderer(TRACKER_TEMPLATE_DIR)
        );

        $artifact = ArtifactTestBuilder::anArtifact(101)
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $parent_selector = new PossibleParentSelector(
            UserTestBuilder::anAnonymousUser()->build(),
            $artifact->getTracker(),
            0,
            0
        );

        $html = $renderer->renderToString(
            $artifact,
            '_is_child',
            'artifact',
            $parent_selector
        );

        self::assertStringNotContainsString('<option  value="_is_parent">', $html);
        self::assertStringContainsString('<option  value="fixed_by">', $html);
    }
}
