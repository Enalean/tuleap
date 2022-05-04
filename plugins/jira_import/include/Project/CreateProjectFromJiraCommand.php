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

use Monolog\Handler\PsrHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraCredentials;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use UserManager;

final class CreateProjectFromJiraCommand extends Command
{
    public const NAME = 'import-project:from-jira';

    private const OPT_JIRA_HOST            = 'jira-host';
    private const OPT_JIRA_USER            = 'jira-user';
    private const OPT_JIRA_TOKEN           = 'jira-token';
    private const OPT_JIRA_PROJECT         = 'jira-project-id';
    private const OPT_JIRA_EPIC_ISSUE_TYPE = 'jira-epic-issue-type';
    private const OPT_TULEAP_USER          = 'tuleap-user';
    private const OPT_SHORTNAME            = 'shortname';
    private const OPT_FULLNAME             = 'fullname';
    private const OPT_OUTPUT               = 'output';
    private const OPT_DEBUG                = 'debug';

    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var JiraProjectBuilder
     */
    private $jira_project_builder;
    /**
     * @var CreateProjectFromJira
     */
    private $create_project_from_jira;

    public function __construct(
        UserManager $user_manager,
        JiraProjectBuilder $jira_project_builder,
        CreateProjectFromJira $create_project_from_jira,
    ) {
        parent::__construct(self::NAME);
        $this->user_manager             = $user_manager;
        $this->jira_project_builder     = $jira_project_builder;
        $this->create_project_from_jira = $create_project_from_jira;
    }

    protected function configure(): void
    {
        $this->setDescription('Import a project from a Jira instance')
            ->addOption(self::OPT_JIRA_HOST, '', InputOption::VALUE_REQUIRED, 'URL of the Jira server')
            ->addOption(self::OPT_JIRA_USER, '', InputOption::VALUE_REQUIRED, 'User email (or login for Jira Server) to access the platform')
            ->addOption(self::OPT_JIRA_TOKEN, '', InputOption::VALUE_REQUIRED, 'User token (or password for Jira Server) to access the platform')
            ->addOption(self::OPT_JIRA_PROJECT, '', InputOption::VALUE_REQUIRED, 'ID of the Jira project to import (you will be prompted if not provided)')
            ->addOption(self::OPT_JIRA_EPIC_ISSUE_TYPE, '', InputOption::VALUE_REQUIRED, 'Name of the epic issue type of the Jira project to import (default is Epic if not provided)')
            ->addOption(self::OPT_TULEAP_USER, '', InputOption::VALUE_REQUIRED, 'Login name of the user who will be admin of the project')
            ->addOption(self::OPT_SHORTNAME, '', InputOption::VALUE_REQUIRED, 'Short name of the Tuleap project to create')
            ->addOption(self::OPT_FULLNAME, '', InputOption::VALUE_REQUIRED, 'Full name of the Tuleap project to create')
            ->addOption(self::OPT_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Generate the project archive without actually importing it')
            ->addOption(self::OPT_DEBUG, 'd', InputOption::VALUE_REQUIRED, 'Turn on debug mode, will dump content in provided directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger          = new ConsoleLogger($output, [LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL]);
        $debug_directory = $input->getOption(self::OPT_DEBUG);
        if ($debug_directory !== null && is_string($debug_directory)) {
            $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            if (! is_dir($debug_directory) && ! mkdir($debug_directory, 0700, true) && ! is_dir($debug_directory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $debug_directory));
            }
            $log_file = $debug_directory . '/import.log';
            if (file_exists($log_file)) {
                unlink($log_file);
            }
            $logger = new Logger('jira');
            $logger->pushHandler(new StreamHandler($log_file, Logger::DEBUG));
            $logger->pushHandler(new PsrHandler(new ConsoleLogger($output)));
        }

        $question_helper = $this->getHelper('question');
        assert($question_helper instanceof QuestionHelper);

        $jira_host     = $this->getStringOption($input, self::OPT_JIRA_HOST);
        $jira_username = $this->getStringOption($input, self::OPT_JIRA_USER);
        if (! $input->getOption(self::OPT_JIRA_TOKEN)) {
            do {
                $question = new Question("Please provide $jira_username token (or password for Jira Server): ");
                $question->setHidden(true);
                $question->setHiddenFallback(false);
                $token = $question_helper->ask($input, $output, $question);
            } while (! is_string($token));
            $jira_token = new ConcealedString($token);
            \sodium_memzero($token);
        } else {
            $jira_token = new ConcealedString($this->getStringOption($input, self::OPT_JIRA_TOKEN));
        }

        $shortname = $this->getStringOption($input, self::OPT_SHORTNAME);
        $fullname  = $input->getOption(self::OPT_FULLNAME);
        if (! is_string($fullname)) {
            $fullname = $shortname;
        }
        $tuleap_username = $this->getStringOption($input, self::OPT_TULEAP_USER);

        $user = $this->user_manager->forceLogin($tuleap_username);
        if (! $user->isAlive()) {
            throw new InvalidArgumentException('invalid user');
        }

        $jira_credentials = new JiraCredentials($jira_host, $jira_username, $jira_token);

        $jira_client = ClientWrapper::build($jira_credentials, $logger);
        if ($debug_directory !== null && is_string($debug_directory)) {
            $jira_client->setDebugDirectory($debug_directory);
        }

        if (! $input->getOption(self::OPT_JIRA_PROJECT)) {
            $jira_projects = $this->jira_project_builder->build($jira_client, $logger);
            $autocomplete  = [];
            $output->writeln('');
            foreach ($jira_projects as $project) {
                $autocomplete[] = $project['id'];
            }
            $question = new ChoiceQuestion("Please select the name of the Jira project to import", $autocomplete);
            $question->setAutocompleterValues($autocomplete);
            $jira_project = $question_helper->ask($input, $output, $question);
        } else {
            $jira_project = $this->getStringOption($input, self::OPT_JIRA_PROJECT);
        }

        $archive_path = $input->getOption(self::OPT_OUTPUT);
        if (! is_string($archive_path)) {
            $archive_path = false;
        }

        $jira_epic_issue_type = $input->getOption(self::OPT_JIRA_EPIC_ISSUE_TYPE);
        if (! is_string($jira_epic_issue_type)) {
            $jira_epic_issue_type = "Epic";
        }

        $output->writeln(sprintf("Create project %s", $shortname));

        try {
            if ($archive_path !== false) {
                $this->create_project_from_jira->generateArchive(
                    $logger,
                    $jira_client,
                    $jira_credentials,
                    $jira_project,
                    $shortname,
                    $fullname,
                    $jira_epic_issue_type,
                    $archive_path
                );
                $output->writeln("XML file generated: $archive_path");
            } else {
                $project = $this->create_project_from_jira->create(
                    $logger,
                    $jira_client,
                    $jira_credentials,
                    $jira_project,
                    $shortname,
                    $fullname,
                    $jira_epic_issue_type
                );
                $output->writeln(sprintf('Project %d created', $project->getID()));
                $output->writeln("Import completed");
            }
        } catch (\XML_ParseException $exception) {
            $logger->debug($exception->getIndentedXml());
            foreach ($exception->getErrors() as $error) {
                $logger->error($error->getMessage() . ' (Type: ' . $error->getType() . ') Line: ' . $error->getLine() . ' Column: ' . $error->getColumn());
                $logger->error('Error L' . $error->getLine() . ': ' . $exception->getSourceXMLForError($error));
            }
            return 1;
        }
        return 0;
    }

    private function getStringOption(InputInterface $input, string $key): string
    {
        $shortname = $input->getOption($key);
        if (! is_string($shortname)) {
            throw new MissingOptionsException('--' . $key . ' is missing');
        }
        return $shortname;
    }
}
