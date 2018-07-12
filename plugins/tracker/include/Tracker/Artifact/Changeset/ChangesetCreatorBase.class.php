<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Webhook\Emitter;

/**
 * I am a Template Method to create an initial changeset.
 */
abstract class Tracker_Artifact_Changeset_ChangesetCreatorBase {


    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    protected $fields_validator;

    /** @var Tracker_FormElementFactory */
    protected $formelement_factory;

    /** @var Tracker_ArtifactFactory */
    protected $artifact_factory;

    /** @var Tracker_Artifact_Changeset_ChangesetDataInitializator */
    protected $field_initializator;

    /** @var EventManager */
    protected $event_manager;

    /**
     * @var Emitter
     */
    private $emitter;

    /**
     * @var WebhookFactory
     */
    private $webhook_factory;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_FormElementFactory                 $formelement_factory,
        Tracker_ArtifactFactory                    $artifact_factory,
        EventManager                               $event_manager,
        Emitter                                    $emitter,
        WebhookFactory                             $webhook_factory
    ) {
        $this->fields_validator    = $fields_validator;
        $this->formelement_factory = $formelement_factory;
        $this->artifact_factory    = $artifact_factory;
        $this->event_manager       = $event_manager;
        $this->field_initializator = new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory);
        $this->emitter             = $emitter;
        $this->webhook_factory     = $webhook_factory;
    }

    /**
     * @return bool
     */
    protected function isFieldSubmitted(Tracker_FormElement_Field $field, array $fields_data) {
        return isset($fields_data[$field->getId()]);
    }

    /**
     * Should we move this method outside of changeset creation
     * so that we can remove the dependency on artifact factory
     * and enforce SRP ?
     */
    protected function saveArtifactAfterNewChangeset(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        if ($this->artifact_factory->save($artifact)) {
            $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
            foreach ($used_fields as $field) {
                $field->postSaveNewChangeset($artifact, $submitter, $new_changeset, $previous_changeset);
            }

            $artifact->getWorkflow()->after($fields_data, $new_changeset, $previous_changeset);

            return true;
        }

        return false;
    }

    public function emitWebhooks(Tracker_Artifact $artifact, PFUser $user, $action)
    {
        $tracker  = $artifact->getTracker();
        $webhooks = $this->webhook_factory->getWebhooksForTracker($tracker);

        if (count($webhooks) === 0) {
            return;
        }

        $payload  = new \Tuleap\Tracker\Webhook\ArtifactPayload($artifact, $user, $action);
        $this->emitter->emit($payload, ...$webhooks);
    }
}