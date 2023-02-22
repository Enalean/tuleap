<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Instrument\Prometheus\Prometheus;

class InvitationInstrumentation
{
    private const INVITATION_METRIC_NAME           = 'user_invitations_total';
    private const INVITATION_METRIC_HELP           = 'Total number of invitations sent by users';
    private const INVITATION_COMPLETED_METRIC_NAME = 'completed_user_invitations_total';
    private const INVITATION_COMPLETED_METRIC_HELP = 'Total number of completed user invitations';

    public function __construct(private Prometheus $prometheus)
    {
    }

    public function incrementProjectInvitation(): void
    {
        $this->prometheus->increment(self::INVITATION_METRIC_NAME, self::INVITATION_METRIC_HELP, ['type' => 'project']);
    }

    public function incrementPlatformInvitation(): void
    {
        $this->prometheus->increment(self::INVITATION_METRIC_NAME, self::INVITATION_METRIC_HELP, ['type' => 'platform']);
    }

    public function incrementUsedInvitation(): void
    {
        $this->prometheus->increment(self::INVITATION_COMPLETED_METRIC_NAME, self::INVITATION_COMPLETED_METRIC_HELP, ['type' => 'used']);
    }

    public function incrementCompletedInvitation(): void
    {
        $this->prometheus->increment(self::INVITATION_COMPLETED_METRIC_NAME, self::INVITATION_COMPLETED_METRIC_HELP, ['type' => 'completed']);
    }

    /**
     * @psalm-param positive-int $nb
     */
    public function incrementExpiredInvitations(int $nb): void
    {
        $this->prometheus->incrementBy(self::INVITATION_COMPLETED_METRIC_NAME, self::INVITATION_COMPLETED_METRIC_HELP, $nb, ['type' => 'expired']);
    }
}
