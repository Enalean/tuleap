<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference;

use Tuleap\Event\Dispatchable;

class ReferenceAdministrationWarningsCollectorEvent implements Dispatchable
{
    public const NAME = 'referenceAdministrationWarningsCollectorEvent';

    /**
     * @var \Reference[]
     */
    private $project_references;

    /**
     * @var string[]
     */
    private $warning_messages = [];

    /**
     * @param \Reference[] $project_references
     */
    public function __construct(array $project_references)
    {
        $this->project_references = $project_references;
    }

    /**
     * @return string[]
     */
    public function getWarningMessages(): array
    {
        return $this->warning_messages;
    }

    public function addWarningMessage(string $warning_message): void
    {
        $this->warning_messages[] = $warning_message;
    }

    /**
     * @return \Reference[]
     */
    public function getProjectReferences(): array
    {
        return $this->project_references;
    }
}
