<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Events\IsFieldUsedInASemanticEvent;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\REST\FieldListStaticValueRepresentation;

class Tracker_FormElement_Field_List_Bind_Static extends Tracker_FormElement_Field_List_Bind
{
    public const TYPE = 'static';

    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    protected $values;

    protected $is_rank_alpha;

    /**
     * @var Tracker_FormElement_Field_List_Bind_Static_ValueDao
     */
    private $value_dao = null;

    public function __construct($field, $is_rank_alpha, $values, $default_values, $decorators)
    {
        parent::__construct($field, $default_values, $decorators);

        $this->is_rank_alpha = $is_rank_alpha;
        $this->values        = $values;
    }

    /**
     * @return string
     */
    protected function format($value)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($value->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
    }

    /**
     * Get a bindvalue by its row
     *
     * @param array $row The row identifying the bindvalue
     *
     * @return Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function getValueFromRow($row)
    {
        return new Tracker_FormElement_Field_List_Bind_StaticValue(
            $row['id'],
            $row['label'],
            $row['description'],
            $row['rank'],
            $row['is_hidden']
        );
    }

    /**
     * Get the sql fragment used to retrieve value for a changeset to display the bindvalue in table rows for example.
     * Used by OpenList.
     *
     * @return array {
     *                  'select'     => "user.user_name, user.realname, CONCAT(user.realname,' (',user.user_name,')') AS full_name",
     *                  'select_nb'  => 3,
     *                  'from'       => 'user',
     *                  'join_on_id' => 'user.user_id',
     *              }
     */
    public function getBindtableSqlFragment()
    {
        return array(
            'select'     => "tracker_field_list_bind_static_value.id,
                             tracker_field_list_bind_static_value.label,
                             tracker_field_list_bind_static_value.description,
                             tracker_field_list_bind_static_value.rank,
                             tracker_field_list_bind_static_value.is_hidden",
            'select_nb'  => 5,
            'from'       => 'tracker_field_list_bind_static_value',
            'join_on_id' => 'tracker_field_list_bind_static_value.id',
        );
    }

    protected function getRESTBindingList()
    {
        // returns empty array as static are already listed in 'values'
        return array();
    }

    /**
     * @return string
     */
    public function formatCriteriaValue($value_id)
    {
         return $this->format($this->values[$value_id]);
    }

    /**
     * @return string
     */
    public function formatMailCriteriaValue($value_id)
    {
        return $this->format($this->getValue($value_id));
    }

    /**
     * @return string
     */
    public function formatChangesetValue($value)
    {
        // Should receive only valid value object but keep it as is for compatibility reasons
        if (is_array($value)) {
            if (isset($this->values[$value['id']])) {
                $value = $this->values[$value['id']];
            } elseif ($value['id'] == Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
                $value = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            }
        }
        if ($value) {
            assert($value instanceof Tracker_FormElement_Field_List_Value);
            return $this->formatChangesetValueObject($value);
        }
    }

    private function formatChangesetValueObject(Tracker_FormElement_Field_List_Value $value)
    {
        if (isset($this->decorators[$value->getId()])) {
            return $this->decorators[$value->getId()]->decorate($this->format($value));
        }
        return $this->format($value);
    }

    /**
     * @return string
     */
    public function formatChangesetValueForCSV($value)
    {
        if ($value['id'] == 100 || ! array_key_exists($value['id'], $this->values)) {
            return '';
        } else {
            return $this->values[$value['id']]->getLabel();
        }
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    public function getAllValues()
    {
        return $this->values;
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string $submitted_value the field value
     * @param bool   $is_multiple     if the value is multiple or not
     *
     * @return mixed the field data corresponding to the value for artifact submision
     */
    public function getFieldData($submitted_value, $is_multiple)
    {
        $values = $this->getAllValues();
        if ($is_multiple) {
            $return = array();
            $submitted_values = explode(",", $submitted_value);
            foreach ($values as $id => $value) {
                if (in_array($value->getLabel(), $submitted_values)) {
                    $return[] = $id;
                }
            }
            if (count($submitted_values) == count($return)) {
                return $return;
            } else {
                // if one value was not found, return null
                return null;
            }
        } else {
            foreach ($values as $id => $value) {
                if ($value->getLabel() == $submitted_value) {
                    return $id;
                }
            }
            // if not found, return null
            return null;
        }
    }

    /**
     * @param int $value_id
     * @return Tracker_FormElement_Field_List_Bind_StaticValue
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public function getValue($value_id)
    {
        if (! isset($this->values[$value_id])) {
            throw new Tracker_FormElement_InvalidFieldValueException();
        }

        return $this->values[$value_id];
    }

    public function getIsRankAlpha()
    {
        return $this->is_rank_alpha;
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return $this->values[$value]->getLabel();
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    /*public function fetchRawValueFromChangeset($changeset) {
        $value = '';
        if ($v = $changeset->getValue($this->field)) {
            if (isset($v['value_id'])) {
                $v = array($v);
            }
            foreach($v as $val) {
                $value .= $this->values[$val['value_id']]['label'];
            }
        }
        return $value;
    }*/
    public function fetchRawValueFromChangeset($changeset)
    {
        $value = '';
        $values_array = array();
        if ($v = $changeset->getValue($this->field)) {
            $values = $v->getListValues();
            foreach ($values as $val) {
                $values_array[] = $val->getLabel();
            }
        }
        return implode(",", $values_array);
    }

    /**
     * @return array
     */
    public function getChangesetValues($changeset_id)
    {
        $values = array();
        foreach ($this->getValueDao()->searchChangesetValues($changeset_id, $this->field->id, $this->is_rank_alpha) as $row) {
            $values[] = $row;
        }
        return $values;
    }

    protected function getIdsToSearch($criteria_value)
    {
        return array_values($criteria_value);
    }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect()
    {
        $R1 = 'R1_' . $this->field->id;
        $R2 = 'R2_' . $this->field->id;
        return "$R2.id AS `" . $this->field->name . "`";
    }

    /**
     * Get the "select" statement to retrieve field values with the RGB values of their decorator
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelectWithDecorator()
    {
        return $this->getQuerySelect() . ", color.red, color.green, color.blue, color.tlp_color_name";
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     *
     * @param string $changesetvalue_table The changeset value table to use
     *
     * @return string
     */
    public function getQueryFrom($changesetvalue_table = 'tracker_changeset_value_list')
    {
        $R1 = 'R1_' . $this->field->id;
        $R2 = 'R2_' . $this->field->id;
        $R3 = 'R3_' . $this->field->id;

        return "LEFT JOIN (
                    tracker_changeset_value AS $R1
                    INNER JOIN $changesetvalue_table AS $R3 ON ($R3.changeset_value_id = $R1.id)
                    LEFT JOIN tracker_field_list_bind_static_value AS $R2 ON ($R2.id = $R3.bindvalue_id AND $R2.field_id = " . $this->field->id . " )
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->field->id . " )
               ";
    }

/**
     * Get the "from" statement to retrieve field values with the RGB values of their decorator
     * Has no sense for fields other than lists
     * @return string
     */
    public function getQueryFromWithDecorator($changesetvalue_table = 'tracker_changeset_value_list')
    {
        $R2 = 'R2_' . $this->field->id;

        return $this->getQueryFrom($changesetvalue_table) . " LEFT OUTER JOIN tracker_field_list_bind_decorator AS color ON ($R2.field_id = color.field_id AND color.value_id = $R2.id)";
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby()
    {
        $R1 = 'R1_' . $this->field->id;
        $R2 = 'R2_' . $this->field->id;
        return $this->is_rank_alpha ? "$R2.label" : "$R2.rank";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby()
    {
        $R1 = 'R1_' . $this->field->id;
        $R2 = 'R2_' . $this->field->id;
        return "$R2.id";
    }

    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions)
    {
        $R1  = 'R1_' . $this->field->id;
        $R2  = 'R2_' . $this->field->id;
        $same     = array();
        $separate = array();
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = array(
                        'function' => $f,
                        'select'   => "$R2.label AS label, count(*) AS value",
                        'group_by' => "$R2.label",
                    );
                } else {
                    $select = "$f($R2.label) AS `" . $this->field->name . "_$f`";
                    if ($this->field->isMultiple()) {
                        $separate[] = array(
                            'function' => $f,
                            'select'   => $select,
                            'group_by' => null,
                        );
                    } else {
                        $same[] = $select;
                    }
                }
            }
        }
        return array(
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        );
    }

    public function getDao()
    {
        return new Tracker_FormElement_Field_List_Bind_StaticDao();
    }

    private function getOpenValueDao()
    {
        return new Tracker_FormElement_Field_List_OpenValueDao();
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_Static_ValueDao
     */
    public function getValueDao()
    {
        if ($this->value_dao === null) {
            $this->value_dao = new Tracker_FormElement_Field_List_Bind_Static_ValueDao();
        }
        return $this->value_dao;
    }

    /**
     * Allow the user to define the bind
     *
     * @param Field $field
     *
     * @return string html
     */
    public static function fetchAdminCreateForm($field)
    {
        $html = '';
        $h = new HTML_Element_Input_Checkbox($GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'alphabetically_sort'), 'bind[is_rank_alpha]', 0);
        $h->setId('is_rank_alpha');
        $html .= '<p>' . $h->render() . '</p>';
        $html .= '<p>';
        $html .= '<textarea name="formElement_data[bind][add]" rows="5" cols="30"></textarea><br />';
        $html .= '<span style="color:#999; font-size:0.8em;">' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'add_row') . '</span>';
        $html .= '</p>';
        return $html;
    }

    /**
     * Fetch the form to edit the formElement
     *
     * @return string html
     */
    public function fetchAdminEditForm()
    {
        if ($this->field->isTargetSharedField()) {
            return $this->fetchAdminEditFormNotModifiable();
        } else {
            return $this->fetchAdminEditFormModifiable();
        }
    }

    private function fetchAdminEditFormModifiable()
    {
        $html = '';
        $html .= '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'static_values') . '</h3>';

        $h = new HTML_Element_Input_Checkbox($GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'alphabetically_sort'), 'bind[is_rank_alpha]', $this->is_rank_alpha);
        $h->setId('is_rank_alpha');
        $h->addParam('class', 'is_rank_alpha');
        $html .= '<p>' . $h->render() . '</p>';

        $html .= '<table><tr><td>';
        $html .= '<input type="hidden" name="bind[order]" class="bind_order_values" value="" />';
        $html .= '<ul class="tracker-admin-bindvalue_list">';
        foreach ($this->getAllValues() as $v) {
            $html .= $this->fetchAdminEditRowModifiable($v);
        }
        $html .= '</ul>';
        $html .= '</td></tr></table>';

        //Add new values
        $html .= '<p id="tracker-admin-bind-static-addnew">';
        $html .= '<strong>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'add_new_values') . '</strong><br />';
        $html .= '<textarea name="bind[add]" rows="5" cols="30"></textarea><br />';
        $html .= '<span style="color:#999; font-size:0.8em;">' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'add_row') . '</span><br />';
        $html .= '</p>';

        //Select default values
        $html .= $this->getField()->getSelectDefaultValues($this->default_values);

        return $html;
    }

    private function fetchAdminEditRowModifiable(Tracker_FormElement_Field_List_Value $v)
    {
        $html = '';

        $hp = Codendi_HTMLPurifier::instance();

        $is_hidden = $v->isHidden();

        $html .= '<li id="staticvalue_' . $v->getId() . '" class="' . ($is_hidden ? 'tracker_admin_static_value_hidden' : '') . '">';

        $html .= '<span class="tracker-admin-bindvalue_grip">';
        $html .= $GLOBALS['HTML']->getImage('ic/grip.png');
        $html .= '</span>';

        $html .= '<span class="tracker-admin-bindvalue_decorator">';

        $event = new IsFieldUsedInASemanticEvent($this->field);

        EventManager::instance()->processEvent($event);

        $is_used_in_semantics = $event->isUsed();

        if (isset($this->decorators[$v->getId()])) {
            $html .= $this->decorators[$v->getId()]->decorateEdit($is_used_in_semantics);
        } else {
            $html .= Tracker_FormElement_Field_List_BindDecorator::noDecoratorEdit($this->field->id, $v->getId(), $is_used_in_semantics);
        }
        $html .= '</span>';

        //{{{ Actions
        $html .= '<span class="tracker-admin-bindvalue_actions">';

        $img_params = array();
        $icon_suffix = '';
        if ($this->canValueBeHidden($v)) {
            $checked = '';
            if ($is_hidden) {
                $icon_suffix = '-half';
            } else {
                $checked = 'checked="checked"';
            }
            $html .= '<input type="hidden" name="bind[edit][' . $v->getId() . '][is_hidden]" value="1" />';
            $html .= '<input type="checkbox" name="bind[edit][' . $v->getId() . '][is_hidden]" value="0" ' . $checked . ' class="tracker_admin_static_value_hidden_chk" />';
            $img_params['alt'] = 'show/hide value';
            $img_params['title'] = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'hide_value');
        } else {
            $icon_suffix = '--exclamation-hidden';
            $img_params['alt'] = 'cannot hide';
            $img_params['title'] = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'hide_value_impossible');
        }
        $html .= $GLOBALS['HTML']->getImage('ic/eye' . $icon_suffix . '.png', $img_params);

        $html .= ' ';

        if ($this->canValueBeDeleted($v)) {
            $html .= '<a title="Delete the value"
                href="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                        'tracker' => $this->field->getTracker()->id,
                        'func' => 'admin-formElement-update',
                        'formElement' => $this->field->getId(),
                        'bind-update' => 1,
                        'bind[delete]' => $v->getId(),
                    )) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png') . '</a>';
        } else {
            $html .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', array('title' => "You can't delete"));
        }

        $html .= '</span>';
        //}}}

        $html .= '<span class="tracker-admin-bindvalue_label">';
        $html .= '<input type="text" name="bind[edit][' . $v->getId() . '][label]" required value="' . $hp->purify($v->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '" />';
        $placeholder = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'description_placeholder');
        $html .= '<textarea name="bind[edit][' . $v->getId() . '][description]" class="tracker-admin-bindvalue_description_field" placeholder="' . $placeholder . '" cols="50" rows="3">';
        $html .= $hp->purify($v->getDescription(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</textarea>';
        $html .= '</span>';

        $html .= '</li>';
        return $html;
    }

    private function fetchAdminEditFormNotModifiable()
    {
        $html = '';

        $html .= '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'static_values') . '</h3>';
        $html .= '<table cellpadding="2" cellspacing="0" border="0">';
        foreach ($this->getAllValues() as $v) {
            $html .= $this->fetchAdminEditRowNotModifiable($v);
        }
        $html .= '</table>';

        // @todo: Show default value ?

        return $html;
    }

    private function fetchAdminEditRowNotModifiable(Tracker_FormElement_Field_List_Value $v)
    {
        $html = '';
        $html .= '<tr valign="top" class="' . ($v->isHidden() ? 'tracker_admin_static_value_hidden' : '') . '">';
        $html .= '<td>' . $this->formatChangesetValue(array('id' => $v->getId())) . '</td>';
        $html .= '</tr>';
        return $html;
    }

    /**
     * Say if a value can be hidden
     *
     * @param Tracker_FormElement_Field_List_Bind_StaticValue $value the value
     *
     * @return bool true if the value can be hidden
     */
    public function canValueBeHidden(Tracker_FormElement_Field_List_Bind_StaticValue $value)
    {
        return $this->getValueDao()->canValueBeHidden($this->field, $value->getId());
    }

    /**
     * Say if a value can be hidden without checking the semantic status
     *
     * @param Tracker_FormElement_Field_List_Bind_StaticValue $value the value
     *
     * @return bool true if the value can be hidden
     */
    public function canValueBeHiddenWithoutCheckingSemanticStatus(Tracker_FormElement_Field_List_Bind_StaticValue $value)
    {
        return $this->getValueDao()->canValueBeHiddenWithoutCheckingSemanticStatus($this->field, $value->getId());
    }

    /**
     * Say if a value can be deleted
     *
     * @param Tracker_FormElement_Field_List_Bind_StaticValue $value the value
     *
     * @return bool true if the value can be deleted
     */
    public function canValueBeDeleted(Tracker_FormElement_Field_List_Bind_StaticValue $value)
    {
        return $this->getValueDao()->canValueBeDeleted($this->field, $value->getId());
    }

    /**
     * Process the request
     *
     * @param array $params the request parameters
     * @param bool  $no_redirect true if we do not have to redirect the user
     *
     * @return void
     */
    public function process($params, $no_redirect = false)
    {
        $hp        = Codendi_HTMLPurifier::instance();
        $value_dao = $this->getValueDao();
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'is_rank_alpha':
                    $is_rank_alpha = $value ? 1 : 0;
                    if ($this->is_rank_alpha != $is_rank_alpha) {
                        $this->getDao()->save($this->field->id, $is_rank_alpha);
                        if (! empty($this->field->getSharedTargets())) {
                            $this->getDao()->updateChildrenAlphaRank($this->field->id, $is_rank_alpha);
                        }
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'alpha_ranking_updated'));
                    }
                    break;
                case 'delete':
                    if (($row = $value_dao->searchById((int) $value)->getRow()) && $value_dao->delete((int) $value)) {
                        $params['decorator'] = array((int) $value => null);
                        $GLOBALS['Response']->addFeedback('info', 'Value ' .  $hp->purify($row['label'], CODENDI_PURIFIER_CONVERT_HTML)  . ' deleted');
                    }
                    break;
                case 'order':
                    if (is_string($value) && $value != '') {
                        $ids_in_right_order = explode(',', $value);
                        $value_dao->reorder($ids_in_right_order);
                    }
                    break;
                case 'edit':
                    foreach ($value as $value_id => $info) {
                        if (isset($this->values[$value_id])) {
                            $bind_static_value = $this->values[$value_id];

                            $new_label       = null;
                            $new_description = null;
                            $new_is_hidden   = null;
                            if (isset($info['label']) && trim($info['label']) != $bind_static_value->getLabel()) {
                                if (empty(trim($info['label']))) {
                                    $GLOBALS['Response']->addFeedback(
                                        Feedback::WARN,
                                        dgettext('tuleap-tracker', 'Field value cannot be empty')
                                    );
                                } else {
                                    $new_label = trim($info['label']);
                                }
                            }
                            if (isset($info['description']) && trim($info['description']) != $bind_static_value->getDescription()) {
                                $new_description = trim($info['description']);
                            }
                            if (isset($info['is_hidden']) && trim($info['is_hidden']) != $bind_static_value->isHidden()) {
                                $new_is_hidden = trim($info['is_hidden']);
                            }
                            if ($new_label !== null || $new_description !== null || $new_is_hidden !== null) {
                                //something has changed. we can save it
                                $value_dao->save(
                                    $value_id,
                                    $this->field->getId(),
                                    isset($new_label)       ? $new_label       : $bind_static_value->getLabel(),
                                    isset($new_description) ? $new_description : $bind_static_value->getDescription(),
                                    $bind_static_value->getRank(),
                                    isset($new_is_hidden)   ? $new_is_hidden   : $bind_static_value->isHidden()
                                );
                                unset($new_label, $new_description);
                            }
                        }
                    }
                    break;
                case 'add':
                    $valueMapping = array();
                    foreach (explode("\n", $value) as $new_value) {
                        $id = $this->addValue($new_value);
                        if ($id) {
                            $this->values[$id] = $this->getValueFromRow($value_dao->searchById($id)->getRow());
                            $valueMapping[] = $id;
                        }
                    }
                    if (isset($params['decorators'])) {
                        $params['decorator'] = array();
                        foreach ($params['decorators'] as $key => $deco) {
                            $params['decorator'][$valueMapping[$key]] =
                                   ColorHelper::RGBtoHexa($deco->r, $deco->g, $deco->b);
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return parent::process($params, $no_redirect);
    }

    /**
     * @param string $new_value
     *
     * @return int | null
     */
    public function addValue($new_value)
    {
        $value_dao = $this->getValueDao();
        //remove the \r submitted by the user
        $new_value = trim(str_replace("\r", '', $new_value));
        if (! $new_value) {
            return;
        }
        $id = $value_dao->create($this->field->getId(), $new_value, '', 'end', 0);
        if (! $id) {
            return;
        }
        $this->propagateCreation($this->field, $id);

        return $id;
    }

    public function propagateCreation($field, $original_value_id)
    {
        $value_dao = $this->getValueDao();
        $value_dao->propagateCreation($field, $original_value_id);
    }

    /**
     * Transforms Bind into a SimpleXMLElement
     */
    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    ) {
        $cdata_section_factory = new XML_SimpleXMLCDATAFactory();
        $root->addAttribute('is_rank_alpha', $this->is_rank_alpha);
        if ($this->getAllValues()) {
            $child = $root->addChild('items');
            foreach ($this->getAllValues() as $val) {
                $grandchild = $child->addChild('item');
                $ID = $val->getXMLId();
                $grandchild->addAttribute('ID', $ID);
                $xmlMapping['values'][$ID] = $val->getId();
                $grandchild->addAttribute('label', $val->getLabel());
                $grandchild->addAttribute('is_hidden', $val->isHidden());

                if ($val->getDescription() != '') {
                    $cdata_section_factory->insert($grandchild, 'description', $val->getDescription());
                }
            }
            if ($this->decorators) {
                $child = $root->addChild('decorators');
                foreach ($this->decorators as $deco) {
                    $deco->exportToXML($child, array_search($deco->value_id, $xmlMapping['values']));
                }
            }
            if ($this->default_values) {
                $default_child = $root->addChild('default_values');
                foreach ($this->default_values as $id => $nop) {
                    if ($ref = array_search($id, $xmlMapping['values'])) {
                        $default_child->addChild('value')->addAttribute('REF', $ref);
                    }
                }
            }
        }
    }

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is null then return all values.
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @Return array the BindValue(s)
     */
    public function getBindValues($bindvalue_ids = null)
    {
        if ($bindvalue_ids === null) {
            return $this->values;
        } else {
            return $this->extractBindValuesByIds($bindvalue_ids);
        }
    }

    /**
     * Give an extract of the bindvalues defined. The extract is based on $bindvalue_ids.
     * If the $bindvalue_ids is empty then return empty array
     *
     * @param array $bindvalue_ids The ids of BindValue to retrieve
     *
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getBindValuesForIds(array $bindvalue_ids)
    {
        return $this->extractBindValuesByIds($bindvalue_ids);
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    private function extractBindValuesByIds(array $bindvalue_ids)
    {
        $list_of_bindvalues = array();
        foreach ($bindvalue_ids as $i) {
            if (isset($this->values[$i])) {
                $list_of_bindvalues[$i] = $this->values[$i];
            }
        }

        return $list_of_bindvalues;
    }

    /**
     * Saves a bind in the database
     *
     * @return void
     */
    public function saveObject()
    {
        $dao = new Tracker_FormElement_Field_List_Bind_StaticDao();
        if ($dao->save($this->field->getId(), $this->is_rank_alpha)) {
            $value_dao = $this->getValueDao();
            foreach ($this->getAllValues() as $v) {
                if ($id = $value_dao->create($this->field->getId(), $v->getLabel(), $v->getDescription(), 'end', $v->isHidden())) {
                    $v->setId($id);
                }
            }
        }
        parent::saveObject();
    }

    public function isValid($value)
    {
        return true;
    }

    /**
     * @see Tracker_FormElement_Field_Shareable
     */
    public function fixOriginalValueIds(array $value_mapping)
    {
        $value_dao = $this->getValueDao();
        $field_id  = $this->getField()->getId();

        foreach ($value_mapping as $old_original_value_id => $new_original_value_id) {
            $value_dao->updateOriginalValueId($field_id, $old_original_value_id, $new_original_value_id);
        }
    }

    public function getNumericValues(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        $bind_values = $this->getBindValues($changeset_value->getValue());

        return $this->extractNumericValues($bind_values);
    }

    private function extractNumericValues(Array $bind_values)
    {
        $numeric_values = array();

        foreach ($bind_values as $bind_value) {
            $value = $bind_value->getLabel();

            if (is_numeric($value)) {
                $numeric_values[] = $value;
            }
        }

        return $numeric_values;
    }

    public function getType()
    {
        return self::TYPE;
    }

    public function getFieldDataFromRESTObject(array $rest_data, Tracker_FormElement_Field_List $field)
    {
        if (isset($rest_data['id']) && is_numeric($rest_data['id'])) {
            $id = (int) $rest_data['id'];
            try {
                $this->getValue($id);
            } catch (Tracker_FormElement_InvalidFieldValueException $e) {
                if (! $this->getOpenValueDao()->searchById($field->getId(), $id)->getRow()) {
                    throw new Tracker_FormElement_InvalidFieldValueException('Bind Value with ID ' . $id . ' does not exist for field ID ' . $field->getId());
                }

                return Tracker_FormElement_Field_OpenList::OPEN_PREFIX . $id;
            }
            return Tracker_FormElement_Field_OpenList::BIND_PREFIX . $id;
        }
        if (isset($rest_data['label'])) {
            $identifier = (string) $rest_data['label'];
        } else {
            throw new Tracker_FormElement_InvalidFieldValueException('OpenList static fields values should be passed as an object with at least one of the properties "id" or "label"');
        }

        $row = $this->getOpenValueDao()->searchByExactLabel($field->getId(), $identifier)->getRow();
        if ($row) {
            return Tracker_FormElement_Field_OpenList::OPEN_PREFIX . $row['id'];
        }

        return Tracker_FormElement_Field_OpenList::NEW_VALUE_PREFIX . $identifier;
    }

    public function getRESTAvailableValues()
    {
        $rest_values = array();
        foreach ($this->getAllValues() as $value) {
            $rest_values[] = $this->getRESTBindValue($value);
        }

        $new_values = $this->getOpenValueDao()->searchByFieldId($this->getField()->getId());
        foreach ($new_values as $row_value) {
            $bind_value    = new Tracker_FormElement_Field_List_Bind_StaticValue(
                $row_value['id'],
                $row_value['label'],
                '',
                '',
                ''
            );
            $rest_values[] = $this->getRESTBindValue($bind_value);
        }

        return $rest_values;
    }

    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        $representation = new FieldListStaticValueRepresentation();
        $representation->build($value);

        return $representation;
    }

    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
        return array(
            'label' => $value->getLabel(),
            'id'    => $value->getId(),
        );
    }

    public function accept(BindVisitor $visitor, BindParameters $parameters)
    {
        return $visitor->visitListBindStatic($this, $parameters);
    }

    /**
     * @param int $bindvalue_id
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getBindValueById($bindvalue_id)
    {
        $dao = new Tracker_FormElement_Field_List_Bind_Static_ValueDao();
        $row = $dao->searchById($bindvalue_id)->getRow();

        return $this->getValueFromRow($row);
    }

    /**
     * @return array
     */
    protected function filterDefaultValues(array $bind_default)
    {
        return array_intersect($bind_default, array_keys($this->getAllVisibleValues()));
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getAllValuesWithActiveUsersOnly(): array
    {
        return [];
    }
}
