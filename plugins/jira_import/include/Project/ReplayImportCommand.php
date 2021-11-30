<?php
/*
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

use ProjectCreationData;
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
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\LinkedIssuesCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldAndValueIDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraClientReplay;
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
            ->addOption('path', '', InputOption::VALUE_REQUIRED, 'Path to the directory with the debug files')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'User who is doing the import')
            ->addOption('project', '', InputOption::VALUE_REQUIRED, 'Import in project')
            ->addOption('tracker-name', '', InputOption::VALUE_REQUIRED, 'New name for tracker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output, [LogLevel::DEBUG => OutputInterface::VERBOSITY_NORMAL]);
        try {
            $jira_client = JiraClientReplay::buildJiraServer($input->getOption('path'));

            $jira_xml_exporter = $this->getJiraXmlExporter($jira_client, $logger);

            $generated_xml = $this->generateXML($jira_xml_exporter, $input->getOption('tracker-name'));

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
                $this->createProject($generated_xml, $logger);
            }

            return Command::SUCCESS;
        } catch (\XML_ParseException $exception) {
            $logger->debug($exception->getIndentedXml());
            foreach ($exception->getErrors() as $error) {
                $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
                $logger->error('Error L' . $error->getLine() . ': ' . $exception->getSourceXMLForError($error));
            }
            return 1;
        }
    }

    private function generateXML(JiraXmlExporter $jira_xml_exporter, string $item_name): SimpleXMLElement
    {
        $platform_configuration_collection = new PlatformConfiguration();

        $tracker_for_export = (new XMLTracker('T200', $item_name))
            ->withName($item_name)
            ->withDescription('Bug')
            ->withColor(TrackerColor::default());

        $xml          = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $trackers_xml = $xml->addChild('trackers');
        $tracker_xml  = $tracker_for_export->export($trackers_xml);

        $jira_xml_exporter->exportJiraToXml(
            $platform_configuration_collection,
            $tracker_xml,
            'https://jira.example.com',
            'SBX',
            new IssueType('10102', 'Bogue', false),
            new FieldAndValueIDGenerator(),
            new LinkedIssuesCollection(),
        );

        return $xml;
    }

    private function getJiraXmlExporter(JiraClient $jira_client, LoggerInterface $logger): JiraXmlExporter
    {
        return JiraXmlExporter::build(
            $jira_client,
            $logger,
            new JiraUserOnTuleapCache(
                new JiraTuleapUsersMapping(),
                new TrackerImporterUser(),
            ),
        );
    }

    private function createProject(SimpleXMLElement $generated_xml, LoggerInterface $logger): \Project
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
