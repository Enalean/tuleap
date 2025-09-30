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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Docman_View_Admin_FilenamePattern extends AdminView
{
    public const string IDENTIFIER = 'admin_filename_pattern';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    #[\Override]
    protected function includeStylesheets(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addCssAsset(
            new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($include_assets, 'admin-style')
        );
    }

    #[\Override]
    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset($include_assets, 'admin-filename-pattern.js')
        );
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $project_id = (int) $params['group_id'];

        $pattern_retriever = new FilenamePatternRetriever(new SettingsDAO());
        $filename_pattern  = $pattern_retriever->getPattern($project_id);

        $warning_collector = EventManager::instance()->dispatch(
            new \Tuleap\Docman\View\Admin\FilenamePatternWarningsCollector($project_id, $filename_pattern)
        );

        $renderer->renderToPage('admin/pattern-filename', [
            'pattern'     => $filename_pattern->getPattern(),
            'is_enforced' => $filename_pattern->isEnforced(),
            'csrf'        => self::getCSRFToken($project_id),
            'warnings'    => $warning_collector->getWarnings(),
            'info'        => $warning_collector->getInfo(),
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

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Filename pattern');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Apply a naming pattern on files.');
    }
}
