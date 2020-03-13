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

namespace Tuleap\Configuration\Nginx;

use DirectoryIterator;
use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Common\Exec;

class Common
{
    private $tuleap_base_dir;
    private $nginx_base_dir;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, $tuleap_base_dir, $nginx_base_dir)
    {
        $this->logger          = $logger;
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
    }

    public function generateSSLCertificate($server_name, $cert_filepath, $key_filepath)
    {
        if (! file_exists($cert_filepath)) {
            $this->logger->info("Generate self-signed certificate in $cert_filepath");
            $exec = new Exec();
            $exec->command('( cat /etc/pki/tls/openssl.cnf; echo "[SAN]" ; echo "subjectAltName=DNS:' . $server_name . '" ) | openssl req -batch -nodes -x509 -newkey rsa:4096 -keyout ' . $key_filepath . ' -out ' . $cert_filepath . ' -days 365 -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN=' . $server_name . '" -extensions SAN -config /dev/stdin 2> /dev/null');
        }
    }

    public function deployConfigurationChunks()
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}");
        $this->copyTuleapDotD();
        $this->copyTuleapPlugins();
    }

    private function copyTuleapDotD()
    {
        $this->logger->info("Deploy configuration chunks in {$this->nginx_base_dir}/conf.d/tuleap.d");
        $tuleap_d_dir       = $this->nginx_base_dir . '/conf.d/tuleap.d';
        $tuleap_d_base_dir  = $this->tuleap_base_dir . '/src/etc/nginx/tuleap.d';

        $this->createDirectoryIfNotExists($tuleap_d_dir);
        foreach (new DirectoryIterator($tuleap_d_base_dir) as $file) {
            if (! $file->isDot()) {
                copy($file->getPathname(), $tuleap_d_dir . '/' . $file->getBasename());
            }
        }
    }

    private function copyTuleapPlugins()
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

    private function writeFromTemplate(string $target_path, string $plugin_name, string $template_path) : void
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

    public function deployMainNginxConf()
    {
        if (! $this->hasTuleapMarker()) {
            $this->backupOriginalFile($this->nginx_base_dir . '/nginx.conf');
            copy($this->tuleap_base_dir . '/src/etc/nginx/nginx.conf', $this->nginx_base_dir . '/nginx.conf');
        }
    }

    private function hasTuleapMarker()
    {
        return strpos(file_get_contents($this->nginx_base_dir . '/nginx.conf'), '# Replaced for Tuleap usage') !== false;
    }

    public function replacePlaceHolderInto($template_path, $target_path, array $variables, array $values)
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

    private function backupOriginalFile($file)
    {
        if (! file_exists($file . '.orig')) {
            copy($file, $file . '.orig');
        }
    }

    private function createDirectoryIfNotExists($directory)
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755);
        }
    }
}
