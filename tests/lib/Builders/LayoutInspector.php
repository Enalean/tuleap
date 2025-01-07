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

final class LayoutInspector
{
    /** @var list<array{level:string, message:string}> */
    private array $feedbacks = [];

    public function setRedirectUrl(string $redirect_url): never
    {
        throw new LayoutInspectorRedirection($redirect_url);
    }

    public function addFeedback(string $level, string $message): void
    {
        $this->feedbacks[] = [
            'level'   => $level,
            'message' => $message,
        ];
    }

    /**
     * @return list<array{level:string, message:string}>
     */
    public function getFeedback(): array
    {
        return $this->feedbacks;
    }

    public function setPermanentRedirectUrl(string $redirect_url): never
    {
        throw new LayoutInspectorPermanentRedirection($redirect_url);
    }
}
