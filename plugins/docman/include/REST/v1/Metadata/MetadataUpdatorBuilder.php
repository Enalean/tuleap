<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_SettingsBo;
use Tuleap\Docman\Metadata\DocmanMetadataInputValidator;
use Tuleap\Docman\Metadata\DocmanMetadataTypeValueFactory;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\Metadata\MetadataRecursiveUpdator;
use Tuleap\Docman\Metadata\MetadataValueObjectFactory;
use Tuleap\Docman\Metadata\MetadataValueStore;
use Tuleap\Docman\Metadata\MetadataValueUpdator;
use Tuleap\Docman\Metadata\Owner\OwnerRetriever;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

class MetadataUpdatorBuilder
{
    public static function build(\Project $project, \EventManager $event_manager): MetadataUpdator
    {
        $user_manager = \UserManager::instance();
        $docman_metadata_factory      = new \Docman_MetadataFactory($project->getGroupId());

        $metadata_value_dao = new \Docman_MetadataValueDao();

        $list_values_builder = new MetadataListOfValuesElementListBuilder(new \Docman_MetadataListOfValuesElementDao());

        return new MetadataUpdator(
            new \Docman_ItemFactory(),
            new ItemStatusMapper(new Docman_SettingsBo($project->getID())),
            new HardcodedMetadataObsolescenceDateRetriever(
                new HardcodedMetdataObsolescenceDateChecker(
                    new Docman_SettingsBo($project->getID())
                )
            ),
            $user_manager,
            new OwnerRetriever($user_manager),
            new MetadataEventProcessor($event_manager),
            new MetadataRecursiveUpdator(
                new \Docman_MetadataFactory($project->getID()),
                \Docman_PermissionsManager::instance($project->getID()),
                new \Docman_MetadataValueFactory($project->getID()),
                \ReferenceManager::instance()
            ),
            new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO()),
            new CustomMetadataRepresentationRetriever(
                $docman_metadata_factory,
                $list_values_builder,
                new CustomMetadataCollectionBuilder($docman_metadata_factory, $list_values_builder)
            ),
            new MetadataValueUpdator(
                new DocmanMetadataInputValidator(),
                new MetadataValueObjectFactory(new DocmanMetadataTypeValueFactory()),
                $metadata_value_dao,
                new MetadataValueStore($metadata_value_dao, \ReferenceManager::instance())
            )
        );
    }
}
