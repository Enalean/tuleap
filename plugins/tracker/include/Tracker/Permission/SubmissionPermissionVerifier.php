<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Permission;

use EventManager;
use PFUser;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Tracker;

final class SubmissionPermissionVerifier implements VerifySubmissionPermissions
{
    /**
     * @var array<int, bool>
     */
    private array $permission_cache = [];

    /**
     * @psalm-internal Tuleap\Tracker\Permission
     */
    public function __construct(
        private Tracker_FormElementFactory $form_element_factory,
        private EventManager $event_manager,
    ) {
    }

    private static ?self $instance;

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self(Tracker_FormElementFactory::instance(), EventManager::instance());
        }

        return self::$instance;
    }

    public function canUserSubmitArtifact(PFUser $user, Tracker $tracker): bool
    {
        if (isset($this->permission_cache[$tracker->getId()])) {
            return $this->permission_cache[$tracker->getId()];
        }

        $can_user_submit                           = $this->checkSubmissionPermissions($user, $tracker);
        $this->permission_cache[$tracker->getId()] = $can_user_submit;

        return $can_user_submit;
    }

    private function checkSubmissionPermissions(PFUser $user, Tracker $tracker): bool
    {
        if ($user->isAnonymous() || ! $tracker->userCanView($user)) {
            return false;
        }


        $can_submit = false;
        foreach ($this->form_element_factory->getUsedFields($tracker) as $form_element) {
            if ($form_element->userCanSubmit($user)) {
                $can_submit = true;
            }
        }

        if ($can_submit) {
            $event = new CanSubmitNewArtifact($user, $tracker);

            return $this->event_manager->dispatch($event)->canSubmitNewArtifact();
        }

        return false;
    }
}
