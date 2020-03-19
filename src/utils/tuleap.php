<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Tuleap\CLI\Application;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\CLI\Command\ConfigGetCommand;
use Tuleap\CLI\Command\ConfigSetCommand;
use Tuleap\CLI\Command\DailyJobCommand;
use Tuleap\CLI\Command\ImportProjectXMLCommand;
use Tuleap\CLI\Command\LaunchEveryMinuteJobCommand;
use Tuleap\CLI\Command\ProcessSystemEventsCommand;
use Tuleap\CLI\Command\QueueSystemCheckCommand;
use Tuleap\CLI\Command\UserPasswordCommand;
use Tuleap\CLI\Command\WorkerSupervisorCommand;
use Tuleap\CLI\Command\WorkerSVNRootUpdateCommand;
use Tuleap\CLI\Command\WorkerSystemCtlCommand;
use Tuleap\CLI\DelayExecution\ConditionalTuleapCronEnvExecutionDelayer;
use Tuleap\CLI\DelayExecution\ExecutionDelayedLauncher;
use Tuleap\CLI\DelayExecution\ExecutionDelayerRandomizedSleep;
use Tuleap\DB\DBFactory;
use Tuleap\FRS\CorrectFrsRepositoryPermissionsCommand;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\User\UserSuspensionManager;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Queue\TaskWorker\TaskWorkerProcessCommand;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyRevoker;
use Tuleap\Dao\UserSuspensionDao;
use TuleapCfg\Command\ProcessFactory;

(static function () {
    require_once __DIR__ . '/../vendor/autoload.php';

    $execution_launcher = new ExecutionDelayedLauncher(
        new ConditionalTuleapCronEnvExecutionDelayer(
            new ExecutionDelayerRandomizedSleep(59)
        )
    );

    $execution_launcher->execute(
        static function () {
            // Do nothing
            // In a ideal world loading pre.php and doing the setup
            // of the CLI command should be done here but there is
            // too much code relying on having access to information
            // loaded implicitly into $GLOBALS by pre.php.
        }
    );
})();

require_once __DIR__ . '/../www/include/pre.php';

$event_manager         = EventManager::instance();
$backend_logger        = BackendLogger::getDefaultLogger();
$user_manager          = UserManager::instance();
$CLI_command_collector = new CLICommandsCollector();

$CLI_command_collector->addCommand(
    ConfigGetCommand::NAME,
    static function (): ConfigGetCommand {
        return new ConfigGetCommand();
    }
);
$CLI_command_collector->addCommand(
    ConfigSetCommand::NAME,
    static function () use ($event_manager) : ConfigSetCommand {
        return new ConfigSetCommand(
            new ConfigDao(),
            $event_manager
        );
    }
);
$CLI_command_collector->addCommand(
    UserPasswordCommand::NAME,
    static function () use ($user_manager): UserPasswordCommand {
        return new UserPasswordCommand(
            $user_manager,
            PasswordSanityChecker::build()
        );
    }
);
$CLI_command_collector->addCommand(
    ImportProjectXMLCommand::NAME,
    static function () : ImportProjectXMLCommand {
        return new ImportProjectXMLCommand();
    }
);
$CLI_command_collector->addCommand(
    ProcessSystemEventsCommand::NAME,
    static function () use ($backend_logger, $event_manager) : ProcessSystemEventsCommand {
        $store   = new SemaphoreStore();
        $factory = new LockFactory($store);

        return new ProcessSystemEventsCommand(
            new SystemEventProcessor_Factory($backend_logger, SystemEventManager::instance(), $event_manager),
            DBFactory::getMainTuleapDBConnection(),
            $factory
        );
    }
);
$CLI_command_collector->addCommand(
    QueueSystemCheckCommand::NAME,
    static function () use ($event_manager) : QueueSystemCheckCommand {
        return new QueueSystemCheckCommand(
            $event_manager,
            DBFactory::getMainTuleapDBConnection(),
            new ExecutionDelayedLauncher(
                new ConditionalTuleapCronEnvExecutionDelayer(
                    new ExecutionDelayerRandomizedSleep(1799)
                )
            )
        );
    }
);

$CLI_command_collector->addCommand(
    LaunchEveryMinuteJobCommand::NAME,
    static function () use ($event_manager, $backend_logger) : LaunchEveryMinuteJobCommand {
        return new LaunchEveryMinuteJobCommand(
            $event_manager,
            $backend_logger,
            DBFactory::getMainTuleapDBConnection()
        );
    }
);

$CLI_command_collector->addCommand(
    WorkerSVNRootUpdateCommand::NAME,
    static function () : WorkerSVNRootUpdateCommand {
        return new WorkerSVNRootUpdateCommand();
    }
);

$CLI_command_collector->addCommand(
    \Tuleap\CLI\Command\RedisWaiterCommand::NAME,
    static function () : \Tuleap\CLI\Command\RedisWaiterCommand {
        return new \Tuleap\CLI\Command\RedisWaiterCommand();
    }
);

$CLI_command_collector->addCommand(
    DailyJobCommand::NAME,
    static function () use ($event_manager, $user_manager) : DailyJobCommand {
        return new DailyJobCommand(
            $event_manager,
            new AccessKeyRevoker(
                new AccessKeyDAO()
            ),
            DBFactory::getMainTuleapDBConnection(),
            new ExecutionDelayedLauncher(
                new ConditionalTuleapCronEnvExecutionDelayer(
                    new ExecutionDelayerRandomizedSleep(1799)
                )
            ),
            new UserSuspensionManager(
                new MailPresenterFactory(),
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/mail/'),
                new Codendi_Mail,
                new UserSuspensionDao(),
                $user_manager,
                new BaseLanguageFactory(),
                BackendLogger::getDefaultLogger('usersuspension_syslog'),
                new LocaleSwitcher()
            )
        );
    }
);

$CLI_command_collector->addCommand(
    TaskWorkerProcessCommand::NAME,
    static function () use ($event_manager) : TaskWorkerProcessCommand {
        return new TaskWorkerProcessCommand(
            $event_manager,
            new TruncateLevelLogger(
                BackendLogger::getDefaultLogger(basename(Tuleap\Queue\Worker::DEFAULT_LOG_FILE_PATH)),
                ForgeConfig::get('sys_logger_level')
            )
        );
    }
);

$CLI_command_collector->addCommand(
    WorkerSupervisorCommand::NAME,
    static function () : WorkerSupervisorCommand {
        return new WorkerSupervisorCommand(
            new ProcessFactory(),
            new LockFactory(new SemaphoreStore()),
        );
    }
);

$CLI_command_collector->addCommand(
    WorkerSystemCtlCommand::NAME,
    static function () : WorkerSystemCtlCommand {
        return new WorkerSystemCtlCommand(
            new ProcessFactory(),
        );
    }
);

$CLI_command_collector->addCommand(
    CorrectFrsRepositoryPermissionsCommand::NAME,
    function (): CorrectFrsRepositoryPermissionsCommand {
        return new CorrectFrsRepositoryPermissionsCommand(
            new DirectoryIterator('/var/lib/tuleap/ftp/tuleap/'),
            ProjectManager::instance()
        );
    }
);

$event_manager->dispatch($CLI_command_collector);

$application = new Application();
$CLI_command_collector->loadCommands($application);
$application->run();
