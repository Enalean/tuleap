<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Project\Icons\EmojiCodepointConverter;

class Project extends Group implements PFO_Project  // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * The project is active
     */
    public const STATUS_ACTIVE       = 'A';
    public const STATUS_ACTIVE_LABEL = 'active';

    /**
     * The project is pending
     */
    public const STATUS_PENDING       = 'P';
    public const STATUS_PENDING_LABEL = 'pending';

    /**
     * The project is suspended
     */
    public const STATUS_SUSPENDED       = 'H';
    public const STATUS_SUSPENDED_LABEL = 'suspended';

    /**
     * The project is deleted
     */
    public const STATUS_DELETED       = 'D';
    public const STATUS_DELETED_LABEL = 'deleted';

    /**
     * The project is system
     */
    public const STATUS_SYSTEM       = 's';
    public const STATUS_SYSTEM_LABEL = 'system';

    public const SITE_NEWS_PROJECT_ID        = 46;
    public const DEFAULT_TEMPLATE_PROJECT_ID = 100;
    public const DEFAULT_ADMIN_PROJECT_ID    = 1;

    public const ACCESS_PRIVATE               = 'private';
    public const ACCESS_PRIVATE_WO_RESTRICTED = 'private-wo-restr';
    public const ACCESS_PUBLIC_UNRESTRICTED   = 'unrestricted';
    public const ACCESS_PUBLIC                = 'public';

    private $project_data_array;
    private readonly \Tuleap\Project\Service\ServiceForProjectCollection $service_collection;

    public function __construct($param)
    {
        parent::__construct($param);

        //for right now, just point our prefs array at Group's data array
        //this will change later when we split the project_data table off from groups table
        $this->project_data_array = $this->data_array;
        $this->service_collection = new \Tuleap\Project\Service\ServiceForProjectCollection($this, ServiceManager::instance());
    }

    public function getMinimalRank(): int
    {
        return $this->service_collection->getMinimalRank();
    }

    private function getServiceLink(string $short_name): string
    {
        $service = $this->getService($short_name);
        if ($service === null) {
            return '';
        }

        return $service->getUrl();
    }

    public function getService(string $service_name): ?Service
    {
        return $this->service_collection->getService($service_name);
    }

    /**
     * @return Service[]
     */
    public function getServices(): array
    {
        return $this->service_collection->getServices();
    }

    public function usesTracker(): bool
    {
        return $this->usesService(Service::TRACKERV3);
    }

    public function usesSVN(): bool
    {
        return $this->usesService(Service::SVN);
    }

    public function usesFile(): bool
    {
        return $this->usesService(Service::FILE);
    }

    //whether or not this group has opted to use news
    public function usesNews(): bool
    {
        return $this->usesService(Service::NEWS);
    }

    //whether or not this group has opted to use discussion forums
    public function usesForum(): bool
    {
        return $this->usesService(Service::FORUM);
    }

    //whether or not this group has opted to use wiki
    public function usesWiki(): bool
    {
        return $this->usesService(Service::WIKI);
    }


    // Generic versions
    public function usesService($service_short_name): bool
    {
        return $this->service_collection->usesService($service_short_name);
    }

    /**
     * This method is designed to work only with @see \Tuleap\Test\Builders\ProjectTestBuilder moreover it only works
     * to be able to use @see usesService (and friends) in tests. Using this for other service releated methods **will**
     * break.
     *
     * @param array{0: string, 1: Service}|string ...$services
     */
    public function addUsedServices(...$services): void
    {
        $this->service_collection->addUsedServices(...$services);
    }

    public function getWikiPage(): string
    {
        return $this->getServiceLink(Service::WIKI);
    }

    public function getForumPage(): string
    {
        return $this->getServiceLink(Service::FORUM);
    }

    public function isSVNMandatoryRef()
    {
        return $this->project_data_array['svn_mandatory_ref'];
    }

    public function canChangeSVNLog()
    {
        return $this->project_data_array['svn_can_change_log'];
    }

    public function getSVNpreamble()
    {
        return $this->project_data_array['svn_preamble'];
    }

    /**
     * @psalm-mutation-free
     */
    public function getAccess()
    {
        return $this->data_array['access'];
    }

    public function getTruncatedEmailsUsage()
    {
        return $this->data_array['truncated_emails'];
    }

    /**
     * @psalm-mutation-free
     */
    public function isPublic(): bool
    {
        $access = $this->data_array['access'];
        return $access !== self::ACCESS_PRIVATE && $access !== self::ACCESS_PRIVATE_WO_RESTRICTED;
    }

    /**
     * @return bool
     */
    public function allowsRestricted()
    {
        return $this->getAccess() === self::ACCESS_PUBLIC_UNRESTRICTED
            || $this->isSuperPublic();
    }

    public function isSuperPublic()
    {
        $super_public_projects = ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        return in_array($this->getID(), $super_public_projects);
    }

    /**
     * SVN root path must have project name in mixed case.
     *
     * @return String
     */
    public function getSVNRootPath()
    {
        return ForgeConfig::get('svn_prefix') . DIRECTORY_SEPARATOR . $this->getUnixNameMixedCase();
    }

    public function getProjectsCreatedFrom()
    {
        $sql         = 'SELECT * FROM `groups` WHERE built_from_template = ' . db_ei($this->getGroupId()) . " AND status <> 'D'";
        $subprojects = [];
        if ($res = db_query($sql)) {
            while ($data = db_fetch_array($res)) {
                $subprojects[] = $data;
            }
        }
        return $subprojects;
    }

    public function getProjectsDescFieldsValue()
    {
        $sql = 'SELECT group_desc_id, value FROM group_desc_value WHERE group_id=' . db_ei($this->getGroupId());

        $descfieldsvalue = [];
        if ($res = db_query($sql)) {
            while ($data = db_fetch_array($res)) {
                $descfieldsvalue[] = $data;
            }
        }

        return $descfieldsvalue;
    }

    private function getUGroupManager()
    {
        return new UGroupManager();
    }

    /**
     * @return PFUser[] of User admin of the project
     */
    public function getAdmins(?UGroupManager $ugm = null): array
    {
        if (is_null($ugm)) {
            $ugm = $this->getUGroupManager();
        }
        return $ugm->getDynamicUGroupsMembers(ProjectUGroup::PROJECT_ADMIN, $this->getID());
    }

    /**
     * @return PFUser[] array of User members of the project
     */
    public function getMembers(?UGroupManager $ugm = null)
    {
        if (is_null($ugm)) {
            $ugm = $this->getUGroupManager();
        }
        return $ugm->getDynamicUGroupsMembers(ProjectUGroup::PROJECT_MEMBERS, $this->getID());
    }

    /**
     * Alias of @see getMembers()
     */
    public function getUsers()
    {
        return $this->getMembers();
    }

    public function projectsMustBeApprovedByAdmin()
    {
        return (int) ForgeConfig::get(\ProjectManager::CONFIG_PROJECT_APPROVAL, 1) === 1;
    }

    /**
     * @return bool
     */
    public function isLegacyDefaultTemplate()
    {
        return (int) $this->getID() === self::DEFAULT_TEMPLATE_PROJECT_ID;
    }

    public function isSuspended()
    {
        return $this->getStatus() === self::STATUS_SUSPENDED;
    }

    /**
     * @psalm-mutation-free
     */
    public function getIconUnicodeCodepoint(): ?string
    {
        return $this->project_data_array['icon_codepoint'];
    }

    /**
     * @psalm-mutation-free
     */
    public function getIconAndPublicName(): string
    {
        return EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($this->getIconUnicodeCodepoint()) . ' ' . $this->getPublicName();
    }
}
