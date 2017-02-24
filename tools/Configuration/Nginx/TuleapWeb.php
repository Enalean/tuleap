<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class TuleapWeb
{
    const SSL_CERT_KEY_PATH  = '/etc/pki/tls/private/localhost.key.pem';
    const SSL_CERT_CERT_PATH = '/etc/pki/tls/certs/localhost.cert.pem';

    private $tuleap_base_dir;
    private $nginx_base_dir;
    private $server_name;

    public function __construct($tuleap_base_dir, $nginx_base_dir, $server_name)
    {
        $this->tuleap_base_dir = $tuleap_base_dir;
        $this->nginx_base_dir  = $nginx_base_dir;
        $this->server_name     = $server_name;
    }

    public function configure()
    {
        $this->copyTuleapDotD();
        $this->copyTuleapPlugins();

        $this->deployMainNginxConf();
        $this->configureNginxTuleapServer();

        $this->generateSSLCertificate();
    }

    private function copyTuleapDotD()
    {
        $tuleap_d_dir       = $this->nginx_base_dir.'/conf.d/tuleap.d';
        $tuleap_d_base_dir  = $this->tuleap_base_dir.'/src/etc/nginx18/tuleap.d';

        $this->createDirectoryIfNotExists($tuleap_d_dir);
        foreach (new DirectoryIterator($tuleap_d_base_dir) as $file) {
            if (! $file->isDot()) {
                copy($file->getPathname(), $tuleap_d_dir.'/'.$file->getBasename());
            }
        }
    }

    private function copyTuleapPlugins()
    {
        $tuleap_plugins_dir = $this->nginx_base_dir.'/conf.d/tuleap-plugins';

        $this->createDirectoryIfNotExists($tuleap_plugins_dir);

        $plugin_conf_template = file_get_contents($this->tuleap_base_dir.'/src/etc/nginx18/plugin.conf.dist');
        foreach (new DirectoryIterator($this->tuleap_base_dir.'/plugins') as $file) {
            if (! $file->isDot()) {
                $plugin = $file->getBasename();
                $conf_file = $file->getPathname().'/etc/nginx18/'.$plugin.'.conf';
                $plugin_conf_file = $tuleap_plugins_dir.'/'.basename($conf_file);
                if (is_file($conf_file)) {
                    copy($conf_file, $plugin_conf_file);
                } else {
                    if (is_dir($file->getPathname().'/www')) {
                        file_put_contents($plugin_conf_file, str_replace('%name%', $plugin, $plugin_conf_template));
                    }
                }
            }
        }
    }

    private function generateSSLCertificate()
    {
        if (! file_exists(self::SSL_CERT_KEY_PATH)) {
            exec('openssl req -batch -nodes -x509 -newkey rsa:4096 -keyout '.self::SSL_CERT_KEY_PATH.' -out '.self::SSL_CERT_CERT_PATH.' -days 365 -subj "/C=XX/ST=SomeState/L=SomeCity/O=SomeOrganization/OU=SomeDepartment/CN='.$this->server_name.'" 2>/dev/null');
        }
    }

    private function configureNginxTuleapServer()
    {
        $template = file_get_contents($this->tuleap_base_dir.'/src/etc/nginx18/tuleap.conf.dist');
        $searches = array(
            '%ssl_certificate_key_path%',
            '%ssl_certificate_path%',
            '%sys_default_domain%',
        );
        $replaces = array(
            self::SSL_CERT_KEY_PATH,
            self::SSL_CERT_CERT_PATH,
            $this->server_name,
        );

        $conf = str_replace($searches, $replaces, $template);
        file_put_contents($this->nginx_base_dir.'/conf.d/tuleap.conf', $conf);
    }

    private function backupOriginalFile($file)
    {
        if (! file_exists($file.'.orig')) {
            copy($file, $file.'.orig');
        }
    }

    private function createDirectoryIfNotExists($directory)
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755);
        }
    }

    private function deployMainNginxConf()
    {
        if (! $this->hasTuleapMarker()) {
            $this->backupOriginalFile($this->nginx_base_dir.'/nginx.conf');
            copy($this->tuleap_base_dir.'/src/etc/nginx18/nginx.conf', $this->nginx_base_dir.'/nginx.conf');
        }
    }

    private function hasTuleapMarker()
    {
        return strpos(file_get_contents($this->nginx_base_dir.'/nginx.conf'), '# Replaced for Tuleap usage') !== false;
    }
}
