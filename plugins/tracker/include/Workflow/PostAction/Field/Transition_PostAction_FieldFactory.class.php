<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/Workflow/PostAction/PostActionSubFactory.class.php';

/**
 * Loads and saves Field post actions
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Transition_PostAction_FieldFactory implements Transition_PostActionSubFactory
{

    /** @var Array of available post actions classes */
    protected $post_actions_classes = array(
        Transition_PostAction_Field_Date::SHORT_NAME  => 'Transition_PostAction_Field_Date',
        Transition_PostAction_Field_Int::SHORT_NAME   => 'Transition_PostAction_Field_Int',
        Transition_PostAction_Field_Float::SHORT_NAME => 'Transition_PostAction_Field_Float',
    );

    /** @var array of Transition_PostAction_FieldDao */
    private $daos;

    /** @var Tracker_FormElementFactory */
    private $element_factory;

    public function __construct(
        Tracker_FormElementFactory $element_factory,
        Transition_PostAction_Field_DateDao $date_dao,
        Transition_PostAction_Field_IntDao $int_dao,
        Transition_PostAction_Field_FloatDao $float_dao
    ) {
        $this->element_factory = $element_factory;
        $this->daos            = array(
            Transition_PostAction_Field_Date::SHORT_NAME  => $date_dao,
            Transition_PostAction_Field_Int::SHORT_NAME   => $int_dao,
            Transition_PostAction_Field_Float::SHORT_NAME => $float_dao,
        );
    }

    /**
     * @see Transition_PostActionSubFactory::saveObject()
     */
    public function saveObject(Transition_PostAction $post_action)
    {
        $short_name = $post_action->getShortName();
        $dao = $this->getDao($short_name);

        $dao->save(
            $post_action->getTransition()->getId(),
            $post_action->getFieldId(),
            $this->getValue($post_action)
        );
    }

    /**
     * @see Transition_PostActionSubFactory::loadPostActions()
     */
    public function loadPostActions(Transition $transition)
    {
        $post_actions = array();
        $post_actions_classes = $this->post_actions_classes;

        foreach ($post_actions_classes as $shortname => $klass) {
            foreach ($this->loadPostActionRows($transition, $shortname) as $row) {
                $post_actions[] = $this->buildPostAction($transition, $row, $shortname, $klass);
            }
        }
        return $post_actions;
    }

    /**
     * @return \Transition_PostAction_Field_Date[]
     */
    public function getSetDateFieldValues(Transition $transition)
    {
        return $this->buildPostActionsForShortname($transition, Transition_PostAction_Field_Date::SHORT_NAME);
    }

    /**
     * @return \Transition_PostAction_Field_Float[]
     */
    public function getSetFloatFieldValues(Transition $transition)
    {
        return $this->buildPostActionsForShortname($transition, Transition_PostAction_Field_Float::SHORT_NAME);
    }

    /**
     * @return \Transition_PostAction_Field_Int[]
     */
    public function getSetIntFieldValues(Transition $transition)
    {
        return $this->buildPostActionsForShortname($transition, Transition_PostAction_Field_Int::SHORT_NAME);
    }

    private function buildPostActionsForShortname(Transition $transition, string $shortname)
    {
        $rows         = $this->loadPostActionRows($transition, $shortname);
        $post_actions = [];
        foreach ($rows as $row) {
            $post_actions[] = $this->buildPostAction(
                $transition,
                $row,
                $shortname,
                $this->post_actions_classes[$shortname]
            );
        }
        return $post_actions;
    }

    /**
     * @see Transition_PostActionSubFactory::duplicate()
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $postactions = $this->loadPostActions($from_transition);
        foreach ($postactions as $postaction) {
            $from_field_id = $postaction->getFieldId();

            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_field_id) {
                    $to_field_id = $mapping['to'];
                    $this->getDao($postaction->getShortname())->duplicate($from_transition->getId(), $to_transition_id, $from_field_id, $to_field_id);
                }
            }
        }
    }

    /**
     * @see Transition_PostActionSubFactory::isFieldUsedInPostActions()
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        foreach (array_keys($this->post_actions_classes) as $shortname) {
            if ($this->getDao($shortname)->countByFieldId($field->getId()) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @see Transition_PostActionSubFactory::getInstanceFromXML()
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        $xml_tag_name          = $xml->getName();
        $post_action_class     = $this->getPostActionClassFromXmlTagName($xml_tag_name);
        $field_id              = $xmlMapping[(string) $xml->field_id['REF']];
        $postaction_attributes = $xml->attributes();
        $value                 = $this->getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes);

        if ($field_id) {
            return new $post_action_class($transition, 0, $field_id, $value);
        }
    }

    /**
     * Return the PostAction short name, given an XML tag name.
     *
     * @param string $xml_tag_name
     *
     * @return string
     */
    private function getShortNameFromXmlTagName($xml_tag_name)
    {
        return str_replace('postaction_', '', $xml_tag_name);
    }

    /**
     * Return the PostAction class, given an XML tag name.
     *
     * @param string $xml_tag_name
     *
     * @psalm-return class-string<Transition_PostAction>
     *
     * @throws Transition_PostAction_NotFoundException
     */
    private function getPostActionClassFromXmlTagName($xml_tag_name): string
    {
        $short_name = $this->getShortNameFromXmlTagName($xml_tag_name);

        if (! key_exists($short_name, $this->post_actions_classes)) {
            throw new Transition_PostAction_NotFoundException($short_name);
        }

        return $this->post_actions_classes[$short_name];
    }

    /**
     * Extract the PostAction value from the attributes,
     * deducing the PostAction type from the XML tag name.
     *
     * @param string $xml_tag_name
     * @param array $postaction_attributes
     *
     * @return mixed
     *
     * @throws Transition_PostAction_NotFoundException
     */
    private function getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes)
    {
        switch ($xml_tag_name) {
            case Transition_PostAction_Field_Date::XML_TAG_NAME:
                return (int) $postaction_attributes['valuetype'];
            case Transition_PostAction_Field_Int::XML_TAG_NAME:
                return (int) $postaction_attributes['value'];
            case Transition_PostAction_Field_Float::XML_TAG_NAME:
                return (float) $postaction_attributes['value'];
            default:
                throw new Transition_PostAction_NotFoundException($xml_tag_name);
        }
    }

    /**
     * Reconstitute a PostAction from database
     *
     * @param Transition $transition The transition to which this PostAction is associated
     * @param mixed      $row        The raw data (array-like)
     * @param string     $shortname  The PostAction short name
     *
     * @psalm-param class-string $klass
     *
     * @return Transition_PostAction
     */
    private function buildPostAction(Transition $transition, $row, $shortname, string $klass)
    {
        $id    = (int) $row['id'];
        $field = $this->getFieldFromRow($row);
        $value = $this->getValueFromRow($row, $shortname);

        return new $klass($transition, $id, $field, $value);
    }

    /**
     * Returns the corresponding DAO given a post action short name.
     *
     * @param string $post_action_short_name
     * @return Transition_PostAction_FieldDao
     * @throws Transition_PostAction_NotFoundException
     */
    public function getDao($post_action_short_name)
    {
        if (isset($this->daos[$post_action_short_name])) {
            return $this->daos[$post_action_short_name];
        }
        throw new Transition_PostAction_NotFoundException();
    }



    /**
     * Retrieves the field from the given PostAction database row.
     *
     * @param array $row
     *
     * @return Tracker_FormElement_Field
     */
    private function getFieldFromRow($row)
    {
        return $this->element_factory->getFormElementById((int) $row['field_id']);
    }

    /**
     * Retrieves the value (or value type) from the given PostAction database row.
     *
     * @param array $row
     * @param string $shortname
     *
     * @return mixed
     *
     * @throws Transition_PostAction_NotFoundException
     */
    private function getValueFromRow($row, $shortname)
    {
        switch ($shortname) {
            case Transition_PostAction_Field_Date::SHORT_NAME:
                return (int) $row['value_type'];
            case Transition_PostAction_Field_Int::SHORT_NAME:
                return (int) $row['value'];
            case Transition_PostAction_Field_Float::SHORT_NAME:
                return (float) $row['value'];
            default:
                throw new Transition_PostAction_NotFoundException($shortname);
        }
    }

    /**
     * XXX: PostAction value / value type should be an object representing
     * the PostAction configuration, allowing DAOs to share the same API.
     */
    private function getValue(Transition_PostAction $post_action)
    {
        $shortname = $post_action->getShortName();

        switch ($shortname) {
            case Transition_PostAction_Field_Date::SHORT_NAME:
                return $post_action->getValueType();
            case Transition_PostAction_Field_Int::SHORT_NAME:
            case Transition_PostAction_Field_Float::SHORT_NAME:
                return $post_action->getValue();
            default:
                throw new Transition_PostAction_NotFoundException($shortname);
        }
    }

    /**
     * Retrieves matching PostAction database records.
     *
     * @param Transition $transition The Transition to which the PostActions must be associated
     * @param string     $shortname  The PostAction type (short name, not class name)
     *
     * @return DataAccessResult
     */
    private function loadPostActionRows(Transition $transition, $shortname)
    {
        $dao = $this->getDao($shortname);
        return $dao->searchByTransitionId($transition->getId());
    }
}
