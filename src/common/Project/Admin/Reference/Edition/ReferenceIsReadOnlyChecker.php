<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference\Edition;

use Event;

final class ReferenceIsReadOnlyChecker implements CheckReferenceIsReadOnly
{
    public function __construct(private readonly \EventManager $event_manager)
    {
    }

    #[\Override]
    public function isReferenceReadOnly(\Reference $reference): bool
    {
        $can_be_edited = true;
        $this->event_manager->processEvent(
            Event::GET_REFERENCE_ADMIN_CAPABILITIES,
            [
                'reference'     => $reference,
                'can_be_edited' => &$can_be_edited,
            ]
        );


        return ! $can_be_edited ||
            ($reference->isSystemReference() && (int) $reference->getGroupId() !== 100) ||
            $reference->getServiceShortName() !== '';
    }
}
