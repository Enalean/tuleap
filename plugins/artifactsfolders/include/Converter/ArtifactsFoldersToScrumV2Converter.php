<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Converter;

use Psr\Log\LoggerInterface;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\ArtifactsFolders\Folder\HierarchyOfFolderBuilder;

class ArtifactsFoldersToScrumV2Converter
{
    /**
     * @var ConverterDao
     */
    private $converter_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var HierarchyOfFolderBuilder
     */
    private $hierarchy_of_folder_builder;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AncestorFolderChecker
     */
    private $ancestor_folder_checker;

    public function __construct(
        ConverterDao $converter_dao,
        Tracker_ArtifactFactory $artifact_factory,
        HierarchyOfFolderBuilder $hierarchy_of_folder_builder,
        LoggerInterface $logger,
        AncestorFolderChecker $ancestor_folder_checker
    ) {
        $this->converter_dao               = $converter_dao;
        $this->artifact_factory            = $artifact_factory;
        $this->hierarchy_of_folder_builder = $hierarchy_of_folder_builder;
        $this->logger                      = $logger;
        $this->ancestor_folder_checker     = $ancestor_folder_checker;
    }

    public function convertFromArtifactsFoldersToScrumV2($project_id, PFUser $user)
    {
        $folder_trackers = $this->converter_dao->getFolderConfigurationForProject($project_id);

        if (count($folder_trackers) === 0) {
            $this->logger->warning("No artifactsfolders configuration found for project $project_id, stopping");
            return;
        }
        /**
         * First, get all artifacts A linked to folders F with '_in_folder' nature. Iterate on A.
         * Create the reverse link from F to A (no nature).
         * (opt) delete the forward link from A to F (nature '_in_folder')
         * Does F have a parent folder F' ?
         * If yes, create a link from F' to A.
         * Does F' have a parent folder ? continue until no parent.
         */
        $links_to_add = $this->buildLinksToAddCollection($project_id);

        foreach ($links_to_add->getLinksToAddToFolder() as $folder_id => $links_for_folder) {
            $folder_artifact        = $links_for_folder["folder"];
            $to_add                 = array_keys($links_for_folder["links"]);
            $links_to_add_as_string = implode(',', $to_add);

            $this->logger->info("Adding artifact links from artifact $folder_id to $links_to_add_as_string");

            $folder_artifact->linkArtifacts($to_add, $user);
        }

        $this->logger->info("Removing artifactsfolders configuration for project $project_id");
        $this->converter_dao->disableFolderConfigurationsForProject($project_id);
    }

    private function buildLinksToAddCollection($project_id)
    {
        $collection_of_links_to_add  = new CollectionOfLinksToAdd();
        $artifacts_and_their_folders = $this->converter_dao->searchArtifactsLinkedToFolderInProject($project_id);

        foreach ($artifacts_and_their_folders as $row) {
            $item_id   = $row['item_id'];
            $folder_id = $row['folder_id'];

            $folder_artifact = $this->artifact_factory->getArtifactById($folder_id);
            if (! $folder_artifact) {
                $this->logger->warning("Folder artifact with id $folder_id not found, skipping");
                continue;
            }

            $item_artifact = $this->artifact_factory->getArtifactById($item_id);
            if (! $item_artifact) {
                $this->logger->warning("Item artifact with id $item_id not found, skipping");
                continue;
            }

            if ($this->ancestor_folder_checker->isAncestorInSameFolder($folder_artifact, $item_artifact) === true) {
                continue;
            }

            $collection_of_links_to_add->addALink($folder_artifact, $item_artifact);

            $hierarchy_of_folder = $this->hierarchy_of_folder_builder->getHierarchyOfFolder($folder_artifact);

            foreach ($hierarchy_of_folder as $parent_folder) {
                $collection_of_links_to_add->addALink($parent_folder, $item_artifact);
            }
        }

        return $collection_of_links_to_add;
    }
}
