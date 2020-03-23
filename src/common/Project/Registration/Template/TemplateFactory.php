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

use ProjectManager;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;

class TemplateFactory
{
    /**
     * @var array<string,TuleapTemplate>
     */
    private $templates;
    /**
     * @var TemplateDao
     */
    private $template_dao;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;

    public function __construct(
        GlyphFinder $glyph_finder,
        ProjectXMLMerger $project_xml_merger,
        ConsistencyChecker $consistency_checker,
        TemplateDao $template_dao,
        ProjectManager $project_manager
    ) {
        $this->template_dao    = $template_dao;
        $this->templates       = [
            AgileALMTemplate::NAME => new AgileALMTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            ScrumTemplate::NAME => new ScrumTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            KanbanTemplate::NAME => new KanbanTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            IssuesTemplate::NAME => new IssuesTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            EmptyTemplate::NAME => new EmptyTemplate($glyph_finder),
        ];
        $this->project_manager = $project_manager;
        $this->glyph_finder    = $glyph_finder;
    }

    public static function build(): self
    {
        return new self(
            new GlyphFinder(
                \EventManager::instance()
            ),
            new ProjectXMLMerger(),
            new ConsistencyChecker(
                new XMLFileContentRetriever(),
                \EventManager::instance(),
                new ServiceEnableForXmlImportRetriever(\PluginFactory::instance())
            ),
            new TemplateDao(),
            \ProjectManager::instance()
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

        if (count($templates) === 0) {
            $templates[] = new EmptyTemplate($this->glyph_finder);
        }

        return $templates;
    }

    /**
     * @throws InvalidTemplateException
     */
    public function getTemplate(string $name): TuleapTemplate
    {
        if ($name === EmptyTemplate::NAME && isset($this->templates[$name])) {
            return $this->templates[EmptyTemplate::NAME];
        }
        if (! isset($this->templates[$name]) || ! $this->templates[$name]->isAvailable()) {
            throw new InvalidXMLTemplateNameException();
        }
        return $this->templates[$name];
    }

    public function recordUsedTemplate(\Project $project, ProjectTemplate $template): \Project
    {
        $this->template_dao->saveTemplate($project, $template->getId());
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

    /**
     * @return CompanyTemplate[]
     */
    public function getCompanyTemplateList(): array
    {
        $company_templates = [];
        $project_templates = $this->project_manager->getSiteTemplates();

        foreach ($project_templates as $project_template) {
            if ((int) $project_template->getGroupId() === \Project::ADMIN_PROJECT_ID) {
                continue;
            }
            $company_templates[] = new CompanyTemplate($project_template, $this->glyph_finder);
        }
        return $company_templates;
    }

    public function getDefaultProjectTemplate(): ?DefaultProjectTemplate
    {
        $default_project_template = $this->project_manager->getProject(\Project::ADMIN_PROJECT_ID);
        if (!$default_project_template->isSuspended() && !$default_project_template->isDeleted()) {
            return new DefaultProjectTemplate($default_project_template, $this->glyph_finder);
        }
        return null;
    }
}
