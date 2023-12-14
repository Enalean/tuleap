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

namespace Tuleap\Test\Builders;

use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetGeneric;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\JavascriptAssetGeneric;
use Widget_Static;

final class TestLayout extends BaseLayout
{
    /**
     * @var LayoutInspector
     */
    private $inspector;
    private bool $header_has_been_written = false;

    public function __construct(LayoutInspector $inspector)
    {
        $this->css_assets = new CssAssetCollection([]);
        $this->inspector  = $inspector;
    }

    public function header(HeaderConfiguration|array $params): void
    {
        $this->header_has_been_written = true;
    }

    protected function hasHeaderBeenWritten(): bool
    {
        return $this->header_has_been_written;
    }

    public function footer(FooterConfiguration|array $params): void
    {
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
    }

    public function includeCalendarScripts()
    {
    }

    public function includeFooterJavascriptFile($file)
    {
    }

    public function addJavascriptAsset(JavascriptAssetGeneric $asset): void
    {
        $this->javascript_assets[] = $asset;
    }

    /**
     * @return JavascriptAssetGeneric[]
     */
    public function getJavascriptAssets(): array
    {
        return $this->javascript_assets;
    }

    public function addCssAsset(CssAssetGeneric $asset): void
    {
        $this->css_assets = $this->css_assets->merge(new CssAssetCollection([$asset]));
    }

    public function getCssAssets(): CssAssetCollection
    {
        return $this->css_assets;
    }

    protected function getUser()
    {
    }

    public function redirect(string $url): never
    {
        $this->inspector->setRedirectUrl($url);
    }

    public function addFeedback($level, $message, $purify = CODENDI_PURIFIER_CONVERT_HTML)
    {
        $this->inspector->addFeedback($level, $message);
    }

    public function permanentRedirect($redirect_url): never
    {
        $this->inspector->setPermanentRedirectUrl((string) $redirect_url);
    }
}
