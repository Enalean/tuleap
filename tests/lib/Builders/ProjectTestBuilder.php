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

namespace Tuleap\Test\Builders;

use Project;
use TemplateSingleton;
use Tuleap\Project\Icons\EmojiCodepointConverter;

final class ProjectTestBuilder
{
    private $data = [
        'group_id'        => '101',
        'status'          => Project::STATUS_ACTIVE,
        'unix_group_name' => 'TestProject',
        'group_name'      => 'The Test Project',
        'icon_codepoint'  => '"\ud83d\ude2c"',
        'access'          => '',
    ];

    private ?array $used_service_short_names = null;
    private ?array $used_services            = null;
    private bool $is_error                   = false;

    public function __construct()
    {
        $this->data['type'] = (string) TemplateSingleton::PROJECT;
    }

    public static function aProject(): self
    {
        return new self();
    }

    public function build(): Project
    {
        $project = new Project($this->data);
        if ($this->used_service_short_names !== null) {
            $project->addUsedServices(...$this->used_service_short_names);
        } elseif ($this->used_services !== null) {
            $project->addUsedServices(...$this->used_services);
        }

        if ($this->is_error) {
            $project->setError('Error message');
        }

        return $project;
    }

    public function withId(int $id): self
    {
        $this->data['group_id'] = (string) $id;
        return $this;
    }

    public function withUnixName(string $unix_name): self
    {
        $this->data['unix_group_name'] = $unix_name;
        return $this;
    }

    public function withPublicName(string $name): self
    {
        $this->data['group_name'] = $name;
        return $this;
    }

    public function withStatusDeleted(): self
    {
        $this->data['status'] = Project::STATUS_DELETED;
        return $this;
    }

    public function withStatusPending(): self
    {
        $this->data['status'] = Project::STATUS_PENDING;
        return $this;
    }

    public function withStatusActive(): self
    {
        $this->data['status'] = Project::STATUS_ACTIVE;
        return $this;
    }

    public function withStatusSuspended(): self
    {
        $this->data['status'] = Project::STATUS_SUSPENDED;
        return $this;
    }

    public function withStatusCreatingFromArchive(): self
    {
        $this->data['status'] = Project::STATUS_CREATING_FROM_ARCHIVE;
        return $this;
    }

    public function withAccess(string $access): self
    {
        $this->data['access'] = $access;
        return $this;
    }

    public function withAccessPublicIncludingRestricted(): self
    {
        return $this->withAccess(\Project::ACCESS_PUBLIC_UNRESTRICTED);
    }

    public function withAccessPublic(): self
    {
        return $this->withAccess(\Project::ACCESS_PUBLIC);
    }

    public function withAccessPrivate(): self
    {
        return $this->withAccess(\Project::ACCESS_PRIVATE);
    }

    public function withAccessPrivateWithoutRestricted(): self
    {
        return $this->withAccess(\Project::ACCESS_PRIVATE_WO_RESTRICTED);
    }

    public function withIcon(string $icon_codepoint): self
    {
        $this->data['icon_codepoint'] = EmojiCodepointConverter::convertEmojiToStoreFormat($icon_codepoint);
        return $this;
    }

    public function withUsedService(string $service_short_name): self
    {
        $this->used_service_short_names[] = $service_short_name;
        return $this;
    }

    public function withServices(\Service ...$services): self
    {
        $this->used_services = [];
        foreach ($services as $service) {
            $this->used_services[] = [
                $service->getShortName(),
                $service,
            ];
        }

        return $this;
    }

    public function withoutServices(): self
    {
        $this->used_service_short_names = [];
        return $this;
    }

    public function withTypeTemplate(): self
    {
        $this->data['type'] = (string) TemplateSingleton::TEMPLATE;
        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->data['short_description'] = $description;
        return $this;
    }

    public function withTruncatedEmails(bool $truncated): self
    {
        $this->data['truncated_emails'] = $truncated;
        return $this;
    }

    public function withError(): self
    {
        $this->is_error = true;
        return $this;
    }
}
