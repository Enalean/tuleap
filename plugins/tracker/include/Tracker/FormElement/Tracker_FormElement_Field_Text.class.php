<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\RichTextareaProvider;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_FormElement_Field_Text extends Tracker_FormElement_Field_Alphanum
{

    public $default_properties = array(
        'rows'      => array(
            'value' => 10,
            'type'  => 'string',
            'size'  => 3,
        ),
        'cols'          => array(
            'value' => 50,
            'type'  => 'string',
            'size'  => 3,
        ),
        'default_value' => array(
            'value' => '',
            'type'  => 'text',
            'size'  => 40,
        ),
    );

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return bool true if success
     */
    public function delete()
    {
        return $this->getDao()->delete($this->id);
    }

    public function getCriteriaFrom($criteria)
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;
                return " INNER JOIN tracker_changeset_value AS $a
                         ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_text AS $b
                         ON ($b.changeset_value_id = $a.id
                             AND " . $this->buildMatchExpression("$b.value", $criteria_value) . "
                         ) ";
            }
        }
        return '';
    }

    public function getCriteriaWhere($criteria)
    {
        return '';
    }

    public function getQuerySelect()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;
        return "$R2.value AS `" . $this->name . "`";
    }

    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_text AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . " )";
    }

    protected function buildMatchExpression($field_name, $criteria_value)
    {
        $matches = array();
        $expr = parent::buildMatchExpression($field_name, $criteria_value);
        if (!$expr) {
            // else transform into a series of LIKE %word%
            if (is_array($criteria_value)) {
                $split = preg_split('/\s+/', $criteria_value['value']);
            } else {
                $split = preg_split('/\s+/', $criteria_value);
            }
            $words        = array();
            $criterie_dao = $this->getCriteriaDao();
            if ($criterie_dao === null) {
                return '';
            }
            foreach ($split as $w) {
                $words[] = $field_name . " LIKE " . $criterie_dao->getDa()->quoteLikeValueSurround($w);
            }
            $expr = join(' AND ', $words);
        }
        return $expr;
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }

    public function canBeUsedToSortReport()
    {
        return true;
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        $tracker = $this->getTracker();
        if ($tracker === null) {
            return '';
        }
        $project_id = $tracker->getGroupId();

        static $cache = [];
        if (isset($cache[$project_id][$value])) {
            return $cache[$project_id][$value];
        }

        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        $format   = $this->getRightBodyFormat($artifact, $value);
        $hp       = Codendi_HTMLPurifier::instance();

        if ($format == Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $changeset_value = $hp->purify($value, CODENDI_PURIFIER_FULL, $project_id);
        } else {
            $changeset_value = $hp->purify($value, CODENDI_PURIFIER_BASIC, $project_id);
        }

        $cache[$project_id][$value] = $changeset_value;
        return $changeset_value;
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        return $value;
    }

    protected function getValueDao()
    {
        return new Tracker_FormElement_Field_Value_TextDao();
    }
    protected function getDao()
    {
        return new Tracker_FormElement_Field_TextDao();
    }

    /**
     * Return true if this field is the semantic title field of the tracker,
     * false otherwise if not or if there is no title field defined.
     *
     * @return bool true if the field is the 'title' of the tracker
     */
    protected function isSemanticTitle()
    {
        $semantic_manager = new Tracker_SemanticManager($this->getTracker());
        $semantics        = $semantic_manager->getSemantics();
        $field            = $semantics['title']->getField();
        return ($field === $this);
    }

    private function getDefaultFormatForUser(PFUser $user)
    {
        $user_preference = $user->getPreference(PFUser::EDITION_DEFAULT_FORMAT);

        if (! $user_preference || $user_preference === Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT) {
            return Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
        }

        return Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $value  = $this->getValueFromSubmitOrDefault($submitted_values);
        $format = $this->getDefaultFormatForUser($this->getCurrentUser());

        if (isset($value['format'])) {
            $format = $value['format'];
        }

        $data_attributes = [];
        if ($value === $this->getDefaultValue()) {
            $data_attributes[] = [
                'name'  => 'field-default-value',
                'value' => '1'
            ];
        }

        return $this->getRichTextarea(null, $data_attributes, $format, $value['content']);
    }

     /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html = '';
        $value = dgettext('tuleap-tracker', 'Unchanged');

        //check if this field is the title we do not allow to change it
        if ($this->isSemanticTitle()) {
            $html .= '<textarea readonly="readonly" title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact_masschange', 'cannot_masschange_title') . '">' . $value . '</textarea>';
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= '<textarea id = field_' . $this->id . ' class="user-mention"
                                name="artifact[' . $this->id . '][content]"
                                rows="' . $this->getProperty('rows') . '"
                                cols="' . $this->getProperty('cols') . '">';
            $html .= $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML);
            $html .= '</textarea>';
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        $content = '';

        if ($value) {
            assert($value instanceof Tracker_Artifact_ChangesetValue);
            $format = $value->getFormat();
        } else {
            $default_value = $this->getDefaultValue();
            $format = $default_value['format'];
        }

        if (isset($submitted_values[$this->getId()])) {
            $content = $submitted_values[$this->getId()]['content'];
            $format  = $submitted_values[$this->getId()]['format'] == Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT ? Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT : Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
        } elseif ($value != null) {
            $content = $value->getText();
        }

        return $this->getRichTextarea($artifact, [], $format, $content);
    }

    /**
     * @return string
     */
    private function getRichTextarea(?Tracker_Artifact $artifact, array $data_attributes, string $format, string $content)
    {
        $tracker = $this->getTracker();
        if (! $tracker) {
            throw new LogicException(self::class . ' # ' . $this->getId() . ' must have a valid tracker');
        }

        $hp = Codendi_HTMLPurifier::instance();

        $rich_textarea_provider = new RichTextareaProvider(
            TemplateRendererFactory::build(),
            new \Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder(
                Tracker_FormElementFactory::instance(),
                $this->getFrozenFieldDetector()
            )
        );

        $html = '<input type="hidden"
             id="artifact[' . $this->id . ']_body_format"
             name="artifact[' . $this->id . ']_body_format"
             value="' . $hp->purify($format) . '" />';

        $html .= $rich_textarea_provider->getTextarea(
            $tracker,
            $artifact,
            $this->getCurrentUser(),
            'field_' . $this->id,
            'artifact[' . $this->id . '][content]',
            $this->getProperty('rows'),
            $this->getProperty('cols'),
            $content,
            $this->isRequired(),
            $data_attributes
        );

        return $html;
    }

     /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param bool $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
        if (empty($value) || $value->getText() == '') {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $output = $value->getContentAsText();
                break;
        }
        return $output;
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $text = $value ? $value->getValue() : '';

        if ($text === '') {
            return $this->getNoValueLabel();
        }

        return '<div class="textarea-value">' . $text . '</div>';
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_Artifact $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to)
    {
        $html = '';
        $html .= 'changed <a href="#show-diff" class="tracker_artifact_showdiff">[diff]</a>';
        $html .= $this->fetchHistory($artifact, $from, $to);
        return $html;
    }

    /**
     * Fetch the value to display changes in artifact history
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     * @return string
     */
    public function fetchHistory($artifact, $from, $to)
    {
        $from_value = $this->getValue($from['value_id']);
        $from_value = isset($from_value['value']) ? $from_value['value'] : '';
        $to_value = $this->getValue($to['value_id']);
        $to_value = isset($to_value['value']) ? $to_value['value'] : '';

        $callback = array($this, '_filter_html_callback');
        $d = new Codendi_Diff(
            array_map($callback, explode("\n", $from_value)),
            array_map($callback, explode("\n", $to_value))
        );
        $f = new Codendi_HtmlUnifiedDiffFormatter();
        $diff = $f->format($d);
        return $diff ? $diff : '<em>No changes</em>';
    }
    protected function _filter_html_callback($s)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($s, CODENDI_PURIFIER_CONVERT_HTML);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp      = Codendi_HTMLPurifier::instance();
        $html    = '';
        $content = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
            $content = $value['content'];
        }
        $html .= '<textarea rows="' . $this->getProperty('rows') . '" cols="' . $this->getProperty('cols') . '" autocomplete="off">';
        $html .=  $hp->purify($content, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</textarea>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'text');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'text_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-spin.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-spin--plus.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_Text $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';

        if ($value) {
            $html .= $value->getValue();
        }

        return $html;
    }

    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return bool
     */
    public function takesTwoColumns()
    {
        return $this->getProperty('cols') > 40;
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return bool true if Tracler is ok
     */
    public function testImport()
    {
        if (parent::testImport()) {
            if (static::class == 'Tracker_FormElement_Field_Text') {
                if (!(isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
                    var_dump($this, 'Properties must be "rows" and "cols"');
                    return false;
                }
            } elseif (static::class == 'Tracker_FormElement_Field_String') {
                if (!(isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
                    var_dump($this, 'Properties must be "maxchars" and "size"');
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        $rule = $this->getRuleString();
        $content = $this->getRightContent($value);
        if (!($is_valid = $rule->isValid($content))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_text_value', array($this->getLabel())));
        }
        return $is_valid;
    }

    protected function getRuleString()
    {
        return new Rule_String();
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $changeset_value = null;
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $changeset_value = new Tracker_Artifact_ChangesetValue_Text($value_id, $changeset, $this, $has_changed, $row['value'], $row['body_format']);
        }
        return $changeset_value;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changesetvalue, $new_value)
    {
        return $previous_changesetvalue->getText() !== (string) $new_value['content'];
    }

    /**
     * Transform REST representation of field into something that artifact createArtifact or updateArtifact can proceed
     *
     * @return mixed
     */
    public function getFieldDataFromRESTValueByField(array $value, ?Tracker_Artifact $artifact = null)
    {
        if ($this->doesValueUseTheByFieldOutput($value)) {
            $text_value = $this->formatValueWithTheByFieldOutput($value);

            return $this->getRestFieldData($text_value);
        }

        return parent::getFieldDataFromRESTValueByField($value, $artifact);
    }

    private function formatValueWithTheByFieldOutput(array $value)
    {
        return array(
            'content' => $value['value'],
            'format'  => $value['format']
        );
    }

    private function doesValueUseTheByFieldOutput(array $value)
    {
        return array_key_exists('value', $value) &&
               array_key_exists('format', $value) &&
               ! is_array($value['value']);
    }

    public function getRestFieldData($value)
    {
        if ($this->isValueAlreadyWellFormatted($value)) {
            return $value;
        }

        $data            = $this->getDefaultValue();
        $data['content'] = $value;

        return $data;
    }

    private function isValueAlreadyWellFormatted($value)
    {
        return is_array($value) && isset($value['content']) && isset($value['format']);
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
        $content     = $this->getRightContent($value);
        $body_format = $this->getRightBodyFormat($artifact, $value);

        if ($body_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $substitutor = new \Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor();
            $content     = $substitutor->substituteURLsInHTML($content, $url_mapping);
        }

        return $this->getValueDao()->createWithBodyFormat($changeset_value_id, $content, $body_format) &&
               $this->extractCrossRefs($artifact, $content);
    }

    private function getRightContent($value)
    {
        return is_array($value) ? $value['content'] : $value;
    }

    private function getRightBodyFormat(Tracker_Artifact $artifact, $value)
    {
        $last_changeset_value = $this->getLastChangesetValue($artifact);
        assert($last_changeset_value === null || $last_changeset_value instanceof Tracker_Artifact_ChangesetValue_Text);
        $old_format           = $last_changeset_value ? $last_changeset_value->getFormat() : null;
        return is_array($value) ? $value['format'] : $old_format;
    }

    protected function extractCrossRefs($artifact, $content)
    {
        return ReferenceManager::instance()->extractCrossRef(
            $content,
            $artifact->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $this->getTracker()->getGroupID(),
            UserManager::instance()->getCurrentUser()->getId(),
            $this->getTracker()->getItemName()
        );
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
        $user           = $this->getCurrentUser();
        $default_format = $this->getDefaultFormatForUser($user);

        return array(
            'format'  => $default_format,
            'content' => $this->getProperty('default_value'),
        );
    }

    public function isEmpty($value, Tracker_Artifact $artifact)
    {
        return trim($this->getRightContent($value)) === '';
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitText($this);
    }
}
