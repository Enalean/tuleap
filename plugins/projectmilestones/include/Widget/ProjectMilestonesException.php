<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Widget;

class ProjectMilestonesException extends \Exception
{
    /**
     * @var string
     */
    private $translated_message;

    private function __construct(string $message)
    {
        parent::__construct($message);
        $this->translated_message = $message;
    }

    public static function buildBrowserIsIE11(): self
    {
        return new self(dgettext('tuleap-projectmilestones', 'The plugin is not supported under IE11. Please use a more recent browser.'));
    }

    public static function buildUserNotAccessToPrivateProject(): self
    {
        return new self(dgettext('tuleap-projectmilestones', 'This is a private project. You may only have access to private projects you are member of.'));
    }

    public static function buildUserNotAccessToProject(): self
    {
        return new self(dgettext('tuleap-projectmilestones', 'You can not access to this project.'));
    }

    public static function buildProjectDontExist(): self
    {
        return new self(dgettext('tuleap-projectmilestones', 'Project does not exist.'));
    }

    public static function buildRootPlanningDontExist(): self
    {
        return new self(dgettext('tuleap-projectmilestones', 'No root planning is defined.'));
    }

    public function getTranslatedMessage(): string
    {
        return $this->translated_message;
    }
}
