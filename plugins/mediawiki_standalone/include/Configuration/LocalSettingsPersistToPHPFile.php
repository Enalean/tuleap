<?php
/**
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
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Configuration;

use Psl\File\WriteMode;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\PHPStringMustacheRenderer;

final class LocalSettingsPersistToPHPFile implements LocalSettingsPersist
{
    private const FILE_NAME = 'LocalSettings.local.php';

    public function __construct(private string $path_setting_directory, private PHPStringMustacheRenderer $renderer)
    {
    }

    public function persist(LocalSettingsRepresentation $representation): void
    {
        $path = $this->path_setting_directory . '/' . self::FILE_NAME;

        try {
            \Psl\Filesystem\create_file($path);
            \Psl\Filesystem\change_permissions($path, 0600);
            \Psl\File\write(
                $path,
                $this->renderer->renderToString('local-settings-tuleap-php', $representation),
                WriteMode::TRUNCATE
            );
        } catch (\RuntimeException $ex) {
            throw new CannotPersistLocalSettings(sprintf('Cannot write the LocalSettings to %s', $path), $ex);
        }

        $application_user_login = \ForgeConfig::getApplicationUserLogin();
        $chown_success          = chown($path, $application_user_login);
        if (! $chown_success) {
            throw new CannotPersistLocalSettings(sprintf('Cannot set owner of %s to %s', $path, $application_user_login));
        }
    }
}
