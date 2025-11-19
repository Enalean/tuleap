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
use Tuleap\Layout\JavascriptViteAsset;

final class AdditionalNotificationPaneContent implements Dispatchable
{
    public const string NAME = 'collectAdditionalNotificationPaneContent';

    private string $output = '';
    /**
     * @var list<JavascriptViteAsset>
     */
    private array $javascript_vite_assets = [];

    public function __construct(
        public readonly GitRepository $repository,
        public readonly HTTPRequest $request,
    ) {
    }

    public function addHTML(string $content): void
    {
        $this->output .= $content;
    }

    public function addJavascriptViteAsset(JavascriptViteAsset $asset): void
    {
        $this->javascript_vite_assets[] = $asset;
    }

    public function getContent(): string
    {
        return $this->output;
    }

    /**
     * @return list<JavascriptViteAsset>
     */
    public function getJavascriptViteAssets(): array
    {
        return $this->javascript_vite_assets;
    }
}
