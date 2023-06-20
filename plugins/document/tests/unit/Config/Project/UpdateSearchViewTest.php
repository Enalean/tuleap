<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Project;

use Tuleap\Document\Tree\IExtractProjectFromVariables;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;

final class UpdateSearchViewTest extends TestCase
{
    public function testSearchViewIsUpdated(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->columns  = false;
        $assertions->criteria = false;

        $update = $this->getMockBuilder(UpdateSearchView::class)
            ->onlyMethods(['checkCsrfToken'])
            ->setConstructorArgs([
                new class ($project) implements IExtractProjectFromVariables {
                    public function __construct(private \Project $project)
                    {
                    }

                    public function getProject(array $variables): \Project
                    {
                        return $this->project;
                    }
                },
                new class ($assertions) implements IUpdateColumns {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveColumns(int $project_id, array $columns): void
                    {
                        assertEquals(101, $project_id);
                        assertEquals(["title", "status"], $columns);
                        $this->assertions->columns = true;
                    }
                },
                new class ($assertions) implements IUpdateCriteria {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveCriteria(int $project_id, array $criteria): void
                    {
                        assertEquals(101, $project_id);
                        assertEquals(["title", "description"], $criteria);
                        $this->assertions->criteria = true;
                    }
                },
            ])->getMock();

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        try {
            $update->process(
                HTTPRequestBuilder::get()
                    ->withParam('criteria', ["title", "description"])
                    ->withParam('columns', ["title", "status"])
                    ->build(),
                LayoutBuilder::buildWithInspector($layout_inspector),
                ['project_name' => 'acme']
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/plugins/document/testproject/admin-search'), $ex);
        }

        self::assertTrue($assertions->columns);
        self::assertTrue($assertions->criteria);
        self::assertEquals(
            [
                [
                    'level' => 'info',
                    'message' => 'Configuration of search view has been updated',
                ],
            ],
            $layout_inspector->getFeedback()
        );
    }

    public function testCsrfIsChecked(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->columns  = false;
        $assertions->criteria = false;

        $update = $this->getMockBuilder(UpdateSearchView::class)
            ->onlyMethods(['checkCsrfToken'])
            ->setConstructorArgs([
                new class ($project) implements IExtractProjectFromVariables {
                    public function __construct(private \Project $project)
                    {
                    }

                    public function getProject(array $variables): \Project
                    {
                        return $this->project;
                    }
                },
                new class ($assertions) implements IUpdateColumns {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveColumns(int $project_id, array $columns): void
                    {
                        $this->assertions->columns = true;
                    }
                },
                new class ($assertions) implements IUpdateCriteria {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveCriteria(int $project_id, array $criteria): void
                    {
                        $this->assertions->criteria = true;
                    }
                },
            ])->getMock();

        $exception = new \Exception();
        $update->method('checkCsrfToken')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $update->process(
            HTTPRequestBuilder::get()
                ->withParam('criteria', ["title", "description"])
                ->withParam('columns', ["title", "status"])
                ->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme']
        );

        self::assertFalse($assertions->columns);
        self::assertFalse($assertions->criteria);
    }

    public function testColumnsIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions          = new \stdClass();
        $assertions->columns = false;

        $update = $this->getMockBuilder(UpdateSearchView::class)
            ->onlyMethods(['checkCsrfToken'])
            ->setConstructorArgs([
                new class ($project) implements IExtractProjectFromVariables {
                    public function __construct(private \Project $project)
                    {
                    }

                    public function getProject(array $variables): \Project
                    {
                        return $this->project;
                    }
                },
                new class ($assertions) implements IUpdateColumns {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveColumns(int $project_id, array $columns): void
                    {
                        $this->assertions->columns = true;
                    }
                },
                new class () implements IUpdateCriteria {
                    public function saveCriteria(int $project_id, array $criteria): void
                    {
                    }
                },
            ])->getMock();

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        try {
            $update->process(
                HTTPRequestBuilder::get()
                    ->withParam('criteria', ["title", "description"])
                    ->withParam('columns', "warez")
                    ->build(),
                LayoutBuilder::buildWithInspector($layout_inspector),
                ['project_name' => 'acme']
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/plugins/document/testproject/admin-search'), $ex);
        }

        self::assertFalse($assertions->columns);
        self::assertEquals(
            [
                [
                    'level' => 'error',
                    'message' => 'Invalid request',
                ],
            ],
            $layout_inspector->getFeedback()
        );
    }

    public function testCriteriaIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->criteria = false;

        $update = $this->getMockBuilder(UpdateSearchView::class)
            ->onlyMethods(['checkCsrfToken'])
            ->setConstructorArgs([
                new class ($project) implements IExtractProjectFromVariables {
                    public function __construct(private \Project $project)
                    {
                    }

                    public function getProject(array $variables): \Project
                    {
                        return $this->project;
                    }
                },
                new class implements IUpdateColumns {
                    public function saveColumns(int $project_id, array $columns): void
                    {
                    }
                },
                new class ($assertions) implements IUpdateCriteria {
                    public function __construct(private object $assertions)
                    {
                    }

                    public function saveCriteria(int $project_id, array $criteria): void
                    {
                        $this->assertions->criteria = true;
                    }
                },
            ])->getMock();

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        try {
            $update->process(
                HTTPRequestBuilder::get()
                    ->withParam('criteria', "warez")
                    ->withParam('columns', ["title", "description"])
                    ->build(),
                LayoutBuilder::buildWithInspector($layout_inspector),
                ['project_name' => 'acme']
            );
        } catch (LayoutInspectorRedirection $ex) {
            self::assertEquals(new LayoutInspectorRedirection('/plugins/document/testproject/admin-search'), $ex);
        }

        self::assertFalse($assertions->criteria);
        self::assertEquals(
            [
                [
                    'level' => 'error',
                    'message' => 'Invalid request',
                ],
            ],
            $layout_inspector->getFeedback()
        );
    }
}
