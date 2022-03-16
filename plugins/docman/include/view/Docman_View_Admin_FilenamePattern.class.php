<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

use Tuleap\Docman\FilenamePattern\FilenamePatternRetriever;
use Tuleap\Docman\Settings\SettingsDAO;
use Tuleap\Docman\View\Admin\AdminView;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_View_Admin_FilenamePattern extends AdminView
{
    public const IDENTIFIER = "admin_filename_pattern";

    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $project_id = (int) $params['group_id'];

        $pattern_retriever = new FilenamePatternRetriever(new SettingsDAO());
        $pattern           = $pattern_retriever->getPattern($project_id);

        $warning_collector = EventManager::instance()->dispatch(
            new \Tuleap\Docman\View\Admin\FilenamePatternWarningsCollector($project_id, $pattern)
        );

        $renderer->renderToPage('admin/pattern-filename', [
            'pattern'  => $pattern,
            'csrf'     => self::getCSRFToken($project_id),
            'warnings' => $warning_collector->getWarnings(),
        ]);
    }

    public static function getCSRFToken(int $project_id): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(
            DOCMAN_BASE_URL . '/?' . http_build_query([
                'group_id' => $project_id,
                'action'   => self::IDENTIFIER,
            ])
        );
    }

    protected function isBurningParrotCompatiblePage(): bool
    {
        return true;
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'File Pattern');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Apply a naming pattern on files.');
    }
}
