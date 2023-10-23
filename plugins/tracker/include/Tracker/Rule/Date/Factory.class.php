<?php
/**
  * Copyright (c) Enalean, 2012-Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
/**
 * Factory of rules
 * Base class to create, retrieve, update or delete rules
 */
class Tracker_Rule_Date_Factory
{
    /**
     *
     * @var Tracker_Rule_Date_Dao
     */
    protected $dao;

    /** @var Tracker_FormElementFactory */
    private $element_factory;

    public function __construct(Tracker_Rule_Date_Dao $dao, Tracker_FormElementFactory $element_factory)
    {
        $this->dao             = $dao;
        $this->element_factory = $element_factory;
    }

    /** @return array of Tracker_FormElement_Field_Date */
    public function getUsedDateFields(Tracker $tracker)
    {
        return $this->element_factory->getUsedDateFields($tracker);
    }

    /** @return Tracker_FormElement_Field_Date|null */
    public function getUsedDateFieldById(Tracker $tracker, $field_id)
    {
        return $this->element_factory->getUsedDateFieldById($tracker, $field_id);
    }

    /**
     *
     * @param int $source_field_id
     * @param int $target_field_id
     * @param int $tracker_id
     * @param string $comparator
     * @return Tracker_Rule_Date
     */
    public function create($source_field_id, $target_field_id, $tracker_id, $comparator)
    {
        $date_rule = $this->populate(new Tracker_Rule_Date(), $tracker_id, $source_field_id, $target_field_id, $comparator);
        $this->insert($date_rule);

        return $date_rule;
    }

    /**
     * @throws Tracker_Rule_Date_InvalidComparatorException
     */
    public function insert(Tracker_Rule_Date $rule)
    {
        if (! in_array($rule->getComparator(), Tracker_Rule_Date::$allowed_comparators)) {
            throw new Tracker_Rule_Date_InvalidComparatorException('Invalid Comparator');
        }

        $rule_id = $this->dao->insert(
            $rule->getTrackerId(),
            $rule->getSourceFieldId(),
            $rule->getTargetFieldId(),
            $rule->getComparator()
        );

        $rule->setId($rule_id);
    }

    /**
     * @return bool
     */
    public function deleteById($tracker_id, $rule_id)
    {
        return $this->dao->deleteById($tracker_id, $rule_id);
    }

    /** @return Tracker_Rule_Date */
    public function getRule(Tracker $tracker, $rule_id)
    {
        $rule = $this->dao->searchById($tracker->getId(), $rule_id);
        if (! $rule) {
            return null;
        }

        return $this->populate(
            new Tracker_Rule_Date(),
            $rule['tracker_id'],
            $rule['source_field_id'],
            $rule['target_field_id'],
            $rule['comparator'],
            $rule['id']
        );
    }

    public function save(Tracker_Rule_Date $rule)
    {
        return $this->dao->save(
            $rule->getId(),
            $rule->getSourceField()->getId(),
            $rule->getTargetField()->getId(),
            $rule->getComparator()
        );
    }

    /**
     *
     * @param int $tracker_id
     * @return array An array of Tracker_Rule_Date objects
     */
    public function searchByTrackerId($tracker_id)
    {
        $rules = $this->dao->searchByTrackerId($tracker_id);

        if (! $rules) {
            return [];
        }

        $rules_array = [];

        foreach ($rules as $rule) {
            $rules_array[] = $this->populate(
                new Tracker_Rule_Date(),
                $rule['tracker_id'],
                $rule['source_field_id'],
                $rule['target_field_id'],
                $rule['comparator'],
                $rule['id']
            );
        }

        return $rules_array;
    }

    /**
     *
     * @param int $from_tracker_id
     * @param int $to_tracker_id
     * @param array $field_mapping
     */
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping)
    {
        $rows = $this->dao->searchByTrackerId($from_tracker_id);

        // Retrieve rules of tracker from
        foreach ($rows as $row) {
            // if we already have the status field, just jump to open values
            $source_field_id = $row['source_field_id'];
            $target_field_id = $row['target_field_id'];
            $comparator      = $row['comparator'];
            // walk the mapping array to get the corresponding field values for tracker TARGET
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $source_field_id) {
                    $duplicate_source_field_id = $mapping['to'];
                }
                if ($mapping['from'] == $target_field_id) {
                    $duplicate_target_field_id = $mapping['to'];
                }
            }
            $this->dao->insert($to_tracker_id, $duplicate_source_field_id, $duplicate_target_field_id, $comparator);
        }
    }

    public function exportToXml(SimpleXMLElement $root, array $xmlMapping, $tracker_id)
    {
        $date_rules = $root->addChild('date_rules');
        $rules      = $this->searchByTrackerId($tracker_id);
        foreach ($rules as $rule) {
            $source_field_id = array_search($rule->getSourceFieldId(), $xmlMapping);
            $target_field_id = array_search($rule->getTargetFieldId(), $xmlMapping);
            if ($source_field_id !== false && $target_field_id !== false) {
                $child = $date_rules->addChild('rule');
                $child->addChild('source_field')->addAttribute('REF', $source_field_id);
                $child->addChild('target_field')->addAttribute('REF', $target_field_id);
                $child->addChild('comparator')->addAttribute('type', $rule->getComparator());
            }
        }
    }

    /**
     *
     * @param int $tracker_id
     * @param int $source_field_id
     * @param int $target_field_id
     * @param string $comparator
     * @return \Tracker_Rule_Date
     */
    private function populate(Tracker_Rule_Date $date_rule, $tracker_id, $source_field_id, $target_field_id, $comparator, $id = null)
    {
        $source_field = $this->element_factory->getFormElementById($source_field_id);
        $target_field = $this->element_factory->getFormElementById($target_field_id);
        $date_rule->setTrackerId($tracker_id)
            ->setSourceFieldId($source_field_id)
            ->setSourceField($source_field)
            ->setTargetFieldId($target_field_id)
            ->setTargetField($target_field)
            ->setTrackerId($tracker_id)
            ->setComparator($comparator);

        if ($date_rule !== null) {
            $date_rule->setId($id);
        }

        return $date_rule;
    }
}
