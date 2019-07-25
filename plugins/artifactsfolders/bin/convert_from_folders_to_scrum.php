#!/usr/share/tuleap/src/utils/php-launcher.sh
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

use Tuleap\ArtifactsFolders\Converter\AncestorFolderChecker;
use Tuleap\ArtifactsFolders\Converter\ArtifactsFoldersToScrumV2Converter;
use Tuleap\ArtifactsFolders\Converter\ConverterDao;
use Tuleap\ArtifactsFolders\Folder\Dao;
use Tuleap\ArtifactsFolders\Folder\HierarchyOfFolderBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'u:'; // give me a user
$usage_options .= 'h';  // help message
$usage_long_options = array(
    'help'
);

function usage()
{
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project_id -u user_name

Convert all artifacts folders links for the given project and reverse them to fit agiledashboard's scrum links.
In artifact folders, links go from the item to its folder and have the system-defined _in_folder type.
Then, disable artifacts folders usage for the project.
  Artifact 1 ---.-> Folder
               /
  Artifact 2 -'
In agiledashboard Scrum, links go from the milestone to its content items and have no type.
  Milestone -.---> Artifact 1
              \
               '-> Artifact 2

  -p <project_id> The id of the project in which to convert the links
  -u <user_name>  The user that will create the changesets on artifacts
  -h              Display this help

Long options:
  --help  Display this help

EOT;
    exit(1);
}

$arguments = getopt($usage_options, $usage_long_options);

if (isset($arguments['h']) || isset($arguments['help'])) {
    usage();
}

if (! isset($arguments['p'])) {
    usage();
} else {
    $project_id = $arguments['p'];
}

if (! isset($arguments['u'])) {
    usage();
} else {
    $username = $arguments['u'];
}

$console      = new Log_ConsoleLogger();
$user_manager = UserManager::instance();

try {
    $user = $user_manager->forceLogin($username);
    if (! $user->isMember($project_id) || ! $user->isActive()) {
        throw new RuntimeException("The user $username is not member of project $project_id");
    }

    $converter_dao    = new ConverterDao();
    $artifact_factory = Tracker_ArtifactFactory::instance();
    $nature_is_child_link_retriever = new NatureIsChildLinkRetriever(
        $artifact_factory,
        new Tracker_FormElement_Field_Value_ArtifactLinkDao()
    );
    $hierarchy_of_folder_builder = new HierarchyOfFolderBuilder(
        new Dao(),
        $nature_is_child_link_retriever,
        $artifact_factory
    );
    $ancestor_folder_checker = new AncestorFolderChecker(
        $nature_is_child_link_retriever,
        $hierarchy_of_folder_builder
    );

    $converter = new ArtifactsFoldersToScrumV2Converter(
        $converter_dao,
        $artifact_factory,
        $hierarchy_of_folder_builder,
        $console,
        $ancestor_folder_checker
    );

    $converter->convertFromArtifactsFoldersToScrumV2($project_id, $user);

    exit(0);
} catch (Exception $exception) {
    $console->error(
        get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile(
        ) . ' L' . $exception->getLine()
    );
}
exit(1);
