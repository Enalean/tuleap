<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsersDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;

class Tracker_FormElement_Field_List_BindFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const STATIK  = 'static';
    public const USERS   = 'users';
    public const UGROUPS = 'ugroups';

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var BindUgroupsValueDao
     */
    private $ugroups_value_dao;
    private \Psr\Log\LoggerInterface $logger;

    public function __construct(?UGroupManager $ugroup_manager = null, ?\Psr\Log\LoggerInterface $logger = null)
    {
        $this->ugroup_manager = $ugroup_manager ? $ugroup_manager : new UGroupManager();
        $this->logger         = $logger ?? BackendLogger::getDefaultLogger();
    }

    private function getUgroupsValueDao()
    {
        if (! $this->ugroups_value_dao) {
            $this->ugroups_value_dao = new BindUgroupsValueDao();
        }
        return $this->ugroups_value_dao;
    }

    public function setUgroupsValueDao(BindUgroupsValueDao $dao)
    {
        $this->ugroups_value_dao = $dao;
    }

    /**
     * Build a binder associated to a list field.
     * @param Tracker_FormElement_Field $field
     * @param string $type ('ug', 'submit', 'Static')
     */
    public function getBind($field, $type)
    {
        $default_value = [];
        $dao           = new BindDefaultValueDao();
        foreach ($dao->searchByFieldId($field->id) as $row) {
            $default_value[$row['value_id']] = true;
        }
        $decorators = [];
        $dao        = new BindDecoratorDao();
        foreach ($dao->searchByFieldId($field->id) as $row) {
            $decorators[$row['value_id']] = new Tracker_FormElement_Field_List_BindDecorator(
                $row['field_id'],
                $row['value_id'],
                $row['red'],
                $row['green'],
                $row['blue'],
                $row['tlp_color_name']
            );
        }

        $bind = new Tracker_FormElement_Field_List_Bind_Null($field);
        switch ($type) {
            case self::STATIK:
                $dao = new BindStaticDao();
                if ($row = $dao->searchByFieldId($field->id)->getRow()) {
                    $values = [];
                    $dao    = new BindStaticValueDao();
                    foreach ($dao->searchByFieldId($field->id, $row['is_rank_alpha']) as $row_value) {
                        $values[$row_value['id']] = $this->getStaticValueInstance(
                            (int) $row_value['id'],
                            $row_value['label'],
                            $row_value['description'],
                            $row_value['rank'],
                            $row_value['is_hidden']
                        );
                    }
                    $bind = new Tracker_FormElement_Field_List_Bind_Static($field, $row['is_rank_alpha'], $values, $default_value, $decorators);
                }
                break;
            case self::USERS:
                $dao = new BindUsersDao();
                if ($row = $dao->searchByFieldId($field->id)->getRow()) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Users($field, $row['value_function'], $default_value, $decorators);
                }
                break;
            case self::UGROUPS:
                $values = [];
                foreach ($this->getUgroupsValueDao()->searchByFieldId($field->id) as $row_value) {
                    $values[$row_value['id']] = $this->getUgroupsValueInstance(
                        $row_value['id'],
                        $field->getTracker()->getProject(),
                        $row_value['ugroup_id'],
                        $row_value['is_hidden']
                    );
                }
                $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($field, array_filter($values), $default_value, $decorators, $this->ugroup_manager, $this->getUgroupsValueDao());
                break;
            default:
                $this->logger->warning('Unknown bind "' . $type . '"');
                break;
        }
        return $bind;
    }

    /**
     * Duplicate a field.
     * @param int $from_field_id
     * @param int $to_field_id
     * @return array the mapping between old values and new ones
     */
    public function duplicate($from_field_id, $to_field_id)
    {
        return $this->doDuplicate($from_field_id, $to_field_id, BindStaticValueDao::COPY_BY_VALUE);
    }

    /**
     * Duplicate a field and keep a reference to the original Static Values.
     * @param int $from_field_id
     * @param int $to_field_id
     * @return array the mapping between old values and new ones
     */
    public function duplicateByReference($from_field_id, $to_field_id)
    {
        return $this->doDuplicate($from_field_id, $to_field_id, BindStaticValueDao::COPY_BY_REFERENCE);
    }

    private function doDuplicate($from_field_id, $to_field_id, $by_reference): array
    {
        //duplicate users info, if any
        $dao = new BindUsersDao();
        $dao->duplicate($from_field_id, $to_field_id);

        //duplicate Static info, if any
        $dao = new BindStaticDao();
        $dao->duplicate($from_field_id, $to_field_id);

        $value_mapping = [];
        //duplicate Static value, if any
        $dao = new BindStaticValueDao();
        foreach ($dao->searchByFieldId($from_field_id, 0) as $row) {
            if ($id = $dao->duplicate($row['id'], $to_field_id, $by_reference)) {
                $value_mapping[$row['id']] = $id;
            }
        }

        //duplicate Ugroups value, if any
        $dao = $this->getUgroupsValueDao();
        foreach ($dao->searchByFieldId($from_field_id) as $row) {
            if ($id = $dao->duplicate($row['id'], $to_field_id)) {
                $value_mapping[$row['id']] = $id;
            }
        }

        $dao = new BindDefaultValueDao();
        $dao->duplicate($from_field_id, $to_field_id, $value_mapping);

        $dao = new BindDecoratorDao();
        $dao->duplicate($from_field_id, $to_field_id, $value_mapping);

        return $value_mapping;
    }

    /**
     * @param array the row allowing the construction of a bind
     * @return Tracker_FormElement_Field_List_Bind Object
     */
    public function getInstanceFromRow($row)
    {
        switch ($row['type']) {
            case self::STATIK:
                return new Tracker_FormElement_Field_List_Bind_Static(
                    $row['field'],
                    $row['is_rank_alpha'],
                    $row['values'],
                    $row['default_values'],
                    $row['decorators']
                );
            case self::USERS:
                return new Tracker_FormElement_Field_List_Bind_Users(
                    $row['field'],
                    $row['value_function'],
                    $row['default_values'],
                    $row['decorators']
                );
            case self::UGROUPS:
                return new Tracker_FormElement_Field_List_Bind_Ugroups(
                    $row['field'],
                    $row['values'],
                    $row['default_values'],
                    $row['decorators'],
                    $this->getUgroupManager(),
                    $this->getUgroupsValueDao()
                );
            default:
                $this->logger->warning('Unknown bind "' . $row['type'] . '"');
                return new Tracker_FormElement_Field_List_Bind_Null($row['field']);
        }
    }

    /**
     * Creates a Field_List_Bind Object
     *
     * @param SimpleXMLElement          $xml         containing the structure of the imported bind
     * @param Tracker_FormElement_Field $field       to which the bind is attached
     * @param array                     &$xmlMapping where the newly created formElements indexed by their XML IDs are stored
     *
     * @return Tracker_FormElement_Field_List_Bind Object
     */
    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        Tracker_FormElement_Field $field,
        array &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
    ) {
        $row = ['type' => (string) $xml['type'],
            'field' => $field,
            'default_values' => null,
            'decorators' => null,
        ];
        if (isset($xml->decorators)) {
            $row['decorators'] = [];
            foreach ($xml->decorators->decorator as $deco) {
                if (isset($deco['REF'])) {
                    $ID = (string) $deco['REF'];
                } else {
                    $ID = Tracker_FormElement_Field_List::NONE_VALUE;
                }
                $row['decorators'][$ID] = $this->getDecoratorInstance(
                    $field,
                    $ID,
                    (int) $deco['r'],
                    (int) $deco['g'],
                    (int) $deco['b'],
                    (string) $deco['tlp_color_name']
                );
            }
        }
        $type = (string) $xml['type'];
        switch ($type) {
            case self::STATIK:
                $row['is_rank_alpha'] = (int) $xml['is_rank_alpha'];
                $values               = [];
                if ($xml->items->item) {
                    $i = 0;
                    foreach ($xml->items->item as $item) {
                        $ID          = (string) $item['ID'];
                        $description = '';
                        if (isset($item->description)) {
                            $description = (string) $item->description;
                        }
                        $is_hidden   = isset($item['is_hidden']) && (int) $item['is_hidden'] ? 1 : 0;
                        $values[$ID] = $this->getStaticValueInstance($ID, (string) $item['label'], $description, $i++, $is_hidden);

                        $xmlMapping[$ID] = $values[$ID];
                    }
                }
                $row['values'] = $values;
                break;

            case self::USERS:
                $values = [];
                if ($xml->items->item) {
                    foreach ($xml->items->item as $item) {
                        $values[] = (string) $item['label'];
                    }
                }
                $row['value_function'] = implode(',', $values);
                break;

            case self::UGROUPS:
                $values = [];
                if ($xml->items->item) {
                    foreach ($xml->items->item as $item) {
                        $ugroup = $this->getUgroupManager()->getUGroupByName($field->getTracker()->getProject(), (string) $item['label']);
                        if ($ugroup) {
                            $ID              = (string) $item['ID'];
                            $is_hidden       = isset($item['is_hidden']) && (int) $item['is_hidden'] ? 1 : 0;
                            $values[$ID]     = new Tracker_FormElement_Field_List_Bind_UgroupsValue($ID, $ugroup, $is_hidden);
                            $xmlMapping[$ID] = $values[$ID];
                        }
                    }
                }
                $row['values'] = array_filter($values);
                break;

            default:
                break;
        }
        if (isset($xml->default_values)) {
            $row['default_values'] = [];
            foreach ($xml->default_values->value as $default_value) {
                if (isset($default_value['REF'])) {
                    $ID = (string) $default_value['REF'];
                    if (isset($xmlMapping[$ID])) {
                        $row['default_values'][$ID] = $xmlMapping[$ID];
                    }
                } else {
                    $user = $user_finder->getUser($default_value);

                    $row['default_values'][$user->getId()] = new Tracker_FormElement_Field_List_Bind_UsersValue(
                        $user->getId()
                    );
                }
            }
        }

        return $this->getInstanceFromRow($row);
    }

    /**
     * Buil an instance of static value
     *
     * @return Tracker_FormElement_Field_List_Bind_StaticValue
     */
    public function getStaticValueInstance($id, $label, $description, $rank, $is_hidden)
    {
        return new Tracker_FormElement_Field_List_Bind_StaticValue($id, $label, $description, $rank, $is_hidden);
    }

    /**
     * Build an instance of static value
     *
     * @return Tracker_FormElement_Field_List_Bind_UgroupsValue
     */
    private function getUgroupsValueInstance($id, Project $project, $ugroup_id, $is_hidden)
    {
        $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
        if ($ugroup) {
            return new Tracker_FormElement_Field_List_Bind_UgroupsValue($id, $ugroup, $is_hidden);
        }
    }

    /**
     * Build an instance of decorator
     *
     * @return Tracker_FormElement_Field_List_BindDecorator
     */
    public function getDecoratorInstance($field, $id, $r, $g, $b, $tlp_color_name)
    {
        return new Tracker_FormElement_Field_List_BindDecorator(
            $field,
            $id,
            $r,
            $g,
            $b,
            $tlp_color_name
        );
    }

    /**
     * @return string html
     */
    public function fetchCreateABind($field)
    {
        $html  = '';
        $html .= '<h3>' . dgettext('tuleap-tracker', 'Values') . '</h3>';
        $html .= '<dl id="tracker-bind-factory">';

        $html .= '<dt class="tracker-bind-type">';
        $h     = new HTML_Element_Input_Radio(dgettext('tuleap-tracker', 'Choose values'), 'formElement_data[bind-type]', self::STATIK, 'checked');
        $h->addParam('autocomplete', 'off');
        $html .= $h->render();
        $html .= '</dt>';

        $html .= '<dd class="tracker-bind-def">';
        $html .= Tracker_FormElement_Field_List_Bind_Static::fetchAdminCreateForm($field);
        $html .= '</dd>';

        $html .= '<dt class="tracker-bind-type">';
        $h     = new HTML_Element_Input_Radio(dgettext('tuleap-tracker', 'Bind to users'), 'formElement_data[bind-type]', self::USERS, '');
        $h->addParam('autocomplete', 'off');
        $html .= $h->render();
        $html .= '</dt>';

        $html .= '<dd class="tracker-bind-def">';
        $html .= Tracker_FormElement_Field_List_Bind_Users::fetchAdminCreateForm($field);
        $html .= '</dd>';

        $html .= '<dt class="tracker-bind-type">';
        $h     = new HTML_Element_Input_Radio(dgettext('tuleap-tracker', 'Bind to user groups'), 'formElement_data[bind-type]', self::UGROUPS, '');
        $h->addParam('autocomplete', 'off');
        $html .= $h->render();
        $html .= '</dt>';

        $html .= '<dd class="tracker-bind-def">';
        $html .= Tracker_FormElement_Field_List_Bind_Ugroups::fetchAdminCreateForm($field);
        $html .= '</dd>';

        $html .= '</dl>';
        return $html;
    }

    /**
     * Create a bind for the field
     *
     * @param Field $field     the field
     * @param string $type     the type of bind. If empty, STATIK
     * @param array $bind_data the data used to create the bind
     *
     * @return Bind null if error
     */
    public function createBind($field, $type, $bind_data)
    {
        $bind = null;
        switch ($type) {
            case '': //default is static
            case self::STATIK:
                $dao = new BindStaticDao();
                if ($dao->save($field->getId(), 0)) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Static($field, 0, [], [], []);
                    $bind->process($bind_data, 'no redirect');
                }
                break;
            case self::USERS:
                $dao = new BindUsersDao();
                if ($dao->save($field->getId(), [])) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Users($field, '', [], []);
                    $bind->process($bind_data, 'no redirect');
                }
                break;
            case self::UGROUPS:
                $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($field, [], [], [], $this->ugroup_manager, $this->getUgroupsValueDao());
                $bind->process($bind_data, 'no redirect');
                break;
            default:
                break;
        }
        return $bind;
    }

    public function getType($bind)
    {
        return $bind instanceof \Tracker_FormElement_Field_List_Bind_Static ? self::STATIK :
                ($bind instanceof \Tracker_FormElement_Field_List_Bind_Users ? self::USERS :
                    ($bind instanceof \Tracker_FormElement_Field_List_Bind_Ugroups ? self::UGROUPS : '')
                );
    }

    /**
     * for testing purpose
     */
    protected function getUgroupManager(): UGroupManager
    {
        return $this->ugroup_manager;
    }
}
