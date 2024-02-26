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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project;

use ProjectCreator;
use ProjectXMLImporter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\XML\JiraXMLNodeBuilder;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use Tuleap\Tracker\XML\XMLTracker;
use Tuleap\XML\MappingsRegistry;
use User\XML\Import\IFindUserFromXMLReference;

final class ReplayImportCommand extends Command
{
    public const NAME = 'import-project:replay-jira';

    public function __construct(private IFindUserFromXMLReference $user_finder)
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setDescription('Replay a jira import from a debug archive (meant for Tuleap developers)')
            ->addOption('server-flavor', '', InputOption::VALUE_REQUIRED, 'Type of Jira instance (cloud or server)', 'server')
            ->addOption('server-major-version', '', InputOption::VALUE_OPTIONAL, 'Major version of Jira server instance')
            ->addOption('path', '', InputOption::VALUE_REQUIRED, 'Path to the directory with the debug files')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'User who is doing the import')
            ->addOption('project', '', InputOption::VALUE_REQUIRED, 'Import in project')
            ->addOption('tracker-name', '', InputOption::VALUE_REQUIRED, 'New name for tracker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output, [LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL]);
        try {
            $jira_client = JiraClientReplayBuilder::buildReplayClientWithCommandOptions(
                $input->getOption('server-flavor'),
                $input->getOption("server-major-version"),
                $input->getOption('path'),
            );

            $jira_xml_exporter = $this->getJiraXmlExporter($jira_client, $logger);

            $jira_project       = $jira_client->getJiraProject();
            $jira_issue_type_id = $jira_client->getJiraIssueTypeId();
            if ($jira_project === null || $jira_issue_type_id === null) {
                $output->writeln('<error>Unable to find Jira project or Issue type in provided archive</error>');
                return 1;
            }

            $generated_xml = $this->generateXML(
                $jira_xml_exporter,
                $input->getOption('tracker-name'),
                $jira_project,
                $jira_issue_type_id,
            );

            $project = $input->getOption('project');
            if ($project) {
                $project = \ProjectManager::instance()->getProjectByUnixName($project);
                if (! $project || $project->isError() || ! $project->isActive()) {
                    $output->writeln('<error>Invalid project name given</error>');
                    return Command::FAILURE;
                }

                $user = \UserManager::instance()->forceLogin($input->getOption('user'));
                if (! $user->isAlive()) {
                    throw new InvalidArgumentException('invalid user');
                }

                $tracker_xml_import = \TrackerXmlImport::build($this->user_finder, $logger);
                $tracker_xml_import->import(
                    new ImportConfig(),
                    $project,
                    $generated_xml,
                    new MappingsRegistry(),
                    '/tmp',
                    $user
                );

                $output->writeln("Import successful");
            } else {
                return $this->createProject($generated_xml, $logger)
                    ->match(
                        static fn() => Command::SUCCESS,
                        function (Fault $fault) use ($logger): int {
                            Fault::writeToLogger($fault, $logger);
                            return Command::FAILURE;
                        }
                    );
            }

            return Command::SUCCESS;
        } catch (\XML_ParseException $exception) {
            $logger->debug($exception->getIndentedXml());
            foreach ($exception->getErrors() as $error) {
                $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
                $logger->error('Error L' . $error->getLine() . ': ' . $exception->getSourceXMLForError($error));
            }
            return Command::FAILURE;
        }
    }

    private function generateXML(JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter $jira_xml_exporter, string $item_name, string $jira_project_key, string $jira_issue_type_id): SimpleXMLElement
    {
        $platform_configuration_collection = new PlatformConfiguration();

        $tracker = (new XMLTracker('T200', $item_name))
            ->withName($item_name)
            ->withDescription('Bug')
            ->withColor(TrackerColor::default());

        $tracker_xml = $jira_xml_exporter->exportIssuesToXml(
            $platform_configuration_collection,
            $tracker,
            'https://jira.example.com',
            $jira_project_key,
            new IssueType($jira_issue_type_id, 'undefined', false),
            new FieldAndValueIDGenerator(),
            new LinkedIssuesCollection(),
            CreateProjectFromJiraCommand::OPT_IMPORT_MODE_MULTI_TRACKERS_VALUE,
        );

        return JiraXMLNodeBuilder::buildProjectSimpleXmlElement($tracker_xml);
    }

    private function getJiraXmlExporter(JiraClient $jira_client, LoggerInterface $logger): JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter
    {
        return JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter::build(
            $jira_client,
            $logger,
            new JiraUserOnTuleapCache(
                new JiraTuleapUsersMapping(),
                new TrackerImporterUser(),
            ),
        );
    }

    /**
     * @return Ok<\Project>|Err<Fault>
     */
    private function createProject(SimpleXMLElement $generated_xml, LoggerInterface $logger): Ok|Err
    {
        $archive = new JiraProjectArchive($generated_xml);

        $data = ProjectCreationData::buildFromXML(
            $generated_xml,
            null,
            null,
            $logger
        );

        $project_xml_importer = ProjectXMLImporter::build(
            $this->user_finder,
            ProjectCreator::buildSelfByPassValidation(),
            $logger,
        );

        return $project_xml_importer->importWithProjectData(
            new ImportConfig(),
            $archive,
            new SystemEventRunnerForProjectCreationFromXMLTemplate(),
            $data
        );
    }
}
