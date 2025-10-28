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

namespace Tuleap\Git\GlobalAdmin;

use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\TemplateRendererStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GlobalAdminTabsRendererTest extends TestCase
{
    private TemplateRendererStub $template_renderer;
    private EventDispatcherStub $event_dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->template_renderer = new TemplateRendererStub();
        $this->event_dispatcher  = EventDispatcherStub::withIdentityCallback();
    }

    private function render(): void
    {
        $project  = ProjectTestBuilder::aProject()
            ->withId(304)
            ->build();
        $renderer = new GlobalAdminTabsRenderer(
            $this->template_renderer,
            $this->event_dispatcher
        );
        $renderer->renderTabs($project, 'a_tab_name');
    }

    public function testItRendersTheTabsForTheGitGlobalAdministrationPages(): void
    {
        $handled_event          = false;
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (GitAdminGetExternalPanePresenters $event) use (&$handled_event) {
                $handled_event = true;
                $event->addExternalPanePresenter(
                    new AdminExternalPanePresenter('external_tab', '/plugin/external/git-global-admin', false)
                );
                return $event;
            }
        );

        $this->render();
        self::assertTrue($this->template_renderer->has_rendered_something);
        self::assertTrue($handled_event);
    }
}
