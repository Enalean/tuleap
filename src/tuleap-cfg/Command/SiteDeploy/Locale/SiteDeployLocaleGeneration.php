<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace TuleapCfg\Command\SiteDeploy\Locale;

use Symfony\Component\Console\Output\OutputInterface;
use TuleapCfg\Command\ProcessFactory;

final class SiteDeployLocaleGeneration
{
    public function __construct(
        private readonly ProcessFactory $process_factory,
    ) {
    }

    public function generate(OutputInterface $output): void
    {
        $output->writeln('<info>Compile required locales</info>');
        $locales = explode(',', \ForgeConfig::get('sys_supported_languages'));

        foreach ($locales as $locale) {
            $trim_locale = trim($locale);

            $this->process_factory->getProcess(
                ['localedef', '-i', $trim_locale, '-c', '-f', 'UTF-8', $trim_locale . '.UTF-8']
            )->mustRun();
        }
    }
}
