<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\PostAction\ExternalPostActionSaveObjectEvent;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoriesEvent;
use Tuleap\Tracker\Workflow\PostAction\GetExternalSubFactoryByNameEvent;
use Tuleap\Tracker\Workflow\PostAction\GetPostActionShortNameFromXmlTagNameEvent;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsFactory;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsRetriever;

/**
 * Collection of subfactories to CRUD postactions. Uniq entry point from the transition point of view.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Transition_PostActionFactory
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    private $shortnames_by_xml_tag_name = [
        Transition_PostAction_Field_Float::XML_TAG_NAME => Transition_PostAction_Field_Float::SHORT_NAME,
        Transition_PostAction_Field_Int::XML_TAG_NAME   => Transition_PostAction_Field_Int::SHORT_NAME,
        Transition_PostAction_Field_Date::XML_TAG_NAME  => Transition_PostAction_Field_Date::SHORT_NAME,
        Transition_PostAction_CIBuild::XML_TAG_NAME     => Transition_PostAction_CIBuild::SHORT_NAME,
        FrozenFields::XML_TAG_NAME                      => FrozenFields::SHORT_NAME,
        HiddenFieldsets::XML_TAG_NAME                   => HiddenFieldsets::SHORT_NAME
    ];

    /** @var Transition_PostAction_FieldFactory */
    private $postaction_field_factory;

    /** @var Transition_PostAction_CIBuildFactory */
    private $postaction_cibuild_factory;

    /** @var FrozenFieldsFactory */
    private $frozen_fields_factory;

    /** @var HiddenFieldsetsFactory */
    private $hidden_fieldsets_factory;

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $this->getSubFactories()->warmUpCacheForWorkflow($workflow);
    }

    /**
     * Load the post actions that belong to a transition
     */
    public function loadPostActions(Transition $transition): void
    {
        $this->getSubFactories()->loadPostActions($transition);
    }

    /**
     * Save a postaction object
     *
     * @param Transition_PostAction $post_action  the object to save
     *
     * @return void
     */
    public function saveObject(Transition_PostAction $post_action)
    {
        if ($post_action instanceof Transition_PostAction_Field) {
            $this->getFieldFactory()->saveObject($post_action);
        } elseif ($post_action instanceof FrozenFields) {
            $this->getFrozenFieldsFactory()->saveObject($post_action);
        } elseif ($post_action instanceof HiddenFieldsets) {
            $this->getHiddenFieldsetsFactory()->saveObject($post_action);
        } elseif ($post_action instanceof Transition_PostAction_CIBuild) {
            $this->getCIBuildFactory()->saveObject($post_action);
        } else {
            $event = new ExternalPostActionSaveObjectEvent($post_action);
            $this->event_manager->processEvent($event);
        }
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        return $this->getSubFactories()->isFieldUsedInPostActions($field);
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition the template transition
     * @param int $to_transition_id the id of the transition
     * @param array $field_mapping the field mapping
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $this->getSubFactories()->duplicate($from_transition, $to_transition_id, $field_mapping);
    }

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition       $transition     to which the postaction is attached
     *
     * @return Transition_PostAction The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        $post_actions  = [];
        foreach ($xml->children() as $child) {
            $short_name = $this->deductPostActionShortNameFromXmlTagName($child->getName());
            $subfactory = $this->getSubFactory($short_name);
            $post_actions[] = $subfactory->getInstanceFromXML($child, $xmlMapping, $transition);
        }

        return $post_actions;
    }

    /** For testing purpose */
    public function setCIBuildFactory(Transition_PostAction_CIBuildFactory $postaction_cibuild_factory)
    {
        $this->postaction_cibuild_factory = $postaction_cibuild_factory;
    }

    /** For testing purpose */
    public function setFieldFactory(Transition_PostAction_FieldFactory $postaction_field_factory)
    {
        $this->postaction_field_factory = $postaction_field_factory;
    }

    /** For testing purpose */
    public function setFrozenFieldsFactory(FrozenFieldsFactory $frozen_fields_factory)
    {
        $this->frozen_fields_factory = $frozen_fields_factory;
    }

    public function setHiddenFieldsetsFactory(HiddenFieldsetsFactory $hidden_fieldsets_factory)
    {
        $this->hidden_fieldsets_factory = $hidden_fieldsets_factory;
    }

    /**
     * @return Transition_PostActionSubFactory
     * @throws Transition_PostAction_NotFoundException
     */
    private function getSubFactory($post_action_short_name)
    {
        $field_factory = $this->getFieldFactory();
        $factories     = [
            Transition_PostAction_Field_Float::SHORT_NAME => $field_factory,
            Transition_PostAction_Field_Int::SHORT_NAME   => $field_factory,
            Transition_PostAction_Field_Date::SHORT_NAME  => $field_factory,
            Transition_PostAction_CIBuild::SHORT_NAME     => $this->getCIBuildFactory(),
            FrozenFields::SHORT_NAME                      => $this->getFrozenFieldsFactory(),
            HiddenFieldsets::SHORT_NAME                   => $this->getHiddenFieldsetsFactory()
        ];

        if (isset($factories[$post_action_short_name])) {
            return $factories[$post_action_short_name];
        }

        $event = new GetExternalSubFactoryByNameEvent($post_action_short_name);
        $this->event_manager->processEvent($event);

        $factory = $event->getFactory();
        if ($factory !== null) {
            return $factory;
        }

        throw new Transition_PostAction_NotFoundException('Invalid Post Action type');
    }

    /** @return Transition_PostAction_FieldFactory */
    private function getFieldFactory()
    {
        if (! $this->postaction_field_factory) {
            $this->postaction_field_factory = new Transition_PostAction_FieldFactory(
                Tracker_FormElementFactory::instance(),
                new Transition_PostAction_Field_DateDao(),
                new Transition_PostAction_Field_IntDao(),
                new Transition_PostAction_Field_FloatDao()
            );
        }
        return $this->postaction_field_factory;
    }

    /** @return Transition_PostAction_CIBuildFactory */
    private function getCIBuildFactory()
    {
        if (! $this->postaction_cibuild_factory) {
            $this->postaction_cibuild_factory = new Transition_PostAction_CIBuildFactory(
                new Transition_PostAction_CIBuildDao()
            );
        }
        return $this->postaction_cibuild_factory;
    }

    private function getFrozenFieldsFactory(): FrozenFieldsFactory
    {
        if (! $this->frozen_fields_factory) {
            $this->frozen_fields_factory = new FrozenFieldsFactory(
                new FrozenFieldsDao(),
                FrozenFieldsRetriever::instance(),
            );
        }
        return $this->frozen_fields_factory;
    }

    private function getHiddenFieldsetsFactory(): HiddenFieldsetsFactory
    {
        if (! $this->hidden_fieldsets_factory) {
            $this->hidden_fieldsets_factory = new HiddenFieldsetsFactory(
                new HiddenFieldsetsDao(),
                HiddenFieldsetsRetriever::instance(),
            );
        }
        return $this->hidden_fieldsets_factory;
    }

    /** @return Transition_PostActionSubFactories */
    private function getSubFactories()
    {
        $sub_factories = [
            $this->getFieldFactory(),
            $this->getCIBuildFactory(),
            $this->getFrozenFieldsFactory(),
            $this->getHiddenFieldsetsFactory(),
        ];

        $event = new GetExternalSubFactoriesEvent();
        $this->event_manager->processEvent($event);

        $sub_factories = array_merge($sub_factories, $event->getFactories());

        return new Transition_PostActionSubFactories($sub_factories);
    }

    /** @return string */
    private function deductPostActionShortNameFromXmlTagName($xml_tag_name)
    {
        if (isset($this->shortnames_by_xml_tag_name[$xml_tag_name])) {
            return $this->shortnames_by_xml_tag_name[$xml_tag_name];
        }

        $event = new GetPostActionShortNameFromXmlTagNameEvent($xml_tag_name);
        $this->event_manager->processEvent($event);

        if ($event->getPostActionShortName() !== '') {
            return $event->getPostActionShortName();
        }
    }
}
