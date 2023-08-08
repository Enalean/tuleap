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
use Tuleap\JiraImport\Project\ReplayCreateProjectFromJiraCommand;
use Tuleap\JiraImport\Project\ReplayImportCommand;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\ArtifactLinkTypeConverter;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraTrackerBuilder;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserRolesChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeValidator;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/vendor/autoload.php';
require_once __DIR__ . '/../../projectmilestones/vendor/autoload.php';

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

    public function getDependencies(): array
    {
        return ['tracker', 'agiledashboard', 'cardwall', 'roadmap', 'projectmilestones'];
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            CreateProjectFromJiraCommand::NAME,
            static function (): CreateProjectFromJiraCommand {
                $user_manager = UserManager::instance();

                $nature_dao              = new TypeDao();
                $nature_validator        = new TypeValidator($nature_dao);
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
                            new ArtifactLinkTypeConverter(
                                new TypePresenterFactory(
                                    $nature_dao,
                                    $artifact_link_usage_dao,
                                ),
                            ),
                            new TypeCreator(
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

        $commands_collector->addCommand(
            ReplayCreateProjectFromJiraCommand::NAME,
            static function (): ReplayCreateProjectFromJiraCommand {
                $user_manager = UserManager::instance();

                $nature_dao              = new TypeDao();
                $nature_validator        = new TypeValidator($nature_dao);
                $artifact_link_usage_dao = new ArtifactLinksUsageDao();

                return new ReplayCreateProjectFromJiraCommand(
                    $user_manager,
                    new CreateProjectFromJira(
                        $user_manager,
                        TemplateFactory::build(),
                        new XMLFileContentRetriever(),
                        new XMLImportHelper($user_manager),
                        new JiraTrackerBuilder(),
                        new ArtifactLinkTypeImporter(
                            new ArtifactLinkTypeConverter(
                                new TypePresenterFactory(
                                    $nature_dao,
                                    $artifact_link_usage_dao,
                                ),
                            ),
                            new TypeCreator(
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

        $commands_collector->addCommand(
            ReplayImportCommand::NAME,
            static fn () => new ReplayImportCommand(
                new XMLImportHelper(UserManager::instance())
            )
        );
    }
}
