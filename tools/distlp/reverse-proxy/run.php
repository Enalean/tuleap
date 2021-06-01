<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/vendor/autoload.php';

use Psr\Log\LoggerInterface;
use TuleapCfg\Command\SiteDeploy\Nginx\NginxCommon;

class ReverseProxy // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private LoggerInterface $logger;
    private string $tuleap_base_dir;
    private string $nginx_base_dir;
    private string $server_name;
    /**
     * @var NginxCommon
     */
    private $common;

    public function __construct(LoggerInterface $logger, string $tuleap_base_dir, string $nginx_base_dir, string $server_name)
    {
        $this->logger          = $logger;
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
        $this->server_name     = $server_name;
        $this->common          = new NginxCommon($logger, $tuleap_base_dir, $nginx_base_dir);
    }

    public function configure()
    {
        $this->logger->info("Configure Nginx as front Reverse Proxy");
        if (is_file($this->nginx_base_dir . '/nginx.conf.orig')) {
            $this->logger->warning($this->nginx_base_dir . '/nginx.conf.orig already exists, skip configuration');
            return;
        }
        $this->backupOriginalFile($this->nginx_base_dir . '/nginx.conf');
        copy($this->tuleap_base_dir . '/tools/distlp/reverse-proxy/nginx.conf', $this->nginx_base_dir . '/nginx.conf');

        copy($this->tuleap_base_dir . '/tools/distlp/reverse-proxy/proxy-vars.conf', $this->nginx_base_dir . '/proxy-vars.conf');
        copy($this->tuleap_base_dir . '/tools/distlp/reverse-proxy/tcp_ssh.conf', $this->nginx_base_dir . '/conf.d/tcp_ssh.conf');
        $this->deployHTTPConfFromTemplate();

        $this->logger->info("Generate SSL certificate");
        $this->common->generateSSLCertificate($this->server_name, '/etc/pki/tls/certs/localhost.cert.pem', '/etc/pki/tls/private/localhost.key.pem');
        $this->logger->info("Done");
    }

    private function deployHTTPConfFromTemplate()
    {
        $template = file_get_contents($this->tuleap_base_dir . '/tools/distlp/reverse-proxy/http_tuleap.conf');
        $searches = [
            '%sys_default_domain%',
        ];
        $replaces = [
            $this->server_name,
        ];

        $conf = str_replace($searches, $replaces, $template);
        file_put_contents($this->nginx_base_dir . '/conf.d/http_tuleap.conf', $conf);
    }

    private function backupOriginalFile($file)
    {
        if (! file_exists($file . '.orig')) {
            copy($file, $file . '.orig');
        }
    }
}

\ForgeConfig::loadFromFile('/data/etc/tuleap/conf/local.inc');

$logger = new \Monolog\Logger('reverse-proxy');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT));

$reverse_proxy = new ReverseProxy(
    $logger,
    '/tuleap',
    '/etc/nginx',
    \ForgeConfig::get('sys_default_domain'),
);

$reverse_proxy->configure();
