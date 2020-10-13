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
 */

declare(strict_types=1);

namespace Tuleap\BrowserDetection;

/**
 * @psalm-immutable
 */
final class BrowserDeprecationMessage
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $message;

    private function __construct(string $title, string $message)
    {
        $this->title   = $title;
        $this->message = $message;
    }

    public static function fromDetectedBrowser(DetectedBrowser $detected_browser): ?self
    {
        if ($detected_browser->isIEBefore11()) {
            return new self(
                _('Your web browser is not supported'),
                _('Internet Explorer in compatibility mode is not supported, you will encounter issues if you continue. Please upgrade to a modern, fully supported browser such as Firefox, Chrome or Edge.')
            );
        }

        if ($detected_browser->isIE11()) {
            return new self(
                _('Your web browser will be unsupported soon'),
                _('Internet Explorer will be unsupported soon, some features are already not available to you. Please upgrade to a modern, fully supported browser such as Firefox, Chrome or Edge.')
            );
        }


        if ($detected_browser->isEdgeLegacy()) {
            return new self(
                _('Your web browser is not supported'),
                _('Edge Legacy is not supported. Please upgrade to the latest version of Edge or use another modern alternative such as Firefox or Chrome.')
            );
        }

        return null;
    }
}
