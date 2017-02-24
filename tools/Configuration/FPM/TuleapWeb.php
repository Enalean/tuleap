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

namespace Tuleap\Configuration\FPM;

class TuleapWeb
{

    private $application_user;

    public function __construct($application_user)
    {
        $this->application_user = $application_user;
    }

    public function configure()
    {
        if (file_exists('/etc/opt/rh/rh-php56/php-fpm.d/www.conf')) {
            rename('/etc/opt/rh/rh-php56/php-fpm.d/www.conf', '/etc/opt/rh/rh-php56/php-fpm.d/www.conf.orig');
        }
        if (file_exists('/etc/opt/rh/rh-php56/php-fpm.d/tuleap.conf')) {
            unlink('/etc/opt/rh/rh-php56/php-fpm.d/tuleap.conf');
        }
        $this->replacePlaceHolderInto(
            '/usr/share/tuleap/src/etc/fpm56/tuleap.conf',
            '/etc/opt/rh/rh-php56/php-fpm.d/tuleap.conf',
            array(
                '%application_user%'
            ),
            array(
                $this->application_user
            )
        );

        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache');
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php');
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php/session');
        $this->createDirectoryForAppUser('/var/tmp/tuleap_cache/php/wsdlcache');
    }

    private function createDirectoryForAppUser($path)
    {
        if (! is_dir($path)) {
            mkdir($path, 0700);
        }
        chown($path, $this->application_user);
        chgrp($path, $this->application_user);
    }

    private function replacePlaceHolderInto($template_path, $target_path, array $variables, array $values)
    {
        file_put_contents(
            $target_path,
            str_replace(
                $variables,
                $values,
                file_get_contents(
                    $template_path
                )
            )
        );
    }
}
