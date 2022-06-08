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

class NginxCommon
{
    /**
     * @var string
     */
    private $tuleap_base_dir;
    /**
     * @var string
     */
    private $nginx_base_dir;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, string $tuleap_base_dir, string $nginx_base_dir)
    {
        $this->logger          = $logger;
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
    }

    public function generateSSLCertificate(string $server_name, string $cert_filepath, string $key_filepath): void
    {
        if (! file_exists($cert_filepath)) {
            $this->logger->info("Generate self-signed certificate in $cert_filepath");
            Process::fromShellCommandline(
                '( cat "$OPENSSL_CONF_FILE"; echo "[SAN]" ; echo "subjectAltName=DNS:' . $server_name . '" ) | openssl req -batch -nodes -x509 -newkey rsa:4096 -keyout ' . $key_filepath . ' -out ' . $cert_filepath . ' -days 365 -subj "/CN=' . $server_name . '" -extensions SAN -extensions root_ca -config /dev/stdin',
                null,
                ['OPENSSL_CONF_FILE' => __DIR__ . '/openssl-conf-self-signed-cert.cnf']
            )
                ->setTimeout(0)
                ->mustRun();
        }
    }

    public function deployConfigurationChunks(): void
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}");
        $this->copyTuleapGeneralSettings();
        $this->copyTuleapDotD();
        $this->copyTuleapPlugins();
    }

    private function copyTuleapGeneralSettings(): void
    {
        $this->logger->info("Deploy global settings chunk in {$this->nginx_base_dir}");
        copy($this->tuleap_base_dir . '/src/etc/nginx/tuleap-managed-global-settings.conf', $this->nginx_base_dir . '/conf.d/tuleap-managed-global-settings.conf');
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

    public function deployMainNginxConf(): void
    {
        if (! $this->hasTuleapMarker()) {
            $this->backupOriginalFile($this->nginx_base_dir . '/nginx.conf');
            copy($this->tuleap_base_dir . '/src/etc/nginx/nginx.conf', $this->nginx_base_dir . '/nginx.conf');
        }
    }

    private function hasTuleapMarker(): bool
    {
        return strpos(file_get_contents($this->nginx_base_dir . '/nginx.conf'), '# Replaced for Tuleap usage') !== false;
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

    private function backupOriginalFile(string $file): void
    {
        if (! file_exists($file . '.orig')) {
            copy($file, $file . '.orig');
        }
    }

    private function createDirectoryIfNotExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755);
        }
    }
}
