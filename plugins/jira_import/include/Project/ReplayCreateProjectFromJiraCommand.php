<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Tracker\Creation\JiraImporter\JiraClientReplay;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use UserManager;

final class ReplayCreateProjectFromJiraCommand extends Command
{
    public const NAME = 'import-project:replay-from-jira';

    public function __construct(
        private UserManager $user_manager,
        private CreateProjectFromJira $create_project_from_jira,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this
            ->setHidden(true)
            ->setDescription('Replay a jira project import from a debug archive (meant for Tuleap developers)')
            ->addOption('server-flavor', '', InputOption::VALUE_REQUIRED, 'Type of Jira instance (cloud or server)', 'server')
            ->addOption('path', '', InputOption::VALUE_REQUIRED, 'Path to the directory with the debug files')
            ->addOption('user', '', InputOption::VALUE_REQUIRED, 'Tuleap user login of who is doing the import')
            ->addOption('project', '', InputOption::VALUE_REQUIRED, 'Import in project')
            ->addOption('epic-name', '', InputOption::VALUE_REQUIRED, 'Name of the epic issueType', 'Epic');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger              = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $tuleap_project_name = $input->getOption('project');

        $traces_path = $input->getOption('path');

        if ($input->getOption('server-flavor') !== 'server') {
            $jira_client = JiraClientReplay::buildJiraCloud($traces_path);
        } else {
            $jira_client = JiraClientReplay::buildJiraServer($traces_path);
        }

        $jira_credentials = $this->getCredentialsFromManifestURL($traces_path);

        $tuleap_username = $input->getOption('user');

        $user = $this->user_manager->forceLogin($tuleap_username);
        if (! $user->isAlive()) {
            throw new InvalidArgumentException('invalid user');
        }

        $jira_project = $jira_client->getJiraProject();
        if ($jira_project === null) {
            throw new \RuntimeException('jira project not found in manifest.log');
        }

        try {
            $project = $this->create_project_from_jira->create(
                $logger,
                $jira_client,
                $jira_credentials,
                $jira_project,
                $tuleap_project_name,
                $tuleap_project_name,
                $input->getOption('epic-name'),
            );
            $output->writeln(sprintf('Project %d created', $project->getID()));
            $output->writeln("Import completed");
        } catch (\XML_ParseException $exception) {
            foreach ($exception->getErrors() as $error) {
                $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
            }
            $logger->info("Generated XML file: " . $traces_path . '/project.xml');
            file_put_contents($traces_path . '/project.xml', $exception->getXMLWithoutLineNumbers());
            return 1;
        }
        return 0;
    }

    private function getCredentialsFromManifestURL(string $path): JiraCredentials
    {
        $lines = file($path . '/manifest.log');
        if (! isset($lines[0])) {
            throw new \RuntimeException('No lines in ' . $path . '/manifest.log');
        }
        $end_of_host = strpos($lines[0], '/rest/api/2');
        if ($end_of_host === false) {
            throw new \RuntimeException('No /rest/api/2 found in ' . $path . '/manifest.log');
        }
        return new JiraCredentials(
            substr($lines[0], 0, $end_of_host),
            '',
            new ConcealedString(''),
        );
    }
}
