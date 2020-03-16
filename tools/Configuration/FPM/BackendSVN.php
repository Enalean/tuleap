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

namespace Tuleap\Configuration\FPM;

use Psr\Log\LoggerInterface;
use Tuleap\Configuration\Logger\Wrapper;

class BackendSVN
{
    private $tuleap_base_dir;
    private $application_user;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, $tuleap_base_dir, $application_user)
    {
        $this->tuleap_base_dir  = $tuleap_base_dir;
        $this->application_user = $application_user;
        $this->logger           = new Wrapper($logger, 'FPM');
    }

    public function configure()
    {
        if (file_exists('/etc/opt/remi/php73/php-fpm.d/www.conf.orig')) {
            $this->logger->warning('/etc/opt/remi/php73/php-fpm.d/www.conf.orig already exists, skip FPM conf');
            return;
        }

        if (file_exists('/etc/opt/remi/php73/php-fpm.d/www.conf') &&
                filesize('/etc/opt/remi/php73/php-fpm.d/www.conf') !== 0) {
            $this->logger->info('Backup original FPM file');
            rename('/etc/opt/remi/php73/php-fpm.d/www.conf', '/etc/opt/remi/php73/php-fpm.d/www.conf.orig');
            touch('/etc/opt/remi/php73/php-fpm.d/www.conf');
        }
        if (file_exists('/etc/opt/remi/php73/php-fpm.d/tuleap.conf')) {
            $this->logger->info('Remove pre-existing tuleap.conf file');
            unlink('/etc/opt/remi/php73/php-fpm.d/tuleap.conf');
        }
        $this->logger->info('Deploy new tuleap.conf');
        $this->replacePlaceHolderInto(
            $this->tuleap_base_dir . '/src/etc/fpm73/tuleap.conf',
            '/etc/opt/remi/php73/php-fpm.d/tuleap.conf',
            array(
                '%application_user%'
            ),
            array(
                $this->application_user
            )
        );
        $this->logger->info("Done");
    }

    private function replacePlaceHolderInto($template_path, $target_path, array $variables, array $values)
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
}
