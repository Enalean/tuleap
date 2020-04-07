<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\Configure;

use TuleapCfg\Command\PermissionsDeniedException;

class ConfigureApache
{
    /**
     * @var string
     */
    private $base_directory;

    public function __construct(string $base_directory)
    {
        $this->base_directory = $base_directory;
    }

    public function configure(): bool
    {
        return $this->configureHTTPDConf($this->base_directory . '/etc/httpd/conf/httpd.conf')
            || $this->configureSSLConf($this->base_directory . '/etc/httpd/conf.d/ssl.conf');
    }

    private function configureHTTPDConf(string $file_path): bool
    {
        if (! file_exists($file_path)) {
            return false;
        }
        if (! is_writable($file_path)) {
            throw new PermissionsDeniedException($file_path . ' is not writable by current user (uid ' . posix_getuid() . ')');
        }

        $content = file_get_contents($file_path);
        $new_content = preg_replace(
            [
                '/^User.*$/m',
                '/^Group.*$/m',
                '/^Listen 80$/m',
            ],
            [
                'User codendiadm',
                'Group codendiadm',
                'Listen 127.0.0.1:8080',
            ],
            $content
        );
        if ($content !== $new_content) {
            file_put_contents($file_path, $new_content);
            return true;
        }
        return false;
    }

    private function configureSSLConf(string $file_path): bool
    {
        if (! file_exists($file_path)) {
            return false;
        }
        if (! is_writable($file_path)) {
            throw new PermissionsDeniedException($file_path . ' is not writable by current user (uid ' . posix_getuid() . ')');
        }

        $content = file_get_contents($file_path);
        $new_content = preg_replace(
            [
                '/^Listen (.*)$/m',
                '/^SSLEngine .*/m',
            ],
            [
                '#Listen $1',
                'SSLEngine off',
            ],
            $content
        );
        if ($content !== $new_content) {
            file_put_contents($file_path, $new_content);
            return true;
        }
        return false;
    }
}
