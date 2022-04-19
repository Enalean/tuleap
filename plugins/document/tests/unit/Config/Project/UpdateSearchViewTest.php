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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

class UpdateSearchViewTest extends TestCase
{
    public function testSearchViewIsUpdated(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->columns  = false;
        $assertions->criteria = false;

        $update = $this->createPartialMock(UpdateSearchView::class, ['checkCsrfToken']);
        $update->__construct(
            new class ($project) implements IExtractProjectFromVariables {
                public function __construct(private $project)
                {
                }

                public function getProject(array $variables): \Project
                {
                    return $this->project;
                }
            },
            new class ($assertions) implements IUpdateColumns {
                public function __construct(private $assertions)
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
                public function __construct(private $assertions)
                {
                }

                public function saveCriteria(int $project_id, array $criteria): void
                {
                    assertEquals(101, $project_id);
                    assertEquals(["title", "description"], $criteria);
                    $this->assertions->criteria = true;
                }
            }
        );

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        $update->process(
            HTTPRequestBuilder::get()
                ->withParam('criteria', ["title", "description"])
                ->withParam('columns', ["title", "status"])
                ->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            ['project_name' => 'acme']
        );

        assertTrue($assertions->columns);
        assertTrue($assertions->criteria);
        assertEquals(
            [
                [
                    'level' => 'info',
                    'message' => 'Configuration of search view has been updated',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        assertEquals('/plugins/document/testproject/admin-search', $layout_inspector->getRedirectUrl());
    }

    public function testCsrfIsChecked(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->columns  = false;
        $assertions->criteria = false;

        $update = $this->createPartialMock(UpdateSearchView::class, ['checkCsrfToken']);
        $update->__construct(
            new class ($project) implements IExtractProjectFromVariables {
                public function __construct(private $project)
                {
                }

                public function getProject(array $variables): \Project
                {
                    return $this->project;
                }
            },
            new class ($assertions) implements IUpdateColumns {
                public function __construct(private $assertions)
                {
                }

                public function saveColumns(int $project_id, array $columns): void
                {
                    $this->assertions->columns = true;
                }
            },
            new class ($assertions) implements IUpdateCriteria {
                public function __construct(private $assertions)
                {
                }

                public function saveCriteria(int $project_id, array $criteria): void
                {
                    $this->assertions->criteria = true;
                }
            }
        );

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

        assertFalse($assertions->columns);
        assertFalse($assertions->criteria);
    }

    public function testColumnsIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions          = new \stdClass();
        $assertions->columns = false;

        $update = $this->createPartialMock(UpdateSearchView::class, ['checkCsrfToken']);
        $update->__construct(
            new class ($project) implements IExtractProjectFromVariables {
                public function __construct(private $project)
                {
                }

                public function getProject(array $variables): \Project
                {
                    return $this->project;
                }
            },
            new class ($assertions) implements IUpdateColumns {
                public function __construct(private $assertions)
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
            }
        );

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        $update->process(
            HTTPRequestBuilder::get()
                ->withParam('criteria', ["title", "description"])
                ->withParam('columns', "warez")
                ->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            ['project_name' => 'acme']
        );

        assertFalse($assertions->columns);
        assertEquals(
            [
                [
                    'level' => 'error',
                    'message' => 'Invalid request',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        assertEquals('/plugins/document/testproject/admin-search', $layout_inspector->getRedirectUrl());
    }

    public function testCriteriaIsInvalid(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->build();

        $assertions           = new \stdClass();
        $assertions->criteria = false;

        $update = $this->createPartialMock(UpdateSearchView::class, ['checkCsrfToken']);
        $update->__construct(
            new class ($project) implements IExtractProjectFromVariables {
                public function __construct(private $project)
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
                public function __construct(private $assertions)
                {
                }

                public function saveCriteria(int $project_id, array $criteria): void
                {
                    $this->assertions->criteria = true;
                }
            }
        );

        $update->method('checkCsrfToken');

        $layout_inspector = new LayoutInspector();

        $update->process(
            HTTPRequestBuilder::get()
                ->withParam('criteria', "warez")
                ->withParam('columns', ["title", "description"])
                ->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            ['project_name' => 'acme']
        );

        assertFalse($assertions->criteria);
        assertEquals(
            [
                [
                    'level' => 'error',
                    'message' => 'Invalid request',
                ],
            ],
            $layout_inspector->getFeedback()
        );
        assertEquals('/plugins/document/testproject/admin-search', $layout_inspector->getRedirectUrl());
    }
}
