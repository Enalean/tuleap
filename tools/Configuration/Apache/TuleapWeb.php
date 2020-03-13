<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Configuration\Apache;

use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Logger\Wrapper;

class TuleapWeb
{
    private $httpd_conf_path;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var LogrotateDeployer
     */
    private $logrotate_deployer;

    public function __construct(LoggerInterface $logger, $httpd_conf_path, LogrotateDeployer $logrotate_deployer)
    {
        $this->logger             = new Wrapper($logger, 'apache');
        $this->httpd_conf_path    = $httpd_conf_path;
        $this->logrotate_deployer = $logrotate_deployer;
    }

    public function configure()
    {
        $this->logger->info("Start apache configuration");
        $this->updateHttpdConf();
        $this->disableSSLvhost();
        $this->logrotate_deployer->deployLogrotate();
        $this->logger->info("Configuration done!");
    }

    private function updateHttpdConf()
    {
        $httpd_conf = file_get_contents($this->httpd_conf_path . '/conf/httpd.conf');

        $conf = preg_replace(
            array(
                '/^Include conf\/ssl\.conf$/m',
                '/^Listen .*$/m',
            ),
            array(
                '#Include conf/ssl.conf',
                'Listen 127.0.0.1:8080',
            ),
            $httpd_conf
        );

        $conf = $this->turnHttpVhostOn8080($conf);

        if ($httpd_conf !== $conf) {
            $this->logger->info("Make apache listen on localhost 8080");
            file_put_contents($this->httpd_conf_path . '/conf/httpd.conf', $conf);
        }
    }

    private function disableSSLvhost()
    {
        $vhost_file = $this->httpd_conf_path . '/conf.d/tuleap-vhost.conf';
        if (is_file($vhost_file)) {
            $new_content = '';
            $in_ssl_vhost = false;
            $need_rewrite = false;
            foreach (file($vhost_file, FILE_IGNORE_NEW_LINES) as $line) {
                if (preg_match('/^<VirtualHost .*:443>$/', $line) === 1) {
                    $this->logger->info("Disable SSH Virtualhost");
                    $in_ssl_vhost = true;
                    $need_rewrite = true;
                }

                if ($in_ssl_vhost === true) {
                    $new_content .= '#' . $line . PHP_EOL;
                } else {
                    $new_content .= $line . PHP_EOL;
                }

                if (preg_match('/^<\/VirtualHost>$/', $line) === 1) {
                    $in_ssl_vhost = false;
                }
            }

            $without_vhosts = $this->turnHttpVhostOn8080($new_content);
            if ($without_vhosts !== $new_content) {
                $this->logger->info("Update :80 vhosts to :8080");
                $new_content  = $without_vhosts;
                $need_rewrite = true;
            }

            if ($need_rewrite) {
                file_put_contents($vhost_file, $new_content);
            }
        }
    }

    private function turnHttpVhostOn8080($content)
    {
        return preg_replace(
            array(
                '/^NameVirtualHost .*:80$/m',
                '/^<VirtualHost .*:80>/m',
            ),
            array(
                'NameVirtualHost 127.0.0.1:8080',
                '<VirtualHost 127.0.0.1:8080>',
            ),
            $content
        );
    }
}
