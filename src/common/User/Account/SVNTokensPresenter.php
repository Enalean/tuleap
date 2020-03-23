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

namespace Tuleap\User\Account;

use SVN_TokenPresenter;

final class SVNTokensPresenter
{
    /**
     * @var SVN_TokenPresenter[]
     * @psalm-readonly
     */
    public $svn_tokens = [];
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_svn_tokens;
    /**
     * @var string|null
     * @psalm-readonly
     */
    public $last_svn_token;

    /**
     * @param \SVN_Token[] $svn_tokens
     */
    public function __construct(array $svn_tokens, ?string $last_svn_token)
    {
        foreach ($svn_tokens as $token) {
            $this->svn_tokens[] = new SVN_TokenPresenter($token);
        }
        $this->has_svn_tokens = count($this->svn_tokens) > 0;
        $this->last_svn_token = $last_svn_token;
    }
}
