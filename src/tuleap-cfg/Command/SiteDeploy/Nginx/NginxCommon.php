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

namespace TuleapCfg\Command\SiteDeploy\Nginx;

use DirectoryIterator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Psl\File;
use Psl\Str;

class NginxCommon
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NginxServerNamesHashBucketSizeCalculator $hash_bucket_size_calculator,
        private readonly string $tuleap_base_dir,
        private readonly string $nginx_base_dir,
    ) {
    }

    public function generateSSLCertificate(string $server_name, string $cert_filepath, string $key_filepath): void
    {
        if (! file_exists($cert_filepath)) {
            $this->logger->info("Generate self-signed certificate in $cert_filepath");
            $cn = $server_name;
            if (strlen($cn) > 64) {
                // The common name cannot be longer than 64 char (RFC 3280)
                // but we still need to set one even if the SAN will be used
                // to make cURL happy on EL9 systems
                $cn = 'tuleap.local.invalid';
            }
            Process::fromShellCommandline(
                '( cat "$OPENSSL_CONF_FILE"; echo "[SAN]" ; echo "subjectAltName=DNS:' . $server_name . '" ) | openssl req -batch -nodes -x509 -newkey ec -pkeyopt ec_paramgen_curve:prime256v1 -keyout ' . $key_filepath . ' -out ' . $cert_filepath . ' -days 365 -subj "/CN=' . $cn . '" -extensions SAN -extensions root_ca -config /dev/stdin',
                '/',
                ['OPENSSL_CONF_FILE' => __DIR__ . '/openssl-conf-self-signed-cert.cnf']
            )
                ->setTimeout(0)
                ->mustRun();
        }
    }

    public function deployConfigurationChunks(string $server_name): void
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}");
        $this->copyTuleapGeneralSettings($server_name);
        $this->copyTuleapDotD();
        $this->copyTuleapPlugins();
    }

    private function copyTuleapGeneralSettings(string $server_name): void
    {
        $this->logger->info("Deploy global settings chunk in {$this->nginx_base_dir}");

        $global_settings_template = File\read($this->tuleap_base_dir . '/src/etc/nginx/tuleap-managed-global-settings.conf');
        $global_settings          = Str\replace($global_settings_template, '%hash_bucket_size%', (string) $this->hash_bucket_size_calculator->computeServerNamesHashBucketSize($server_name));

        File\write($this->nginx_base_dir . '/conf.d/tuleap-managed-global-settings.conf', $global_settings, File\WriteMode::Truncate);
    }

    private function copyTuleapDotD(): void
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}/conf.d/tuleap.d");
        $tuleap_d_dir      = $this->nginx_base_dir . '/conf.d/tuleap.d';
        $tuleap_d_base_dir = $this->tuleap_base_dir . '/src/etc/nginx/tuleap.d';

        $this->createDirectoryIfNotExists($tuleap_d_dir);
        foreach (new DirectoryIterator($tuleap_d_base_dir) as $file) {
            if (! $file->isDot()) {
                copy($file->getPathname(), $tuleap_d_dir . '/' . $file->getBasename());
            }
        }
    }

    private function copyTuleapPlugins(): void
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}/conf.d/tuleap-plugins");
        $tuleap_plugins_dir = $this->nginx_base_dir . '/conf.d/tuleap-plugins';

        $this->createDirectoryIfNotExists($tuleap_plugins_dir);

        foreach (new DirectoryIterator($this->tuleap_base_dir . '/plugins') as $file) {
            if (! $file->isDot()) {
                $plugin           = $file->getBasename();
                $conf_file        = $file->getPathname() . '/etc/nginx/' . $plugin . '.conf';
                $plugin_conf_file = $tuleap_plugins_dir . '/' . basename($conf_file);
                if (is_file($conf_file)) {
                    copy($conf_file, $plugin_conf_file);
                } elseif (is_file($file->getPathname() . '/.use-front-controller')) {
                    $this->writeFromTemplate($plugin_conf_file, $plugin, $this->tuleap_base_dir . '/src/etc/nginx/plugin-frontcontroller.conf.dist');
                } elseif (is_dir($file->getPathname() . '/www')) {
                    $this->writeFromTemplate($plugin_conf_file, $plugin, $this->tuleap_base_dir . '/src/etc/nginx/plugin.conf.dist');
                }
            }
        }
    }

    private function writeFromTemplate(string $target_path, string $plugin_name, string $template_path): void
    {
        file_put_contents(
            $target_path,
            str_replace(
                '%name%',
                $plugin_name,
                file_get_contents($template_path)
            )
        );
    }

    public function replacePlaceHolderInto(string $template_path, string $target_path, array $variables, array $values): void
    {
        file_put_contents(
            $target_path,
            str_replace(
                $variables,
                $values,
                file_get_contents($template_path)
            )
        );
    }

    private function createDirectoryIfNotExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755);
        }
    }
}
