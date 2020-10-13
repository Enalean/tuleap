<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;

class Tracker_Artifact_XMLImportBuilder // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function build(
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        \Psr\Log\LoggerInterface $logger
    ): Tracker_Artifact_XMLImport {
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $formelement_factory     = Tracker_FormElementFactory::instance();
        $artifact_link_usage_dao = new \Tuleap\Tracker\Admin\ArtifactLinksUsageDao();
        $nature_dao              = new NatureDao();
        $artifact_link_validator = new \Tuleap\Tracker\FormElement\ArtifactLinkValidator(
            $artifact_factory,
            new \Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory(
                $nature_dao,
                $artifact_link_usage_dao,
            ),
            $artifact_link_usage_dao
        );

        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($formelement_factory, $artifact_link_validator);
        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();
        $send_notifications    = false;

        $artifact_creator = TrackerArtifactCreator::build(
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                new FieldsToBeSavedInSpecificOrderRetriever($formelement_factory),
                $changeset_dao,
                $artifact_factory,
                EventManager::instance(),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($formelement_factory),
                $logger,
                ArtifactChangesetSaver::build()
            ),
            $fields_validator,
            $logger
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($formelement_factory),
            $changeset_dao,
            $changeset_comment_dao,
            $artifact_factory,
            EventManager::instance(),
            ReferenceManager::instance(),
            new SourceOfAssociationCollectionBuilder(
                new SubmittedValueConvertor(
                    Tracker_ArtifactFactory::instance(),
                    new SourceOfAssociationDetector(
                        Tracker_HierarchyFactory::instance()
                    )
                ),
                Tracker_FormElementFactory::instance()
            ),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($formelement_factory),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
            ArtifactChangesetSaver::build()
        );

        $artifact_source_id_dao = new TrackerArtifactSourceIdDao();

        return new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            Tracker_FormElementFactory::instance(),
            $user_finder,
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $logger,
            $send_notifications,
            Tracker_ArtifactFactory::instance(),
            $nature_dao,
            new XMLArtifactSourcePlatformExtractor(new Valid_HTTPURI(), $logger),
            new ExistingArtifactSourceIdFromTrackerExtractor($artifact_source_id_dao),
            $artifact_source_id_dao,
            new ExternalFieldsExtractor(EventManager::instance())
        );
    }
}
