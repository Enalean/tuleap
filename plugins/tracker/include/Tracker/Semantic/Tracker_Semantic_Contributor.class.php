<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Tracker_Semantic_Contributor extends Tracker_Semantic
{

    public const CONTRIBUTOR_SEMANTIC_SHORTNAME = 'contributor';

    /**
     * @var Tracker_FormElement_Field_List
     */
    protected $list_field;

    /**
     * @var self[]
     */
    private static $instances;

    /**
     * Cosntructor
     *
     * @param Tracker                        $tracker    The tracker
     * @param Tracker_FormElement_Field_List $list_field The field
     */
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field_List $list_field = null)
    {
        parent::__construct($tracker);
        $this->list_field = $list_field;
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::CONTRIBUTOR_SEMANTIC_SHORTNAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return dgettext('tuleap-tracker', 'Contributor/assignee');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'Define the contributor/assignee of an artifact');
    }

    /**
     * The Id of the (list) field used for contributor semantic
     *
     * @return int The Id of the (list) field used for contributor semantic, or 0 if no field
     */
    public function getFieldId()
    {
        if ($this->list_field) {
            return $this->list_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (list) field used for contributor semantic
     *
     * @return Tracker_FormElement_Field_List The (list) field used for contributor semantic, or null if no field
     */
    public function getField()
    {
        return $this->list_field;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return void
     */
    public function display()
    {
        echo dgettext('tuleap-tracker', '<p>The <strong>contributor(s)/assignee(s)</strong> are the person(s) who are responsible for the work needed to complete the artifact.</p>');
        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            $purifier = Codendi_HTMLPurifier::instance();
            echo sprintf(dgettext('tuleap-tracker', '<p>One will be considered as a contributor/assignee of this artifact if her name appears in the field <strong>%1$s</strong>.</p>'), $purifier->purify($field->getLabel()));
        } else {
            echo dgettext('tuleap-tracker', '<p>The artifacts of this tracker does not have any <em>contributor/assignee</em> yet.</p>');
        }
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $semantic_manager              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function displayAdmin(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $semantic_manager->displaySemanticHeader($this, $tracker_manager);
        $html = '';

        if ($list_fields = Tracker_FormElementFactory::instance()->searchUsedUserClosedListFields($this->tracker)) {
            $html .= '<form method="POST" action="' . $this->getUrl() . '">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $select = '<select name="list_field_id">';
            if (! $this->getFieldId()) {
                $select .= '<option value="" selected="selected">' . $purifier->purify(dgettext('tuleap-tracker', 'Choose a field...')) . '</option>';
            }

            foreach ($list_fields as $list_field) {
                if ($list_field->getId() == $this->getFieldId()) {
                    $selected = ' selected="selected" ';
                } else {
                    $selected = '';
                }
                $select .= '<option value="' . $purifier->purify($list_field->getId()) . '" ' . $selected . '>' . $purifier->purify($list_field->getLabel()) . '</option>';
            }
            $select .= '</select>';

            $unset_btn  = '<button type="submit" class="btn btn-danger" name="delete">';
            $unset_btn .= $purifier->purify(dgettext('tuleap-tracker', 'Unset this semantic')) . '</button>';

            $submit_btn  = '<button type="submit" class="btn btn-primary" name="update">';
            $submit_btn .= $purifier->purify($GLOBALS['Language']->getText('global', 'save_change')) . '</button>';

            if (! $this->getFieldId()) {
                $html .= dgettext('tuleap-tracker', '<p>The artifacts of this tracker does not have any <em>contributor/assignee</em> yet.</p>');
                $html .= '<p>' . $purifier->purify(dgettext('tuleap-tracker', 'Feel free to choose one:'));
                $html .= $select . ' <br> ' . $submit_btn;
                $html .= '</p>';
            } else {
                $html .= sprintf(dgettext('tuleap-tracker', '<p>One will be considered as a contributor/assignee of this artifact if her name appears in the field <strong>%1$s</strong>.</p>'), $select);
                $html .= $submit_btn . ' ' . $purifier->purify($GLOBALS['Language']->getText('global', 'or')) . ' ' . $unset_btn;
            }
            $html .= '</form>';
        } else {
            $html .= dgettext('tuleap-tracker', 'You cannot define the <em>contributor/assignee</em> semantic since there isn\'t any user list field in the tracker');
        }
        $html .= '<p><a href="' . TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&amp;func=admin-semantic">&laquo; ' . dgettext('tuleap-tracker', 'go back to semantic overview') . '</a></p>';
        echo $html;
        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $semantic_manager              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            if ($field = Tracker_FormElementFactory::instance()->getUsedUserClosedListFieldById($this->tracker, $request->get('list_field_id'))) {
                $this->list_field = $field;
                if ($this->save()) {
                    $this->sendContributorChangeEvent();
                    $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-tracker', 'The contributor/assignee is now: %1$s'), $field->getLabel()));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback(
                        'error',
                        dgettext('tuleap-tracker', 'Unable to save the contributor/assignee')
                    );
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'The field you submitted is not a user list field'));
            }
        } elseif ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            if ($this->delete()) {
                $this->sendContributorChangeEvent();
                $GLOBALS['Response']->redirect($this->getUrl());
            } else {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    dgettext('tuleap-tracker', 'Unable to save the contributor/assignee')
                );
            }
        }
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function sendContributorChangeEvent()
    {
        EventManager::instance()->processEvent(
            TRACKER_EVENT_SEMANTIC_CONTRIBUTOR_CHANGE,
            [
                'tracker' => $this->tracker,
            ]
        );
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        $dao = new Tracker_Semantic_ContributorDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    public function delete()
    {
        $dao = new Tracker_Semantic_ContributorDao();
        return $dao->delete($this->tracker->getId());
    }

    /**
     * Load an instance of a Tracker_Semantic_Contributor
     *
     *
     * @return Tracker_Semantic_Contributor
     */
    public static function load(Tracker $tracker): self
    {
        if (! isset(self::$instances[$tracker->getId()])) {
            $field_id = null;
            $dao      = new Tracker_Semantic_ContributorDao();
            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
                $field_id = $row['field_id'];
            }

            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$instances[$tracker->getId()] = new self($tracker, $field);
        }

        return self::$instances[$tracker->getId()];
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xml_mapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xml_mapping)
    {
        if ($this->getFieldId() && in_array($this->getFieldId(), $xml_mapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new \XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xml_mapping));
        }
    }

     /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        return $this->getFieldId() == $field->getId();
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Contributor $semantic_contributor, Tracker $tracker)
    {
        self::$instances[$tracker->getId()] = $semantic_contributor;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$instances = null;
    }
}
