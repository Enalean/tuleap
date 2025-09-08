<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Statistics\CSV;

use EventManager;
use PluginManager;
use Project_CustomDescription_CustomDescriptionFactory;
use Project_CustomDescription_CustomDescriptionValueDao;
use Statistics_DiskUsageManager;
use Statistics_Services_UsageFormatter;
use Statistics_ServicesUsageDao;
use TroveCatDao;
use TroveCatFactory;
use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldLabelBuilder;
use Tuleap\StatisticsCore\StatisticsServiceUsage;

class CSVBuilder
{
    public function __construct(
        private readonly Statistics_ServicesUsageDao $services_usage_dao,
        private readonly Statistics_Services_UsageFormatter $services_usage_formatter,
        private readonly Project_CustomDescription_CustomDescriptionFactory $custom_description_factory,
        private readonly Project_CustomDescription_CustomDescriptionValueDao $custom_description_value_dao,
        private readonly TroveCatDao $trove_cat_dao,
        private readonly TroveCatFactory $trove_cat_factory,
        private readonly Statistics_DiskUsageManager $disk_usage_manager,
        private readonly PluginManager $plugin_manager,
        private readonly EventManager $event_manager,
    ) {
    }

    public function buildServiceUsageCSVContent($start_date, $end_date)
    {
        //Project admin
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getIdsOfActiveProjectsBeforeEndDate(), 'Project ID');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNameOfActiveProjectsBeforeEndDate(), 'Project Name');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getShortNameOfActiveProjectsBeforeEndDate(), 'Project Short Name');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getPrivacyOfActiveProjectsBeforeEndDate(), 'Public Project');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getDescriptionOfActiveProjectsBeforeEndDate(), 'Description');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getRegisterTimeOfActiveProjectsBeforeEndDate(), 'Creation date');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getInfosFromTroveGroupLink(), 'Organization');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getAdministrators(), 'Created by');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getAdministratorsRealNames(), 'Created by (Real name)');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getAdministratorsEMails(), 'Created by (Email)');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getBuiltFromTemplateIdBeforeEndDate(), 'Template ID used for creation');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getBuiltFromTemplateNameBeforeEndDate(), 'Template name used for creation');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfUserAddedBetweenStartDateAndEndDate(), 'Users added');

        foreach ($this->custom_description_factory->getCustomDescriptions() as $custom_description) {
            $this->services_usage_formatter->buildDatas(
                $this->custom_description_value_dao->getAllDescriptionValues($custom_description->getId()),
                DescriptionFieldLabelBuilder::getFieldTranslatedName($custom_description->getName())
            );
        }

        //Trove Cats
        $mandatories_trove_cat = $this->trove_cat_factory->getMandatoryParentCategoriesUnderRoot();
        foreach ($mandatories_trove_cat as $trove_cat) {
            $this->services_usage_formatter->buildDatas(
                $this->trove_cat_dao->getMandatoryCategorySelectForAllProject($trove_cat->getId()),
                $trove_cat->getFullname()
            );
        }

        //SVN
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getSVNActivities(), 'SVN activities');

        //GIT
        $git_plugin = $this->plugin_manager->getPluginByName('git');
        if ($git_plugin && $this->plugin_manager->isPluginEnabled($git_plugin)) {
            $this->services_usage_formatter->buildDatas($this->services_usage_dao->getGitWrite(), 'GIT write');
            $this->services_usage_formatter->buildDatas($this->services_usage_dao->getGitRead(), 'GIT read');
        }

        //FRS
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getFilesPublished(), 'Files published');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getDistinctFilesPublished(), 'Distinct files published');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfDownloadedFilesBeforeEndDate(), 'Downloaded files (before end date)');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfDownloadedFilesBetweenStartDateAndEndDate(), 'Downloaded files (between start date and end date)');

        //PHPWiki
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfWikiDocuments(), 'Wiki documents');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfModifiedWikiPagesBetweenStartDateAndEndDate(), 'Modified wiki pages');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfDistinctWikiPages(), 'Distinct wiki pages');

        //Trackers v3
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfOpenArtifactsBetweenStartDateAndEndDate(), 'Open artifacts');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfClosedArtifactsBetweenStartDateAndEndDate(), 'Closed artifacts');

        //Docman
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getAddedDocumentBetweenStartDateAndEndDate(), 'Added documents');
        $this->services_usage_formatter->buildDatas($this->services_usage_dao->getDeletedDocumentBetweenStartDateAndEndDate(), 'Deleted documents');

        //CI
        $ci_plugin = $this->plugin_manager->getPluginByName('hudson');
        if ($ci_plugin && $this->plugin_manager->isPluginEnabled($ci_plugin)) {
            $this->services_usage_formatter->buildDatas($this->services_usage_dao->getProjectWithCIActivated(), 'Continuous integration activated');
            $this->services_usage_formatter->buildDatas($this->services_usage_dao->getNumberOfCIJobs(), 'Continuous integration jobs');
        }

        //Disk usage
        $this->exportDiskUsageForDate($start_date, 'Disk usage at start date (bytes)');
        $this->exportDiskUsageForDate($end_date, 'Disk usage at end date (bytes)');

        $this->event_manager->dispatch(
            new StatisticsServiceUsage($this->services_usage_formatter, $start_date, $end_date)
        );

        return $this->services_usage_formatter->exportCSV();
    }

    private function exportDiskUsageForDate($date, $column_name)
    {
        $disk_usage = $this->disk_usage_manager->returnTotalSizeOfProjects($date);

        $this->services_usage_formatter->buildDatas($disk_usage, $column_name);
    }
}
