<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\CLI\CLICommandsCollector;
use Tuleap\JiraImport\Project\ArtifactLinkType\ArtifactLinkTypeImporter;
use Tuleap\JiraImport\Project\CreateProjectFromJira;
use Tuleap\JiraImport\Project\CreateProjectFromJiraCommand;
use Tuleap\JiraImport\Project\Dashboard\RoadmapDashboardCreator;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackerBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NatureCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NatureValidator;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/vendor/autoload.php';

define('TULEAP_PLUGIN_JIRA_IMPORT', '1');

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class jira_importPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-jira_import', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\JiraImport\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CLICommandsCollector::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'cardwall', 'roadmap'];
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            CreateProjectFromJiraCommand::NAME,
            static function (): CreateProjectFromJiraCommand {
                $user_manager = UserManager::instance();

                $nature_dao              = new NatureDao();
                $nature_validator        = new NatureValidator($nature_dao);
                $artifact_link_usage_dao = new ArtifactLinksUsageDao();

                return new CreateProjectFromJiraCommand(
                    $user_manager,
                    new JiraProjectBuilder(),
                    new CreateProjectFromJira(
                        $user_manager,
                        TemplateFactory::build(),
                        new XMLFileContentRetriever(),
                        new XMLImportHelper($user_manager),
                        new JiraTrackerBuilder(),
                        new ArtifactLinkTypeImporter(
                            new TypePresenterFactory(
                                $nature_dao,
                                $artifact_link_usage_dao,
                            ),
                            new NatureCreator(
                                $nature_dao,
                                $nature_validator,
                            ),
                        ),
                        new PlatformConfigurationRetriever(
                            EventManager::instance()
                        ),
                        ProjectManager::instance(),
                        new UserRolesChecker(),
                        new RoadmapDashboardCreator()
                    )
                );
            }
        );
    }
}
