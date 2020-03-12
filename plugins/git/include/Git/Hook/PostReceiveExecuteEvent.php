<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
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

namespace Tuleap\Git\Hook;

use GitRepository;
use PFUser;
use Tuleap\Event\Dispatchable;

/**
 * @psalm-immutable
 */
class PostReceiveExecuteEvent implements Dispatchable
{
    public const NAME = 'postReceiveExecuteEvent';

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var string
     */
    private $oldrev;

    /**
     * @var string
     */
    private $newrev;

    /**
     * @var string
     */
    private $refname;

    /**
     * @var bool
     */
    private $is_technical_reference;

    public function __construct(
        GitRepository $repository,
        PFUser $user,
        string $oldrev,
        string $newrev,
        string $refname,
        bool $is_technical_reference
    ) {
        $this->repository             = $repository;
        $this->user                   = $user;
        $this->oldrev                 = $oldrev;
        $this->newrev                 = $newrev;
        $this->refname                = $refname;
        $this->is_technical_reference = $is_technical_reference;
    }

    public function getRepository(): GitRepository
    {
        return $this->repository;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getOldrev(): string
    {
        return $this->oldrev;
    }

    public function getNewrev(): string
    {
        return $this->newrev;
    }

    public function getRefname(): string
    {
        return $this->refname;
    }

    public function isATechnicalReference(): bool
    {
        return $this->is_technical_reference;
    }
}
