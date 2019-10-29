<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\ReleaseWidget\Widget;

use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Widget;

class ProjectReleaseWidget extends Widget
{
    public const NAME = 'release';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle() : string
    {
        return dgettext('tuleap-release_widget', 'Release Widget');
    }

    public function getDescription() : string
    {
        return dgettext('tuleap-release_widget', 'A widget for release monitoring.');
    }

    public function getIcon()
    {
        return "fa-map-signs";
    }

    public function getContent() : string
    {
        $builder = ProjectReleasePresenterBuilder::build();

        $renderer = $this->getRenderer(__DIR__ . '/../../templates');

        return $renderer->renderToString(
            'releasewidget',
            $builder->getProjectReleasePresenter($this->isIE11())
        );
    }

    private function getRenderer(string $template_path) : TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    public function getJavascriptDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/releasewidget/scripts',
            '/assets/releasewidget/scripts'
        );
        return [
            ['file' => $include_assets->getFileURL('releasewidget.js')]
        ];
    }

    public function getStylesheetDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/releasewidget/themes/BurningParrot',
            '/assets/releasewidget/themes/BurningParrot'
        );

        return new CssAssetCollection([new CssAsset($include_assets, 'style')]);
    }

    public function getCategory()
    {
        return dgettext('tuleap-release_widget', 'Agile dashboard');
    }

    private function isIE11(): bool
    {
        return preg_match('~MSIE|Internet Explorer~i', $_SERVER['HTTP_USER_AGENT'])
            || (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0;') !== false
                && strpos($_SERVER['HTTP_USER_AGENT'], 'rv:11.0') !== false);
    }
}
