<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use Project;
use TemplateRendererFactory;
use Tuleap\Layout\HeaderConfigurationBuilder;

class TrackerCreatorXmlErrorDisplayer
{
    /**
     * @var \TrackerManager
     */
    private $tracker_manager;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;

    public function __construct(
        \TrackerManager $tracker_manager,
        TemplateRendererFactory $template_renderer_factory,
    ) {
        $this->tracker_manager           = $tracker_manager;
        $this->template_renderer_factory = $template_renderer_factory;
    }

    public static function build(): self
    {
        return new self(
            new \TrackerManager(),
            TemplateRendererFactory::build()
        );
    }

    public function displayErrors(Project $project, array $parse_errors, array $xml_file): void
    {
        $breadcrumbs = [
            [
                'title' => 'Create a new tracker',
                'url'   => TRACKER_BASE_URL . '/?group_id=' . urlencode($project->group_id) . '&amp;func=create',
            ],
        ];
        $title       = 'Trackers';
        $this->tracker_manager->displayHeader(
            $project,
            $title,
            $breadcrumbs,
            [],
            HeaderConfigurationBuilder::get($title)
                ->inProject($project, \trackerPlugin::SERVICE_SHORTNAME)
                ->build()
        );
        $renderer = $this->template_renderer_factory->getRenderer(
            __DIR__ . '/../../../templates/tracker-creation'
        );

        $presenter_builder = new TrackerCreatorXmlErrorPresenterBuilder();

        $errors    = $presenter_builder->buildErrors($parse_errors);
        $presenter = $presenter_builder->buildErrorLineDiff($xml_file, $errors);

        echo $renderer->renderToString('xml-invalid-tracker', $presenter);

        $this->tracker_manager->displayFooter($project);
        exit;
    }
}
