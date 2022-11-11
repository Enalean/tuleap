<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
use TuleapCfg\Command\ProcessFactory;
use TuleapCfg\Command\SiteDeploy\Nginx\SiteDeployNginx;

final class CATrust
{
    private const EXTRA_CA    = '/extra_ca.pem';
    private const ANCHOR_PATH = '/etc/pki/ca-trust/source/anchors';

    public function __construct(private ProcessFactory $process_factory, private LoggerInterface $logger)
    {
    }

    public function update(): void
    {
        if (file_exists(self::EXTRA_CA)) {
            $this->logger->info('Extra CA file detected, update system trust');
            $this->copyToAnchor(self::EXTRA_CA);
        }

        if (! file_exists(self::ANCHOR_PATH . '/' . basename(SiteDeployNginx::SSL_CERT_CERT_PATH))) {
            $this->copyToAnchor(SiteDeployNginx::SSL_CERT_CERT_PATH);
        }

        $this->process_factory->getProcessWithoutTimeout(['/usr/bin/update-ca-trust'])->mustRun();
    }

    private function copyToAnchor(string $source_file): void
    {
        if (! copy($source_file, self::ANCHOR_PATH . '/' . basename($source_file))) {
            $this->logger->error(sprintf('Cannot copy `%s` to `%s`', $source_file, self::ANCHOR_PATH));
        }
    }
}
