<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy\FPM;

use Psr\Log\LoggerInterface;
use TuleapCfg\Command\TemplateHelper;

final class FPMSessionRedis implements FPMSessionInterface
{
    public const DEFAULT_REDIS_PORT = 6379;

    private const TULEAP_CONF_FILE = 'tuleap_sessions_redis.part';

    /**
     * @var string
     */
    private $server;
    /**
     * @var string|null
     */
    private $password;
    /**
     * @var int
     */
    private $port;
    /**
     * @var string
     */
    private $tuleap_conf_file;
    /**
     * @var string
     */
    private $application_user;
    /**
     * @var bool
     */
    private $use_tls;

    public function __construct(string $tuleap_conf_file, string $application_user, string $server, bool $use_tls = false, int $port = self::DEFAULT_REDIS_PORT, ?string $password = null)
    {
        $this->server           = $server;
        $this->port             = $port;
        $this->password         = $password;
        $this->tuleap_conf_file = $tuleap_conf_file;
        $this->application_user = $application_user;
        $this->use_tls          = $use_tls;
    }

    public function deployFreshTuleapConf(
        LoggerInterface $logger,
        string $tuleap_php_configuration_folder,
        string $php_configuration_folder,
    ): void {
        $src_file = $tuleap_php_configuration_folder . '/' . self::TULEAP_CONF_FILE;
        $dst_file = $php_configuration_folder . '/php-fpm.d/' . self::DEPLOYED_FILE_NAME;

        if (! file_exists($dst_file)) {
            $this->deployFPM($logger, $src_file, $dst_file);
        }
        if (! file_exists($this->tuleap_conf_file)) {
            $this->deployTuleapConf($logger);
        }
    }

    public function forceDeployFreshTuleapConf(
        LoggerInterface $logger,
        string $tuleap_php_configuration_folder,
        string $php_configuration_folder,
    ): void {
        $src_file = $tuleap_php_configuration_folder . '/' . self::TULEAP_CONF_FILE;
        $dst_file = $php_configuration_folder . '/php-fpm.d/' . self::DEPLOYED_FILE_NAME;

        $this->deployFPM($logger, $src_file, $dst_file);
        $this->deployTuleapConf($logger);
    }

    /**
     * @param non-empty-string $dst_file
     */
    private function deployFPM(LoggerInterface $logger, string $src_file, string $dst_file): void
    {
        $logger->info("Deploy $src_file into $dst_file");
        TemplateHelper::replacePlaceHolderInto(
            $src_file,
            $dst_file,
            [
                '%redis-server%',
            ],
            [
                $this->getDSN(),
            ],
            0640
        );
    }

    private function getDSN(): string
    {
        $protocol = $this->use_tls ? 'tls' : 'tcp';
        $base     = sprintf('%s://%s:%d', $protocol, $this->server, $this->port);
        if ($this->password !== null && $this->password !== '') {
            return $base . '?auth=' . urlencode($this->password);
        }
        return $base;
    }

    private function deployTuleapConf(LoggerInterface $logger): void
    {
        $logger->info("Deploy $this->tuleap_conf_file");

        $conf = $this->getTuleapConfFile();
        if (file_put_contents($this->tuleap_conf_file, $conf) !== strlen($conf)) {
            throw new \RuntimeException("Cannot write redis configuration file $this->tuleap_conf_file");
        }
        chgrp($this->tuleap_conf_file, $this->application_user);
        chmod($this->tuleap_conf_file, 0640);
    }

    private function getTuleapConfFile(): string
    {
        $server = $this->server;
        if ($this->use_tls) {
            $server = 'tls://' . $server;
        }
        return <<<EOT
        <?php
        \$redis_server   = '$server';
        \$redis_port     = $this->port;
        \$redis_password = '$this->password';
        EOT;
    }
}
