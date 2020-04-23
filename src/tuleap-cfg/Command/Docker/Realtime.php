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

namespace TuleapCfg\Command\Docker;

use Psr\Log\LoggerInterface;
use RandomNumberGenerator;
use Symfony\Component\Process\Process;
use Webimpress\SafeWriter\FileWriter;

final class Realtime
{
    private const INTERNAL_NODEJS_SERVER = '127.0.0.1';
    private const KEY_PATH               = '/etc/pki/tls/private/tuleap-realtime-key.pem';
    private const CERT_PATH              = '/etc/pki/tls/certs/tuleap-realtime-cert.pem';
    private const PORT                   = 8443;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setup(string $tuleap_fqdn): void
    {
        $this->logger->info('Configure Realtime server');
        $private_key = (new RandomNumberGenerator(32))->getNumber();
        $this->generateTLSCertificate();
        $this->updateRealtimeConf($private_key);
        $this->deploySuperviordIni();
        $this->updateLocalInc($tuleap_fqdn, $private_key);
        $this->deployNginx();
        $this->logger->info('Realtime server configuration completed');
    }

    private function generateTLSCertificate(): void
    {
        $this->logger->debug('Generate TLS Certificates');
        $private_key = openssl_pkey_new();
        $cert        = openssl_csr_new(['commonName' => self::INTERNAL_NODEJS_SERVER], $private_key);
        $cert        = openssl_csr_sign($cert, null, $private_key, 3650, [], random_int(0, PHP_INT_MAX));

        openssl_x509_export($cert, $out);
        $public_part = (string) $out;
        FileWriter::writeFile(self::CERT_PATH, $public_part, 0644);
        $this->updateTrustedCerts($public_part);

        openssl_pkey_export($private_key, $out);
        $private_part = (string) $out;
        FileWriter::writeFile(self::KEY_PATH, $private_part, 0600);
    }

    private function updateTrustedCerts(string $certificate): void
    {
        $this->logger->debug('Update trusted certificates');
        FileWriter::writeFile('/etc/pki/ca-trust/source/anchors/tuleap-realtime-cert.pem', $certificate, 0600);
        (new Process(['/usr/bin/update-ca-trust', 'extract']))->mustRun();
    }

    private function deploySuperviordIni(): void
    {
        $this->logger->debug('Deploy supervisord snippet');
        copy(__DIR__ . '/../../resources/realtime.ini', '/etc/supervisord.d/tuleap-realtime.ini');
    }

    private function updateRealtimeConf(string $jwt_private_key): void
    {
        $this->logger->debug('Update realtime configuration');
        $realtime_conf_path = '/etc/tuleap-realtime/config.json';
        $raw_content = file_get_contents($realtime_conf_path);
        $json = \json_decode($raw_content, true, 512, JSON_THROW_ON_ERROR);
        $json['nodejs_server_jwt_private_key'] = $jwt_private_key;
        $json['full_path_ssl_cert'] = self::CERT_PATH;
        $json['full_path_ssl_key']  = self::KEY_PATH;
        $json['port']               = self::PORT;
        FileWriter::writeFile($realtime_conf_path, \json_encode($json, JSON_THROW_ON_ERROR));
    }

    private function updateLocalInc(string $tuleap_fqdn, string $jwt_private_key): void
    {
        $this->logger->debug('Update local.inc');
        $local_inc_path = '/etc/tuleap/conf/local.inc';
        $local_inc_content = file_get_contents('/etc/tuleap/conf/local.inc');
        $conf_string = preg_replace(
            [
                '/\$nodejs_server .*/',
                '/\$nodejs_server_int .*/',
                '/\$nodejs_server_jwt_private_key .*/',
            ],
            [
                sprintf('$nodejs_server = \'%s\';', $tuleap_fqdn),
                sprintf('$nodejs_server_int = \'%s:%d\';', self::INTERNAL_NODEJS_SERVER, self::PORT),
                sprintf('$nodejs_server_jwt_private_key = \'%s\';', $jwt_private_key),
            ],
            $local_inc_content,
        );
        FileWriter::writeFile($local_inc_path, $conf_string);
    }

    private function deployNginx(): void
    {
        $this->logger->debug('Deploy nginx snippet');
        copy(__DIR__ . '/../../resources/realtime.conf', '/etc/nginx/conf.d/tuleap.d/10-realtime.conf');
    }
}
