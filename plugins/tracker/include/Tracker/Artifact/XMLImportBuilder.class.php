<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\MessageFactoryBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookRetriever;
use Tuleap\Tracker\Webhook\WebhookStatusLogger;
use Tuleap\Webhook\Emitter;

class Tracker_Artifact_XMLImportBuilder {

    /**
     * @return Tracker_Artifact_XMLImport
     */
    public function build(
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        Logger $logger
    ) {
        $artifact_factory      = Tracker_ArtifactFactory::instance();
        $formelement_factory   = Tracker_FormElementFactory::instance();
        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator($formelement_factory);
        $visit_recorder        = new VisitRecorder(new RecentlyVisitedDao());
        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();
        $send_notifications    = false;
        $webhook_dao           = new WebhookDao();
        $emitter               = new Emitter(
            MessageFactoryBuilder::build(),
            HttpClientFactory::createClient(),
            new WebhookStatusLogger($webhook_dao)
        );

        $webhook_retriever = new WebhookRetriever($webhook_dao);

        $artifact_creator = new Tracker_ArtifactCreator(
            $artifact_factory,
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                $formelement_factory,
                $changeset_dao,
                $artifact_factory,
                EventManager::instance(),
                $emitter,
                $webhook_retriever
            ),
            $visit_recorder
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            $formelement_factory,
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
            $emitter,
            $webhook_retriever
        );

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
            new NatureDao()
        );
    }
}
