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

namespace Tuleap\OAuth2Server\App;

final class OAuth2App
{
    /**
     * @var int
     * @psalm-readonly
     */
    private $id;
    /**
     * @var string
     * @psalm-readonly
     */
    private $name;
    /**
     * @var string
     * @psalm-readonly
     */
    private $redirect_endpoint;
    /**
     * @var bool
     * @psalm-readonly
     */
    private $use_pkce;
    /**
     * @var \Project
     * @psalm-readonly
     */
    private $project;

    public function __construct(int $id, string $name, string $redirect_endpoint, bool $use_pkce, \Project $project)
    {
        $this->id                = $id;
        $this->name              = $name;
        $this->use_pkce          = $use_pkce;
        $this->project           = $project;
        $this->redirect_endpoint = $redirect_endpoint;
    }

    /**
     * @psalm-mutation-free
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @psalm-mutation-free
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRedirectEndpoint(): string
    {
        return $this->redirect_endpoint;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): \Project
    {
        return $this->project;
    }

    public function isUsingPKCE(): bool
    {
        return $this->use_pkce;
    }
}
