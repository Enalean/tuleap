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

namespace Tuleap\OAuth2Server\User\Account;

use Tuleap\OAuth2Server\AuthorizationServer\OAuth2ScopeDefinitionPresenter;

/**
 * @psalm-immutable
 */
final class AccountAppPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var OAuth2ScopeDefinitionPresenter[]
     */
    public $scopes;

    public function __construct(
        int $id,
        string $name,
        string $project_name,
        OAuth2ScopeDefinitionPresenter ...$scope_definition_presenters
    ) {
        $this->id           = $id;
        $this->name         = $name;
        $this->project_name = $project_name;
        $this->scopes       = $scope_definition_presenters;
    }
}
