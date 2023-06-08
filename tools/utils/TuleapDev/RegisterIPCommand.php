<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace TuleapDev\TuleapDev;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterIPCommand extends Command
{
    protected function configure()
    {
        $this->setName('register-ip')
            ->setDescription('Update /etc/hosts with a new entry')
            ->addArgument('service', InputArgument::REQUIRED, 'Name of the service (in docker-composer)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $tld          = '.tuleap-aio-dev.docker';
        $service_name = $input->getArgument('service');
        $full_name    = $service_name . $tld;
        if (gethostbyname($full_name) !== $full_name) {
            $output->writeln("$full_name is already registered");
            return 0;
        }

        $ipv4 = gethostbyname($service_name);
        if ($ipv4 !== $service_name) {
            file_put_contents('/etc/hosts', "$ipv4 $full_name\n", FILE_APPEND);
            $output->writeln("$full_name added to /etc/hosts");
            return 0;
        }
        $output->writeln("$service_name is unknown");
        return 1;
    }
}
