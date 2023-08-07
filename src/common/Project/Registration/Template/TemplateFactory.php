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

use Project;
use Project_AccessException;
use ProjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\Registration\Template\Events\CollectCategorisedExternalTemplatesEvent;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;
use URLVerification;
use UserManager;

class TemplateFactory
{
    /**
     * @var array<string,TuleapTemplate>
     */
    private $templates;

    /**
     * @var array<string, TuleapTemplate>
     */
    private $external_templates;
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
        ProjectManager $project_manager,
        EventDispatcherInterface $event_dispatcher,
        private UserManager $user_manager,
        private URLVerification $url_verification,
    ) {
        $this->template_dao    = $template_dao;
        $this->templates       = [
            AgileALMTemplate::NAME => new AgileALMTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            ScrumTemplate::NAME    => new ScrumTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            KanbanTemplate::NAME   => new KanbanTemplate($glyph_finder, $project_xml_merger, $consistency_checker),
            IssuesTemplate::NAME   => new IssuesTemplate($glyph_finder, $consistency_checker, $event_dispatcher),
            EmptyTemplate::NAME    => new EmptyTemplate($glyph_finder),
        ];
        $this->project_manager = $project_manager;
        $this->glyph_finder    = $glyph_finder;

        $this->external_templates = self::getExternalTemplatesByName($event_dispatcher);
    }

    public static function build(): self
    {
        $event_manager  = \EventManager::instance();
        $plugin_factory = \PluginFactory::instance();

        return new self(
            new GlyphFinder(
                $event_manager
            ),
            new ProjectXMLMerger(),
            new ConsistencyChecker(
                new XMLFileContentRetriever(),
                $event_manager,
                new ServiceEnableForXmlImportRetriever($plugin_factory),
                $plugin_factory,
            ),
            new TemplateDao(),
            \ProjectManager::instance(),
            $event_manager,
            \UserManager::instance(),
            new URLVerification()
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

        if (isset($this->templates[$name]) && $this->templates[$name]->isAvailable()) {
            return $this->templates[$name];
        }

        if (isset($this->external_templates[$name]) && $this->external_templates[$name]->isAvailable()) {
            return $this->external_templates[$name];
        }
        throw new InvalidXMLTemplateNameException($name);
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
            if ((int) $project_template->getGroupId() === \Project::DEFAULT_TEMPLATE_PROJECT_ID || ! $this->userCanAccessTemplate($project_template)) {
                continue;
            }
            $company_templates[] = new CompanyTemplate($project_template, $this->glyph_finder);
        }
        return $company_templates;
    }

    public function getCategorisedExternalTemplates(): array
    {
        $categorised_templates = [];
        foreach ($this->external_templates as $template) {
            if ($template->isAvailable()) {
                $categorised_templates[] = $template;
            }
        }

        return $categorised_templates;
    }

    private static function getExternalTemplatesByName(EventDispatcherInterface $event_dispatcher): array
    {
        $event              = $event_dispatcher->dispatch(new CollectCategorisedExternalTemplatesEvent());
        $external_templates = $event->getCategorisedTemplates();
        $templates_by_name  = [];

        foreach ($external_templates as $template) {
            $templates_by_name[$template->getId()] = $template;
        }

        return $templates_by_name;
    }

    private function userCanAccessTemplate(Project $project_template): bool
    {
        try {
            return $this->url_verification->userCanAccessProject($this->user_manager->getCurrentUser(), $project_template);
        } catch (Project_AccessException $exception) {
            return false;
        }
    }
}
