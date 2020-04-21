<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\BranchUpdate;

use PHPUnit\Framework\TestCase;

final class CommitPresenterTest extends TestCase
{
    public function test12HexDigitsAreKeptForShortReferences(): void
    {
        $presenter = new CommitPresenter('230549fc4be136fcae6ea6ed574c2f5c7b922346', 'My title', 'https://example.com/commit-link');

        $this->assertEquals('230549fc4be1', $presenter->short_reference);
        $this->assertEquals('My title', $presenter->title);
        $this->assertEquals('https://example.com/commit-link', $presenter->url);
    }
}
