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

namespace TuleapCfg\Command\SiteDeploy;

use ForgeConfig;
use Symfony\Component\Console\Output\OutputInterface;

final class SiteDeployImages
{
    private const LOGOS = [
        'organization_logo.png',
        'organization_logo_small.png',
        'organization_logo_mail.png',
    ];

    private const BURNING_PARROT_IMAGES_PATH = __DIR__ . '/../../../www/themes/BurningParrot/images';

    private const COMMON_IMAGES_PATH = __DIR__ . '/../../../www/themes/common/images';

    /**
     * @var string
     */
    private $theme_dir;
    /**
     * @var string
     */
    private $images_dir;
    /**
     * @var string
     */
    private $tuleap_unix_user;

    public function __construct()
    {
        $this->theme_dir        = ForgeConfig::get('sys_custom_dir') . '/themes/common/images';
        $this->images_dir       = ForgeConfig::get('sys_data_dir') . '/images';
        $this->tuleap_unix_user = ForgeConfig::get('sys_http_user');
    }

    public function deploy(OutputInterface $output): void
    {
        foreach (self::LOGOS as $logo_name) {
            $dst_file = $this->images_dir . '/' . $logo_name;
            $logo = $this->theme_dir . '/' . $logo_name;
            if (! file_exists($dst_file)) {
                if (file_exists($logo)) {
                    $output->writeln(sprintf('<info>Copy custom %s to %s</info>', $logo, $dst_file));
                    copy($logo, $dst_file);
                    $output->writeln(sprintf('<comment>%s is no longer used, you can delete this file</comment>', $logo));
                } elseif (file_exists(self::BURNING_PARROT_IMAGES_PATH . '/' . $logo_name)) {
                    $output->writeln(sprintf('<info>Copy platform default logo to %s</info>', $dst_file));
                    copy(self::BURNING_PARROT_IMAGES_PATH . '/' . $logo_name, $dst_file);
                } elseif (file_exists(self::COMMON_IMAGES_PATH . '/' . $logo_name)) {
                    $output->writeln(sprintf('<info>Copy platform default logo to %s</info>', $dst_file));
                    copy(self::COMMON_IMAGES_PATH . '/' . $logo_name, $dst_file);
                }
            } else {
                $output->writeln(sprintf('<info>%s already exists, skipping</info>', $dst_file));
                if (file_exists($logo)) {
                    $output->writeln(sprintf('<comment>%s is no longer used, you can delete this file</comment>', $logo));
                }
            }
            chmod($dst_file, 0644);
            chown($dst_file, $this->tuleap_unix_user);
            chgrp($dst_file, $this->tuleap_unix_user);
        }
    }
}
