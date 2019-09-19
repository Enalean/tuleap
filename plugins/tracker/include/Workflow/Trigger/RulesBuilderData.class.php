<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Build all the data needed to create Trigger rules in workflow administration
 */
class Tracker_Workflow_Trigger_RulesBuilderData implements Tracker_IProvideJsonFormatOfMyself
{
    public const CONDITION_AT_LEAST_ONE = 'at_least_one';
    public const CONDITION_ALL_OFF      = 'all_of';

    /**
     * @var Tracker_FormElement_Field_List[]
     */
    private $targets;

    /**
     * @var Tracker_Workflow_Trigger_RulesBuilderTriggeringFields[]
     */
    private $triggering_fields;

    public function __construct(Iterator $targets, array $triggering_fields)
    {
        $this->targets           = $targets;
        $this->triggering_fields = $triggering_fields;
    }

    /**
     * Json format of rule builder
     *
     * Example:
     * {
     *     "targets": {
     *         "3738": {
     *              "id": "3738",
     *              "name": "status",
     *              "label": "Progress",
     *              "values": {
     *                  "4731": {
     *                      "id": "4731",
     *                      "label": "Todo",
     *                      "is_hidden": false
     *                  },
     *                  ...
     *              }
     *          },
     *          ...
     *     },
     *     "conditions: [
     *         {
     *             "name": "at_least_one"
     *             "operator: "or"
     *         },
     *     ],
     *     "triggers": {
     *          "276": {
     *              "id": "276",
     *              "name": "Tasks",
     *              "fields": {
     *                  "3741": {
     *                      "id": "3741",
     *                      "name": "status",
     *                      "label": "Progress",
     *                      "values": {
     *                          "4743": {
     *                              "id": "4743",
     *                              "label": "Todo",
     *                              "is_hidden": false
     *                          },
     *                      }
     *                  },
     *                  ...
     *              }
     *          },
     *          ...
     *     }
     * }
     *
     * @return Array
     */
    public function fetchFormattedForJson()
    {
        return array(
            'targets'        => $this->getTargets(),
            'conditions'     => $this->getConditions(),
            'triggers'       => $this->getTriggers(),
        );
    }

    private function getTargets()
    {
        return $this->getFields($this->targets);
    }

    private function getConditions()
    {
        return array(
            array(
                'name'     => self::CONDITION_AT_LEAST_ONE,
                'operator' => 'or'
            ),
            array(
                'name'     => self::CONDITION_ALL_OFF,
                'operator' => 'and'
            )
        );
    }

    private function getTriggers()
    {
        $json = array();
        foreach ($this->triggering_fields as $triggering_fields) {
            $json[$triggering_fields->getTracker()->getId()] = $this->getChildTracker($triggering_fields);
        }
        return $json;
    }

    private function getChildTracker(Tracker_Workflow_Trigger_RulesBuilderTriggeringFields $triggering_fields)
    {
        return array(
            'id'     => $triggering_fields->getTracker()->getId(),
            'name'   => $triggering_fields->getTracker()->getName(),
            'fields' => $this->getFields($triggering_fields->getFields()),
        );
    }

    private function getFields($fields)
    {
        $json = array();
        foreach ($fields as $field) {
            $json[$field->getId()] = $field->fetchFormattedForJson();
        }
        return $json;
    }
}
