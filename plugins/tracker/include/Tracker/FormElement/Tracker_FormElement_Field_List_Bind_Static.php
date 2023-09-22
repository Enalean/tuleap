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

use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;
use Tuleap\Tracker\Events\IsFieldUsedInASemanticEvent;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticXmlExporter;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\OpenListValueDao;
use Tuleap\Tracker\FormElement\View\Admin\Field\ListFields\BindValuesAdder;
use Tuleap\Tracker\FormElement\FormElementListValueAdminViewPresenterBuilder;
use Tuleap\Tracker\REST\FieldListStaticValueRepresentation;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_List_Bind_Static extends Tracker_FormElement_Field_List_Bind
{
    public const TYPE = 'static';

    /**
     * @var Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    protected $values;

    protected $is_rank_alpha;

    /**
     * @var BindStaticValueDao
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
        return [
            'select'     => "tracker_field_list_bind_static_value.id,
                             tracker_field_list_bind_static_value.label,
                             tracker_field_list_bind_static_value.description,
                             tracker_field_list_bind_static_value.rank,
                             tracker_field_list_bind_static_value.is_hidden",
            'select_nb'  => 5,
            'from'       => 'tracker_field_list_bind_static_value',
            'join_on_id' => 'tracker_field_list_bind_static_value.id',
        ];
    }

    protected function getRESTBindingList()
    {
        // returns empty array as static are already listed in 'values'
        return [];
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

        return '';
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
            $return           = [];
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

    public function fetchRawValueFromChangeset($changeset)
    {
        $value        = '';
        $values_array = [];
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
        $values = [];
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
     * @see getQueryFrom
     */
    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->field->id;
        return "$R2.id AS " . $this->field->getQuerySelectName();
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

    public function getQueryFromWithDecorator($changesetvalue_table = 'tracker_changeset_value_list'): string
    {
        $current_field_id = $this->field->id;
        $R2               = 'R2_' . $current_field_id;
        $none_value       = (int) self::NONE_VALUE;

        $sql = "LEFT OUTER JOIN tracker_field_list_bind_decorator AS color ON (
                ($R2.field_id = color.field_id AND color.value_id = $R2.id)
                OR ($R2.field_id IS null AND color.value_id = $none_value AND color.field_id = $current_field_id)
            )";

        return $this->getQueryFrom($changesetvalue_table) . $sql;
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby(): string
    {
        if (! $this->getField()->isUsed()) {
            return '';
        }
        $R2 = 'R2_' . $this->field->id;
        return $this->is_rank_alpha ? "$R2.label" : "$R2.rank";
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        if (! $this->getField()->isUsed()) {
            return '';
        }
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
        $R1       = 'R1_' . $this->field->id;
        $R2       = 'R2_' . $this->field->id;
        $same     = [];
        $separate = [];
        foreach ($functions as $f) {
            if (in_array($f, $this->field->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = [
                        'function' => $f,
                        'select'   => "$R2.label AS label, count(*) AS value",
                        'group_by' => "$R2.label",
                    ];
                } else {
                    $select = "$f($R2.label) AS `" . $this->field->name . "_$f`";
                    if ($this->field->isMultiple()) {
                        $separate[] = [
                            'function' => $f,
                            'select'   => $select,
                            'group_by' => null,
                        ];
                    } else {
                        $same[] = $select;
                    }
                }
            }
        }
        return [
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        ];
    }

    public function getDao()
    {
        return new BindStaticDao();
    }

    private function getOpenValueDao()
    {
        return new OpenListValueDao();
    }

    private function getTemplateRenderer(): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
    }

    private function getFormElementListAdminViewBuilder(): FormElementListValueAdminViewPresenterBuilder
    {
        return new FormElementListValueAdminViewPresenterBuilder($this->getValueDao());
    }

    private function getAdminEditRowModifiable(
        Tracker_FormElement_Field_List_Value $value,
        ?ColorpickerMountPointPresenter $decorator,
        bool $is_custom_value,
    ): string {
        return $this->getTemplateRenderer()->renderToString(
            'admin-edit-row-modifiable',
            $this->getFormElementListAdminViewBuilder()->buildPresenter(
                $this->field,
                $value,
                $decorator,
                $is_custom_value
            )
        );
    }

    /**
     * @return BindStaticValueDao
     */
    public function getValueDao()
    {
        if ($this->value_dao === null) {
            $this->value_dao = new BindStaticValueDao();
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
        $h    = new HTML_Element_Input_Checkbox(dgettext('tuleap-tracker', 'alphabetically sort values'), 'bind[is_rank_alpha]', 0);
        $h->setId('is_rank_alpha');
        $html .= '<p>' . $h->render() . '</p>';
        $html .= '<p>';
        $html .= '<textarea name="formElement_data[bind][add]" rows="5" cols="30"></textarea><br />';
        $html .= '<span style="color:#999; font-size:0.8em;">' . dgettext('tuleap-tracker', 'Add one value per row') . '</span>';
        $html .= '</p>';
        return $html;
    }

    /**
     * Fetch the form to edit the formElement
     */
    public function fetchAdminEditForm(): string
    {
        if ($this->field->isTargetSharedField()) {
            return $this->fetchAdminEditFormNotModifiable();
        } else {
            return $this->fetchAdminEditFormModifiable();
        }
    }

    private function fetchAdminEditFormModifiable(): string
    {
        $html = '';

        $html .= $this->fetchAdminEditFormModifiableWithUsersValues();

        $html .= '<h3>' . dgettext('tuleap-tracker', 'Static values') . '</h3>';

        $h = new HTML_Element_Input_Checkbox(
            dgettext('tuleap-tracker', 'alphabetically sort values'),
            'bind[is_rank_alpha]',
            $this->is_rank_alpha
        );
        $h->setId('is_rank_alpha');
        $h->addParam('class', 'is_rank_alpha');
        $html .= '<p>' . $h->render() . '</p>';

        $html .= '<table><tr><td>';
        $html .= '<input type="hidden" name="bind[order]" class="bind_order_values" value="" />';
        $html .= '<ul class="tracker-admin-bindvalue_list tracker-admin-bindvalue_list_sortable">';

        $retriever       = new BindValuesAdder();
        $possible_values = $retriever->addNoneValue($this->getAllValues());

        foreach ($possible_values as $v) {
            $html .= $this->fetchAdminEditRowModifiable($v);
        }
        $html .= '</ul>';
        $html .= '</td></tr></table>';

        //Add new values
        $html .= '<p id="tracker-admin-bind-static-addnew">';
        $html .= '<strong>' . dgettext('tuleap-tracker', 'Add new values') . '</strong><br />';
        $html .= '<textarea name="bind[add]" rows="5" cols="30"></textarea><br />';
        $html .= '<span style="color:#999; font-size:0.8em;">' . dgettext(
            'tuleap-tracker',
            'Add one value per row'
        ) . '</span><br />';
        $html .= '</p>';

        //Select default values
        $html .= $this->getField()->getSelectDefaultValues($this->default_values);

        return $html;
    }

    private function fetchAdminEditFormModifiableWithUsersValues(): string
    {
        $html            = '';
        $user_row_values = $this->getOpenValueDao()->searchByFieldId($this->getField()->getId());

        if ($user_row_values->count() === 0) {
            return $html;
        }

        $html       .= '<h3>' . dgettext('tuleap-tracker', 'Values added by users') . '</h3>';
        $user_values = [];

        foreach ($user_row_values as $row_value) {
            $user_values[] = new Tracker_FormElement_Field_List_OpenValue(
                $row_value['id'],
                $row_value['label'],
                $row_value['is_hidden']
            );
        }

        $html .= '<table><tr><td>';
        $html .= '<input type="hidden" name="bind[order]" value="" />';
        $html .= '<ul class="tracker-admin-bindvalue_list">';

        foreach ($user_values as $value) {
            $html .= $this->getAdminEditRowModifiable($value, null, true);
        }

        $html .= '</ul>';
        $html .= '</td></tr></table>';

        return $html;
    }

    private function fetchAdminEditRowModifiable(Tracker_FormElement_Field_List_Value $value): string
    {
        assert($value instanceof Tracker_FormElement_Field_List_Bind_StaticValue);

        $event = new IsFieldUsedInASemanticEvent($this->field);

        EventManager::instance()->processEvent($event);

        $is_used_in_semantics = $event->isUsed();
        if (isset($this->decorators[$value->getId()])) {
            $decorator = $this->decorators[$value->getId()]->decorateEdit($is_used_in_semantics);
        } else {
            $decorator = Tracker_FormElement_Field_List_BindDecorator::noDecoratorEdit(
                $this->field->id,
                $value->getId(),
                $is_used_in_semantics
            );
        }

        return $this->getAdminEditRowModifiable($value, $decorator, false);
    }

    private function fetchAdminEditFormNotModifiable()
    {
        $html = '';

        $html .= '<h3>' . dgettext('tuleap-tracker', 'Static values') . '</h3>';
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
        $html  = '';
        $html .= '<tr valign="top" class="' . ($v->isHidden() ? 'tracker_admin_static_value_hidden' : '') . '">';
        $html .= '<td>' . $this->formatChangesetValue(['id' => $v->getId()]) . '</td>';
        $html .= '</tr>';
        return $html;
    }

    public function canValueBeHiddenWithoutCheckingSemanticStatus(Tracker_FormElement_Field_List_Value $value): bool
    {
        return $this->getValueDao()->canValueBeHiddenWithoutCheckingSemanticStatus($this->field, $value->getId());
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
                        $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-tracker', 'Alpha Ranking updated'));
                    }
                    break;
                case 'delete':
                    if (($row = $value_dao->searchById((int) $value)->getRow()) && $value_dao->delete($this->field, (int) $value)) {
                        $params['decorator'] = [(int) $value => null];
                        $GLOBALS['Response']->addFeedback(
                            Feedback::INFO,
                            sprintf(
                                dgettext('tuleap-tracker', 'Value %s deleted'),
                                $hp->purify($row['label'], CODENDI_PURIFIER_CONVERT_HTML),
                            )
                        );
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

                            if ($value_dao->canValueBeHidden($this->field, (int) $value_id)) {
                                $new_is_hidden = ! isset($info['is_hidden']);
                            }

                            if ($new_label !== null || $new_description !== null || $new_is_hidden !== null) {
                                $original_value = $this->value_dao->searchById($value_id)->getRow();
                                //something has changed. we can save it
                                $value_dao->save(
                                    $value_id,
                                    $this->field->getId(),
                                    isset($new_label) ? $new_label : $bind_static_value->getLabel(),
                                    isset($new_description) ? $new_description : $bind_static_value->getDescription(),
                                    $original_value['rank'],
                                    isset($new_is_hidden) ? $new_is_hidden : $bind_static_value->isHidden()
                                );
                                unset($new_label, $new_description);
                            }
                        }
                    }
                    break;
                case "edit_custom":
                    $values = $this->getOpenValueDao()->searchByFieldId($this->field->getId());
                    foreach ($values as $row) {
                        $new_is_hidden = ! isset($value[$row['id']]['is_hidden']);
                        $this->getOpenValueDao()->updateOpenValue(
                            (int) $row['id'],
                            $new_is_hidden,
                            $value[$row['id']]['label']
                        );
                    }
                    break;
                case 'add':
                    $valueMapping = [];
                    foreach (explode("\n", $value) as $new_value) {
                        $id = $this->addValue($new_value);
                        if ($id) {
                            $this->values[$id] = $this->getValueFromRow($value_dao->searchById($id)->getRow());
                            $valueMapping[]    = $id;
                        }
                    }
                    if (isset($params['decorators'])) {
                        $params['decorator'] = [];
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
        if ($new_value === '') {
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

    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter,
    ) {
        $root->addAttribute('is_rank_alpha', $this->is_rank_alpha ? "1" : "0");
        if (! $this->getAllValues()) {
            return;
        }

        $exporter = new BindStaticXmlExporter(new XML_SimpleXMLCDATAFactory());
        $exporter->exportToXml(
            $root,
            $this->getAllValues(),
            $this->decorators,
            $this->default_values,
            $xmlMapping
        );
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
        $list_of_bindvalues = [];
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
        $dao = new BindStaticDao();
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

    private function extractNumericValues(array $bind_values)
    {
        $numeric_values = [];

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
        $rest_values = [];
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
        $value_color = null;
        if (isset($this->decorators[$value->getId()])) {
            $value_color = $this->decorators[$value->getId()]->getCurrentColor();
        }
        $representation = new FieldListStaticValueRepresentation();
        $representation->build($value, Codendi_HTMLPurifier::instance()->purify($value_color));

        return $representation;
    }

    public function getFullRESTValue(Tracker_FormElement_Field_List_Value $value)
    {
        return [
            'label' => $value->getLabel(),
            'id'    => $value->getId(),
        ];
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
        $dao = new BindStaticValueDao();
        $row = $dao->searchById($bindvalue_id)->getRow();

        return $this->getValueFromRow($row);
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getAllValuesWithActiveUsersOnly(): array
    {
        return [];
    }
}
