<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use GitRepository;
use Tuleap\Event\Dispatchable;
use Tuleap\HTTPRequest;
use Tuleap\Layout\JavascriptAssetGeneric;

final class AdditionalNotificationPaneContent implements Dispatchable
{
    public const string NAME = 'collectAdditionalNotificationPaneContent';

    private string $html = '';
    /**
     * @var list<JavascriptAssetGeneric> $assets
     */
    private array $assets = [];

    public function __construct(
        public readonly GitRepository $repository,
        public readonly HTTPRequest $request,
    ) {
    }

    public function addHTML(string $content): void
    {
        $this->html .= $content;
    }

    public function addJavascriptAsset(JavascriptAssetGeneric $asset): void
    {
        $this->assets[] = $asset;
    }

    public function getHTML(): string
    {
        return $this->html;
    }

    /**
     * @return list<JavascriptAssetGeneric>
     */
    public function getJavascriptAssets(): array
    {
        return $this->assets;
    }
}
