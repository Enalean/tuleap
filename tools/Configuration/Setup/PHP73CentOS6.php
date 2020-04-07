<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Configuration\Setup;

use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Apache\LogrotateDeployer;
use Tuleap\Configuration\Etc;
use Tuleap\Configuration\FPM;
use Tuleap\Configuration\Nginx;
use Tuleap\Configuration\Apache;

class PHP73CentOS6
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->setErrorHandler($logger);
        $this->logger = $logger;
    }

    public function main(): void
    {
        try {
            $options = getopt(
                'h',
                array(
                    'help',
                    'module:',
                    'development',
                )
            );
            $this->exitIfHelp($options);

            $this->logger->info('Configure Tuleap for PHP 7.3 / FPM and Nginx');
            $this->configure($this->getModules($options), $this->getIsDevelopment($options));
            $this->logger->info('Configuration completed');
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            exit(1);
        }
    }

    private function exitIfHelp(array $options): void
    {
        if (isset($options['h']) || isset($options['help'])) {
            $this->help();
            exit(0);
        }
    }

    private function getModules(array $options): array
    {
        $all_modules = ['nginx', 'apache', 'fpm'];
        if (isset($options['module'])) {
            return array_filter(explode(',', $options['module']), function ($module) use ($all_modules) {
                return in_array($module, $all_modules, true);
            });
        }
        return $all_modules;
    }

    private function getIsDevelopment(array $options): bool
    {
        return isset($options['development']);
    }

    private function configure(array $modules, $for_development): void
    {
        $conf_loader = new Etc\LoadLocalInc('/etc/tuleap', '/usr/share/tuleap');
        $variables   = $conf_loader->getVars();

        $configs = [];
        if (in_array('fpm', $modules, true)) {
            $configs[] = FPM\TuleapWeb::buildForPHP73($this->logger, $variables->getApplicationUser(), $for_development);
        }
        if (in_array('nginx', $modules, true)) {
            $configs[] = new Nginx\TuleapWeb(
                $this->logger,
                $variables->getApplicationBaseDir(),
                '/etc/nginx',
                $variables->getServerName(),
                $for_development
            );
        }
        if (in_array('apache', $modules, true)) {
            $configs[] = new Apache\TuleapWeb($this->logger, '/etc/httpd', new LogrotateDeployer($this->logger));
        }

        foreach ($configs as $conf) {
            $conf->configure();
        }
    }

    private function help(): void
    {
        echo <<<EOT
Usage: /usr/share/tuleap/tools/utils/php73/run.php [--module=nginx,fpm]

Configuration of Tuleap for usage of PHP 7.3 / FPM and Nginx

--module=...    Select the module(s) you want to configure (comma separated)

EOT;
    }

    private function setErrorHandler(LoggerInterface $logger): void
    {
        // Make all warnings or notices fatal
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($logger) {
            $logger->error("PHP message: $errstr $errfile L$errline (err $errno)");
            exit;
        }, E_ALL | E_STRICT);
    }
}
