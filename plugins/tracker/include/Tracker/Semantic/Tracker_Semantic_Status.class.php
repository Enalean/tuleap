<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU GeLneral Public License as published by
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

use Tuleap\Tracker\REST\SemanticStatusRepresentation;
use Tuleap\Tracker\Semantic\SemanticStatusCanBeDeleted;
use Tuleap\Tracker\Semantic\SemanticStatusFieldCanBeUpdated;
use Tuleap\Tracker\Semantic\SemanticStatusGetDisabledValues;

class Tracker_Semantic_Status extends Tracker_Semantic
{
    public const NAME   = 'status';
    public const OPEN   = 'Open';
    public const CLOSED = 'Closed';

    /**
     * @var Tracker_FormElement_Field_List
     */
    protected $list_field;

    /**
     * @var array
     */
    protected $open_values;

    /**
     * Constructor
     *
     * @param Tracker                        $tracker     The tracker
     * @param Tracker_FormElement_Field_List $list_field  The field
     * @param array                          $open_values The values with the meaning "Open"
     */
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field_List $list_field = null, $open_values = array())
    {
        parent::__construct($tracker);
        $this->list_field  = $list_field;
        $this->open_values = $open_values;
    }

    private function getDao()
    {
        return new Tracker_Semantic_StatusDao();
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_description');
    }

    /**
     * The Id of the (SB) field used for status semantic, or 0 if no field
     *
     * @return int The Id of the (SB) field used for status semantic, or 0 if no field
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
     * The (list) field used for status semantic
     *
     * @return Tracker_FormElement_Field_List The (list) field used for status semantic, or null if no field
     */
    public function getField()
    {
        return $this->list_field;
    }

    /**
     * The Ids of open values for this status semantic
     *
     * @return array of int The Id of the open values for this status semantic
     */
    public function getOpenValues()
    {
        return $this->open_values;
    }

    public function isOpen(Tracker_Artifact $artifact)
    {
        if (! $this->getField()) {
            return true;
        }

        $status = $artifact->getStatus();
        if (! $status) {
            return false;
        }

        return in_array($status, $this->getOpenLabels());
    }

    public function isOpenAtGivenChangeset(Tracker_Artifact_Changeset $changeset)
    {
        if (! $this->getField()) {
            return true;
        }

        $status = $changeset->getArtifact()->getStatusForChangeset($changeset);
        return in_array($status, $this->getOpenLabels());
    }

    /**
     * Get status label independent of language (hence english)
     *
     * @return string
     */
    public function getNormalizedStatusLabel(Tracker_Artifact $artifact)
    {
        if ($this->isOpen($artifact)) {
            return self::OPEN;
        }

        return self::CLOSED;
    }

    /**
     * Get status label according to current user language preference
     *
     * @return string
     */
    public function getLocalizedStatusLabel(Tracker_Artifact $artifact)
    {
        if ($this->isOpen($artifact)) {
            return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_Open');
        }

        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_Closed');
    }

    /**
     * @deprecated in favor of getLocalizedStatusLabel
     * @return string
     */
    public function getStatus(Tracker_Artifact $artifact)
    {
        return $this->getLocalizedStatusLabel($artifact);
    }

    /**
     *
     * @return array
     */
    private function getOpenLabels()
    {
        $labels = array();

        if (! $this->list_field instanceof Tracker_FormElement_Field_List) {
            return $labels;
        }
        $field_values = $this->list_field->getAllValues();

        foreach ($this->open_values as $value) {
            if (isset($field_values[$value])) {
                $labels[] = $field_values[$value]->getLabel();
            }
        }

        return $labels;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return void
     */
    public function display()
    {
        if ($this->list_field) {
            $purifier = Codendi_HTMLPurifier::instance();
            echo $GLOBALS['Language']->getText(
                'plugin_tracker_admin_semantic',
                'status_long_desc',
                array($purifier->purify($this->list_field->getLabel()))
            );
            if ($this->open_values) {
                echo '<ul>';
                $field_values = $this->list_field->getAllValues();
                foreach ($this->open_values as $v) {
                    if (isset($field_values[$v])) {
                        echo '<li><strong>' . $purifier->purify($field_values[$v]->getLabel()) . '</strong></li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<blockquote><em>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_no_value') . '</em></blockquote>';
            }
        } else {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_no_field');
        }
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';

        if ($list_fields = Tracker_FormElementFactory::instance()->getUsedListFields($this->tracker)) {
            $html .= '<form method="POST" action="' . $this->getUrl() . '">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $html .= '<input type="hidden" name="field_id" value="' . (int) $this->getFieldId() . '">';

            // field selectbox
            $field = null;
            $select = '<select name="field_id">';

            $selected = '';
            if (! $this->list_field) {
                $selected = 'selected="selected"';
            }
            $select .= '<option value="-1" ' . $selected . '>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_a_field') . '</option>';

            foreach ($list_fields as $list_field) {
                $selected = '';
                if ($list_field->getId() == $this->getFieldId()) {
                    $field = $list_field;
                    $selected = ' selected="selected" ';
                }
                $select .= '<option value="' . $list_field->getId() . '" ' . $selected . '>' . $hp->purify($list_field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
            }
            $select .= '</select>';

            // open values selectbox
            $params = '';
            if ($field) {
                $params = 'name="open_values[' . $this->getFieldId() . '][]" multiple="multiple" size="7" style="vertical-align:top;"';
            }
            $values = '<select ' . $params . '>';
            if ($field) {
                $disabled_values = $this->getDisabledValues();

                foreach ($field->getAllVisibleValues() as $v) {
                    $selected = '';
                    if (in_array($v->getId(), $this->open_values)) {
                        $selected = ' selected="selected" ';
                    }

                    $disabled = '';
                    if (in_array($v->getId(), $disabled_values)) {
                        $disabled = ' disabled="disabled" ';
                    }

                    $values .= '<option value="' . $v->getId() . '" ' . $selected . $disabled . '>' . $hp->purify($v->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
                }
            }
            $values .= '</select>';

            // submit button
            $submit = '<input type="submit" name="update" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';

            if (!$this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_no_field');
                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_one_advice') . $select . ' ' . $submit . '</p>';
            } else {
                $event = new SemanticStatusFieldCanBeUpdated($this->tracker);

                EventManager::instance()->processEvent($event);

                if (! ($event->fieldCanBeUpdated())) {
                    $GLOBALS['Response']->addFeedback(Feedback::INFO, $event->getReason());
                    $select = $this->getField()->getLabel();
                }

                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_long_desc', array($select)) . $values . ' ' . $submit;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_impossible');
        }
        $html .= '<p><a href="' . TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'go_back_overview') . '</a></p>';

        $sm->displaySemanticHeader($this, $tracker_manager);
        echo $html;
        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    private function getDisabledValues()
    {
        $event = new SemanticStatusGetDisabledValues($this->getField());

        EventManager::instance()->processEvent($event);

        return $event->getDisabledValues();
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            if ($request->get('field_id') == '-1') {
                $this->processDelete();
            } else {
                $this->processUpdate($request);
            }
        }
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    /**
     * @return array
     */
    private function getFilteredOpenValues(Codendi_Request $request)
    {
        $filtered_values = array();
        $open_values     = $request->get('open_values');

        if (! $open_values ||
            ! is_array($open_values) ||
            ! isset($open_values[$this->getFieldId()]) ||
            ! is_array($open_values[$this->getFieldId()])
        ) {
            return $filtered_values;
        }

        $selected_open_values = $open_values[$this->getFieldId()];
        $filtered_values      = array_diff($selected_open_values, $this->getDisabledValues());

        if ($filtered_values !== $selected_open_values) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-tracker', 'Some selected values was not saved because they are used in another semantic.')
            );
        }

        return $filtered_values;
    }

    /**
     * Delete this semantic
     */
    private function processDelete()
    {
        if (! $this->getField()) {
            return;
        }

        if ($this->doesTrackerNotificationUseStatusSemantic()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The semantic status cannot de deleted because tracker notifications is set to "Status change notifications".')
            );
            return;
        }

        $event = new SemanticStatusCanBeDeleted($this->tracker);
        EventManager::instance()->processEvent($event);

        if (! $event->semanticCanBeDeleted()) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $event->getReason());
            return;
        }

        $this->list_field  = null;
        $this->open_values = array();
        $dao = new Tracker_Semantic_StatusDao();
        $dao->delete($this->tracker->getId());
    }

    private function doesTrackerNotificationUseStatusSemantic()
    {
        return $this->tracker->getNotificationsLevel() === \Tracker::NOTIFICATIONS_LEVEL_STATUS_CHANGE;
    }

    /**
     * Save this semantic
     *
     * @return bool
     */
    public function save()
    {
        $dao = new Tracker_Semantic_StatusDao();
        $open_values = array();
        foreach ($this->open_values as $v) {
            if (is_scalar($v)) {
                $open_values[] = $v;
            } else {
                $open_values[] = $v->getId();
            }
        }
        $this->open_values = $open_values;
        return $dao->save($this->tracker->getId(), $this->getFieldId(), $this->open_values);
    }

    /**
     * @param string $new_value
     */
    public function addOpenValue($new_value)
    {
        $dao = $this->getDao();

        $dao->startTransaction();
        $new_id = $this->list_field->addBindValue($new_value);
        $this->open_values[] = $new_id;
        $this->save();
        $dao->commit();

        return $new_id;
    }

    public function removeOpenValue($value)
    {
        $this->open_values = array_diff($this->open_values, array($value));
        return $this->save();
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Status
     *
     * @param Tracker $tracker the tracker
     *
     * @return Tracker_Semantic_Status
     */
    public static function load(Tracker $tracker)
    {
        if (! isset(self::$_instances[$tracker->getId()])) {
            return self::forceLoad($tracker);
        }

        return self::$_instances[$tracker->getId()];
    }

    public static function forceLoad(Tracker $tracker)
    {
        $field_id    = null;
        $open_values = array();
        $dao         = new Tracker_Semantic_StatusDao();

        foreach ($dao->searchByTrackerId($tracker->getId()) as $row) {
            $field_id      = $row['field_id'];
            $open_values[] = (int) $row['open_value_id'];
        }

        if (!$open_values) {
            $open_values[] = 100;
        }

        $fef   = Tracker_FormElementFactory::instance();
        $field = $fef->getFieldById($field_id);

        self::$_instances[$tracker->getId()] = new Tracker_Semantic_Status($tracker, $field, $open_values);

        return self::$_instances[$tracker->getId()];
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->getFieldId() && in_array($this->getFieldId(), $xmlMapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new \XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
            $node_open_values = $child->addChild('open_values');
            foreach ($this->open_values as $value) {
                if ($ref = array_search($value, $xmlMapping['values'])) {
                    $node_open_values->addChild('open_value')->addAttribute('REF', $ref);
                }
            }
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

    public function exportToREST(PFUser $user)
    {
        $field = $this->getFieldUserCanRead($user);
        if ($field) {
            $semantic_status_representation = new SemanticStatusRepresentation();
            $semantic_status_representation->build($field->getId(), $this->getOpenValues());
            return $semantic_status_representation;
        }
        return false;
    }

    public function isFieldBoundToStaticValues()
    {
        $bindType = $this->list_field->getBind()->getType();

        return ($bindType == Tracker_FormElement_Field_List_Bind_Static::TYPE);
    }

    public function isBasedOnASharedField()
    {
        return $this->list_field->isTargetSharedField();
    }

    public function isOpenValue($label)
    {
        return in_array($label, $this->getOpenLabels());
    }

    /**
     * Allows to inject a fake Semantic for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Status $status, Tracker $tracker)
    {
        self::$_instances[$tracker->getId()] = $status;
    }

    /**
     * Allows to clear Semantics for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$_instances = null;
    }

    private function processUpdate(Codendi_Request $request): void
    {
        $field = Tracker_FormElementFactory::instance()->getUsedListFieldById(
            $this->tracker,
            $request->get('field_id')
        );
        if (! $field) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'bad_field_status')
            );
            return;
        }

        if ($this->getFieldId() == $request->get('field_id') && ! $request->get('open_values')) {
            return;
        }

        if ($this->getFieldId() !== $field->getId()) {
            $event = new SemanticStatusFieldCanBeUpdated($this->tracker);

            EventManager::instance()->processEvent($event);

            if (! ($event->fieldCanBeUpdated())) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $event->getReason());
                return;
            }
        }

        $this->list_field = $field;
        $open_values      = $this->getFilteredOpenValues($request);
        if (count($open_values) <= 0) {
            return;
        }

        $this->open_values = $open_values;
        if ($this->save()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_now', [$field->getLabel()])
            );
            $GLOBALS['Response']->redirect($this->getUrl());
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'unable_save_status')
            );
        }
    }
}
