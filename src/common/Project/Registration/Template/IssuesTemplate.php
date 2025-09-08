<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use ForgeConfig;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\Service\XML\XMLService;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\XMLProject;

class IssuesTemplate implements TuleapTemplate
{
    public const NAME = 'issues';

    /**
     * @var string
     */
    private $title;
    /**s
     * @var string
     */
    private $description;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var ConsistencyChecker
     */
    private $consistency_checker;
    /**
     * @var bool
     */
    private $available;
    /**
     * @var string
     */
    private $xml_path;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        GlyphFinder $glyph_finder,
        ConsistencyChecker $consistency_checker,
        EventDispatcherInterface $dispatcher,
    ) {
        $this->title               = _('Issue tracking');
        $this->description         = _('Trace and Track all types of activities.');
        $this->glyph_finder        = $glyph_finder;
        $this->consistency_checker = $consistency_checker;
        $this->dispatcher          = $dispatcher;
    }

    /**
     * Actual XML file is generated "on the fly" in order to guaranty the consistency between the AgileDashboard
     * "Start Scrum" template and the "Project Creation" Scrum template
     */
    #[\Override]
    public function getXMLPath(): string
    {
        if ($this->xml_path === null) {
            $base_dir = ForgeConfig::getCacheDir() . '/issues_template';
            if (! is_dir($base_dir) && ! mkdir($base_dir, 0755) && ! is_dir($base_dir)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $base_dir));
            }
            $this->xml_path = ForgeConfig::getCacheDir() . '/issues_template/project.xml';

            $project = (new XMLProject(
                'issuetracking',
                'Issue Tracking',
                'Template used to create a project providing an issue tracking, the best way to start your project with Tuleap.',
                'public'
            ))
                ->withService(XMLService::buildEnabled('summary'))
                ->withService(XMLService::buildEnabled('admin'))
                ->withService(XMLService::buildEnabled('docman'))
                ->withService(XMLService::buildEnabled('plugin_git'))
                ->withService(XMLService::buildEnabled('plugin_tracker'))
                ->withService(XMLService::buildDisabled('plugin_agiledashboard'))
                ->withService(XMLService::buildDisabled('plugin_svn'))
                ->withService(XMLService::buildDisabled('file'))
                ->withService(XMLService::buildDisabled('hudson'))
                ->withService(XMLService::buildDisabled('plugin_baseline'))
                ->withService(XMLService::buildDisabled('plugin_mediawiki'))
                ->withService(XMLService::buildDisabled('plugin_testmanagement'))
                ->withService(XMLService::buildDisabled('plugin_program_management'))
                ->withDashboards(...(new IssuesTemplateDashboardDefinition($this->dispatcher))->getDashboards());

            $template = $project->export();

            $this->dispatcher->dispatch(new DefineIssueTemplateEvent($template));

            $template->asXML($this->xml_path);
        }

        return $this->xml_path;
    }

    #[\Override]
    public function getTitle(): string
    {
        return $this->title;
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->description;
    }

    #[\Override]
    public function getGlyph(): Glyph
    {
        return $this->glyph_finder->get(self::NAME);
    }

    #[\Override]
    public function getId(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function isAvailable(): bool
    {
        if ($this->available === null) {
            $this->available = $this->consistency_checker->areAllServicesAvailable(
                $this->getXMLPath(),
                ['graphontrackersv5']
            );
        }

        return $this->available;
    }

    #[\Override]
    public function isBuiltIn(): bool
    {
        return true;
    }
}
