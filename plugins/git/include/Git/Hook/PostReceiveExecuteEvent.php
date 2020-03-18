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

class PostReceiveExecuteEvent implements Dispatchable
{
    public const NAME = 'postReceiveExecuteEvent';

    /**
     * @var GitRepository
     * @psalm-readonly
     */
    private $repository;

    /**
     * @var PFUser
     * @psalm-readonly
     */
    private $user;

    /**
     * @var string
     * @psalm-readonly
     */
    private $oldrev;

    /**
     * @var string
     * @psalm-readonly
     */
    private $newrev;

    /**
     * @var string
     * @psalm-readonly
     */
    private $refname;

    /**
     * @var bool
     * @psalm-readonly
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

    /**
     * @psalm-mutation-free
     */
    public function getRepository(): GitRepository
    {
        return $this->repository;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOldrev(): string
    {
        return $this->oldrev;
    }

    /**
     * @psalm-mutation-free
     */
    public function getNewrev(): string
    {
        return $this->newrev;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRefname(): string
    {
        return $this->refname;
    }

    /**
     * @psalm-mutation-free
     */
    public function isATechnicalReference(): bool
    {
        return $this->is_technical_reference;
    }
}
