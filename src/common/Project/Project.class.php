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

    public const ACCESS_PRIVATE               = 'private';
    public const ACCESS_PRIVATE_WO_RESTRICTED = 'private-wo-restr';
    public const ACCESS_PUBLIC_UNRESTRICTED   = 'unrestricted';
    public const ACCESS_PUBLIC                = 'public';

    private $project_data_array;

    // All data concerning services for this project
    private $service_data_array = null;
    private $cache_active_services;
    private $services;

    /**
     * @var array The classnames for services
     */
    private $serviceClassnames;

    public function __construct($param)
    {
        parent::__construct($param);

        //for right now, just point our prefs array at Group's data array
        //this will change later when we split the project_data table off from groups table
        $this->project_data_array = $this->data_array;
    }

    private function cacheServiceClassnames()
    {
        if ($this->serviceClassnames !== null) {
            return;
        }

        $this->serviceClassnames = [
            Service::FILE => ServiceFile::class,
            Service::SVN  => ServiceSVN::class,
            Service::CVS  => \Tuleap\ConcurrentVersionsSystem\ServiceCVS::class,
            Service::ML   => \Tuleap\MailingList\ServiceMailingList::class,
        ];

        EventManager::instance()->processEvent(
            Event::SERVICE_CLASSNAMES,
            ['classnames' => &$this->serviceClassnames]
        );
    }

    private function cacheServices()
    {
        if ($this->services !== null) {
            return;
        }

        $this->cacheServiceClassnames();

        // Get Service data
        $allowed_services = ServiceManager::instance()->getListOfAllowedServicesForProject($this);
        if (count($allowed_services) < 1) {
            $this->service_data_array = [];
        }
        $j = 1;
        foreach ($allowed_services as $service) {
            $res_row    = $service->data;
            $short_name = $service->getShortName();
            if (! $short_name) {
                $short_name = $j++;
            }

            $res_row['label']       = $service->getInternationalizedName();
            $res_row['description'] = $service->getInternationalizedDescription();

            $this->service_data_array[$short_name] = $res_row;
            $this->services[$short_name]           = $service;

            if ($service->isActive()) {
                $this->cache_active_services[] = $service;
            }
        }
    }

    public function getMinimalRank()
    {
        // get it, no matter if summary is enabled or not
        $this->cacheServices();
        return isset($this->services[Service::SUMMARY]) ? $this->services[Service::SUMMARY]->getRank() : 1;
    }

    private function getServiceLink($short_name)
    {
        $service = $this->getService($short_name);
        if ($service === null) {
            return '';
        }

        return $service->getUrl();
    }

    private function getServicesData()
    {
        $this->cacheServices();
        return $this->service_data_array;
    }

    /**
     * Return the name of the class to instantiate a service based on its short name
     *
     * @param string $short_name the short name of the service
     *
     * @psalm-return class-string
     */
    public function getServiceClassName($short_name): string
    {
        if (! $short_name) {
            return \Tuleap\Project\Service\ProjectDefinedService::class;
        }

        $this->cacheServiceClassnames();

        $classname = Service::class;
        if (isset($this->serviceClassnames[$short_name])) {
            $classname = $this->serviceClassnames[$short_name];
        }

        return $classname;
    }

    public function getService(string $service_name): ?Service
    {
        $this->cacheServices();
        return $this->usesService($service_name) ? $this->services[$service_name] : null;
    }

    /**
     *
     * @return array
     */
    public function getAllUsedServices()
    {
        $used_services = [];
        foreach ($this->getServices() as $service) {
            if ($service->isUsed()) {
                $used_services[] = $service->getShortName();
            }
        }

        return $used_services;
    }

    /**
     * @return Service[]
     */
    public function getServices()
    {
        $this->cacheServices();
        return $this->services;
    }

    /**
     * @return Service[]
     */
    public function getActiveServices(): array
    {
        $this->cacheServices();
        return $this->cache_active_services;
    }

    public function getFileService(): ?ServiceFile
    {
        $this->cacheServices();
        return $this->usesService(Service::FILE) ? $this->services[Service::FILE] : null;
    }

    public function usesHomePage()
    {
        return $this->usesService(Service::HOMEPAGE);
    }

    public function usesAdmin()
    {
        return $this->usesService(Service::ADMIN);
    }

    public function usesSummary()
    {
        return $this->usesService(Service::SUMMARY);
    }

    public function usesTracker()
    {
        return $this->usesService(Service::TRACKERV3);
    }

    public function usesCVS()
    {
        return $this->usesService(Service::CVS);
    }

    public function usesSVN()
    {
        return $this->usesService(Service::SVN);
    }

    public function usesFile()
    {
        return $this->usesService(Service::FILE);
    }

    //whether or not this group has opted to use mailing lists
    public function usesMail()
    {
        return $this->usesService(Service::ML);
    }

    //whether or not this group has opted to use news
    public function usesNews()
    {
        return $this->usesService(Service::NEWS);
    }

    //whether or not this group has opted to use discussion forums
    public function usesForum()
    {
        return $this->usesService(Service::FORUM);
    }

    //whether or not this group has opted to use wiki
    public function usesWiki()
    {
        return $this->usesService(Service::WIKI);
    }


    // Generic versions
    public function usesService($service_short_name)
    {
        $data = $this->getServicesData();
        return isset($data[$service_short_name]) && $data[$service_short_name]['is_used'];
    }

    /**
     * This method is designed to work only with @see \Tuleap\Test\Builders\ProjectTestBuilder moreover it only works
     * to be able to use @see usesService (and friends) in tests. Using this for other service releated methods **will**
     * break.
     *
     * @psalm-internal Tuleap\Test\Builders
     */

    /**
     * @param array{0: string, 1: Service}|string ...$services
     *
     */
    public function addUsedServices(...$services): void
    {
        if ($this->services !== null || $this->service_data_array !== null) {
            throw new LogicException('This method is not supposed to be called after caching of Services');
        }

        $this->service_data_array = [];
        $this->services           = []; // Gonna break tests that rely on Services but needed to stop caching in @see cacheServices
        foreach ($services as $service) {
            if (is_string($service)) {
                $this->service_data_array[$service] = ['is_used' => true];
            } else {
                $this->service_data_array[$service[0]] = ['is_used' => true];
                $this->services[$service[0]]           = $service[1];
            }
        }
    }

    /*
        The URL for this project's home page
    */
    public function getHomePage()
    {
        return $this->usesHomePage() ? $this->getServiceLink(Service::HOMEPAGE) : '';
    }

    public function getWikiPage()
    {
        return $this->getServiceLink(Service::WIKI);
    }

    public function getForumPage()
    {
        return $this->getServiceLink(Service::FORUM);
    }

    public function getMailPage()
    {
        return $this->getServiceLink(Service::ML);
    }

    public function getCvsPage()
    {
        return $this->getServiceLink(Service::CVS);
    }

    public function getSvnPage()
    {
        return $this->getServiceLink(Service::SVN);
    }

    public function getTrackerPage()
    {
        return $this->getServiceLink(Service::TRACKERV3);
    }

    /*

    Subversion and CVS settings

    */

    public function cvsMailingList()
    {
        return $this->project_data_array['cvs_events_mailing_list'];
    }

    public function getCVSMailingHeader()
    {
        return $this->project_data_array['cvs_events_mailing_header'];
    }

    public function isCVSTracked()
    {
        return $this->project_data_array['cvs_tracker'];
    }

    public function getCVSWatchMode()
    {
        return $this->project_data_array['cvs_watch_mode'];
    }

    public function getCVSpreamble()
    {
        return $this->project_data_array['cvs_preamble'];
    }

    public function isCVSPrivate()
    {
        return $this->project_data_array['cvs_is_private'];
    }

    public function getSVNMailingHeader()
    {
        return $this->project_data_array['svn_events_mailing_header'];
    }

    public function isSVNTracked()
    {
        return $this->project_data_array['svn_tracker'];
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

    public function isSVNPrivate()
    {
        // TODO XXXX not implemented yet.
        return false;
    }

    public function getSVNAccess()
    {
        return $this->project_data_array['svn_accessfile'];
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
