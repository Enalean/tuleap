<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class REST_TestDataBuilderNG extends REST_TestDataBuilder
{

    public function activatePlugins()
    {
        return $this;
    }

    public function generateUsers()
    {
        $user_1 = $this->user_manager->getUserByUserName(self::TEST_USER_1_NAME);
        $user_1->setPassword(self::TEST_USER_1_PASS);
        $this->user_manager->updateDb($user_1);

        $user_2 = $this->user_manager->getUserByUserName(self::TEST_USER_2_NAME);
        $user_2->setPassword(self::TEST_USER_2_PASS);
        $user_2->setAuthorizedKeys('ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDHk9 toto@marche');
        $this->user_manager->updateDb($user_2);

        $user_3 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);
        $user_3->setPassword(self::TEST_USER_3_PASS);
        $this->user_manager->updateDb($user_3);

        $user_4 = $this->user_manager->getUserByUserName(self::TEST_USER_3_NAME);
        $user_4->setPassword(self::TEST_USER_4_PASS);
        $this->user_manager->updateDb($user_4);

        return $this;
    }

    public function generateProject()
    {
        $project_1 = $this->project_manager->getProjectByUnixName(self::PROJECT_PRIVATE_MEMBER_SHORTNAME);
        $this->importTemplateInProject($project_1->getID(), 'tuleap_agiledashboard_template.xml');
        $this->importTemplateInProject($project_1->getID(), 'tuleap_agiledashboard_kanban_template.xml');

        $pbi = $this->project_manager->getProjectByUnixName(self::PROJECT_PBI_SHORTNAME);
        $this->importTemplateInProject($pbi->getId(), 'tuleap_agiledashboard_template_pbi_6348.xml');

        $backlog = $this->project_manager->getProjectByUnixName(self::PROJECT_BACKLOG_DND);
        $this->importTemplateInProject($backlog->getId(), 'tuleap_agiledashboard_template.xml');

        return $this;
    }
}
