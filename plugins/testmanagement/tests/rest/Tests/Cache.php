<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\Tests;

final class Cache
{
    private static ?self $instance = null;

    private ?array $valid_campaign                  = null;
    private ?array $closed_campaign                 = null;
    private ?array $valid_with_attachments_campaign = null;

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getValidCampaign(): ?array
    {
        return $this->valid_campaign;
    }

    public function getClosedCampaign(): ?array
    {
        return $this->closed_campaign;
    }

    public function setValidCampaign(array $valid_campaign): void
    {
        $this->valid_campaign = $valid_campaign;
    }

    public function setClosedCampaign(array $closed_campaign): void
    {
        $this->closed_campaign = $closed_campaign;
    }

    public function getValidWithAttachmentsCampaign(): ?array
    {
        return $this->valid_with_attachments_campaign;
    }

    public function setValidWithAttachmentsCampaign(?array $valid_with_attachments_campaign): void
    {
        $this->valid_with_attachments_campaign = $valid_with_attachments_campaign;
    }
}
