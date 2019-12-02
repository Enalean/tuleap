<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;

class TemplateFactory
{
    /**
     * @var ProjectTemplate[]
     */
    private $templates;
    /**
     * @var TemplateDao
     */
    private $template_dao;

    public function __construct(GlyphFinder $glyph_finder, ProjectXMLMerger $project_xml_merger, ConsistencyChecker $consistency_checker, TemplateDao $template_dao)
    {
        $this->template_dao = $template_dao;
        $this->templates = [
            ScrumTemplate::NAME => new ScrumTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
        ];
    }

    public static function build(): self
    {
        return new self(
            new GlyphFinder(
                \EventManager::instance()
            ),
            new ProjectXMLMerger(),
            new ConsistencyChecker(
                \ServiceManager::instance(),
                new XMLFileContentRetriever()
            ),
            new TemplateDao()
        );
    }

    /**
     * @return ProjectTemplate[]
     */
    public function getValidTemplates(): array
    {
        $templates = [];
        foreach ($this->templates as $template) {
            if ($template->isAvailable()) {
                $templates[] = $template;
            }
        }
        return $templates;
    }

    /**
     * @throws InvalidTemplateException
     */
    public function getTemplate(string $name): ProjectTemplate
    {
        if (! isset($this->templates[$name]) || ! $this->templates[$name]->isAvailable()) {
            throw new InvalidXMLTemplateNameException();
        }
        return $this->templates[$name];
    }

    public function recordUsedTemplate(\Project $project, ProjectTemplate $template): \Project
    {
        $this->template_dao->saveTemplate($project, $template->getName());
        return $project;
    }

    public function getTemplateForProject(\Project $project): ?ProjectTemplate
    {
        try {
            $template_name = $this->template_dao->getTemplateForProject($project);
            if ($template_name === false) {
                return null;
            }
            return $this->getTemplate($template_name);
        } catch (InvalidTemplateException $exception) {
        }
        return null;
    }
}
