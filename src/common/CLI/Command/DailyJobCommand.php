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

namespace Tuleap\CLI\Command;

use EventManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tuleap\CLI\DelayExecution\ExecutionDelayedLauncher;
use Tuleap\DB\DBConnection;
use Tuleap\User\AccessKey\AccessKeyRevoker;
use UserManager;
use Tuleap\Mail\AutomaticMailsSender;

class DailyJobCommand extends Command
{
    public const NAME = 'daily-job';

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var DBConnection
     */
    private $db_connection;
    /**
     * @var ExecutionDelayedLauncher
     */
    private $execution_delayed_launcher;

    /**
     * @var AccessKeyRevoker
     */
    private $access_key_revoker;

    /**
     * @var AutomaticMailsSender
     */
    private $auto_mails_sender;

    public function __construct(
        EventManager $event_manager,
        UserManager $user_manager,
        AccessKeyRevoker $access_key_revoker,
        DBConnection $db_connection,
        ExecutionDelayedLauncher $execution_delayed_launcher,
        AutomaticMailsSender $auto_mails_sender
    ) {
        parent::__construct(self::NAME);
        $this->event_manager              = $event_manager;
        $this->user_manager               = $user_manager;
        $this->db_connection              = $db_connection;
        $this->execution_delayed_launcher = $execution_delayed_launcher;
        $this->access_key_revoker         = $access_key_revoker;
        $this->auto_mails_sender = $auto_mails_sender;
    }

    protected function configure() : void
    {
        $this->setDescription('Execute time consuming, low priority housekeeping jobs that should run once a day');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->execution_delayed_launcher->execute(function () {
            $this->db_connection->reconnectAfterALongRunningProcess();
            $this->event_manager->processEvent('codendi_daily_start');
            $this->user_manager->checkUserAccountValidity();
            $this->auto_mails_sender->sendNotificationMailToIdleAccounts();

            $now = (new \DateTime())->getTimestamp();
            $this->access_key_revoker->revokeExpiredUserAccessKeys($now);
        });
        return 0;
    }
}
