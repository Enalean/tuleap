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
 */

namespace TuleapCfg\Command\SiteDeploy\Nginx;

use Psr\Log\LoggerInterface;

class SiteDeployNginx
{
    public const SSL_CERT_KEY_PATH  = '/etc/pki/tls/private/localhost.key.pem';
    public const SSL_CERT_CERT_PATH = '/etc/pki/tls/certs/localhost.cert.pem';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $tuleap_base_dir;
    /**
     * @var string
     */
    private $nginx_base_dir;
    /**
     * @var string
     */
    private $server_name;
    /**
     * @var NginxCommon
     */
    private $common;
    /**
     * @var bool
     */
    private $for_development;

    public function __construct(LoggerInterface $logger, NginxServerNamesHashBucketSizeCalculator $hash_bucket_size_calculator, string $tuleap_base_dir, string $nginx_base_dir, string $server_name, bool $for_development)
    {
        $this->logger          = $logger;
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
        $this->server_name     = $server_name;
        $this->for_development = $for_development;

        $this->common = new NginxCommon($this->logger, $hash_bucket_size_calculator, $tuleap_base_dir, $nginx_base_dir);
    }

    public function configure(): void
    {
        $should_tls_certificate_be_generated = false;

        $this->logger->info('Start configuration');

        $this->common->deployConfigurationChunks($this->server_name);

        if (! file_exists($this->nginx_base_dir . '/conf.d/tuleap.conf')) {
            $this->logger->info('Generate tuleap.conf');
            $this->common->replacePlaceHolderInto(
                $this->tuleap_base_dir . '/src/etc/nginx/tuleap.conf.dist',
                $this->nginx_base_dir . '/conf.d/tuleap.conf',
                [
                    '%ssl_certificate_key_path%',
                    '%ssl_certificate_path%',
                    '%sys_default_domain%',
                ],
                [
                    self::SSL_CERT_KEY_PATH,
                    self::SSL_CERT_CERT_PATH,
                    $this->server_name,
                ]
            );
            $should_tls_certificate_be_generated = true;

            $this->logger->info('Generate default.d/redirect_tuleap.conf');
            $this->common->replacePlaceHolderInto(
                $this->tuleap_base_dir . '/src/etc/nginx/default.d/redirect_tuleap.conf.dist',
                $this->nginx_base_dir . '/default.d/redirect_tuleap.conf',
                ['%sys_default_domain%'],
                [$this->server_name]
            );
        }

        if (
            ($this->for_development || $should_tls_certificate_be_generated)
                && ! file_exists(self::SSL_CERT_CERT_PATH)
        ) {
            $this->common->generateSSLCertificate(
                $this->server_name,
                self::SSL_CERT_CERT_PATH,
                self::SSL_CERT_KEY_PATH
            );
        }

        $this->logger->info('Configuration done!');
    }
}
