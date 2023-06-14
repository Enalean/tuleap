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

use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\CodeBlockFeaturesOnArtifact;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\RichTextareaProvider;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Text\TextFieldDao;
use Tuleap\Tracker\FormElement\Field\Text\TextValueDao;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_Text extends Tracker_FormElement_Field_Alphanum
{
    public $default_properties = [
        'rows'      => [
            'value' => 10,
            'type'  => 'string',
            'size'  => 3,
        ],
        'cols'          => [
            'value' => 50,
            'type'  => 'string',
            'size'  => 3,
        ],
        'default_value' => [
            'value' => '',
            'type'  => 'text',
            'size'  => 40,
        ],
    ];

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

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a                = 'A_' . $this->id;
                $b                = 'B_' . $this->id;
                $match_expression = $this->getCriteriaFromFragment("$b.value", $criteria_value);

                return Option::fromValue(
                    new ParametrizedFrom(
                        " INNER JOIN tracker_changeset_value AS $a
                         ON ($a.changeset_id = c.id AND $a.field_id = ?)
                         INNER JOIN tracker_changeset_value_text AS $b
                         ON ($b.changeset_value_id = $a.id
                             AND " . $match_expression->sql . "
                         ) ",
                        [$this->id, ...$match_expression->parameters],
                    ),
                );
            }
        }

        return Option::nothing(ParametrizedFrom::class);
    }

    /**
     * @param mixed $criteria_value
     */
    private function getCriteriaFromFragment(string $field_name, $criteria_value): ParametrizedSQLFragment
    {
        return $this->buildMatchExpression($field_name, $criteria_value)
             ->unwrapOr(new ParametrizedSQLFragment('1', []));
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        $R2 = 'R2_' . $this->id;
        return "$R2.value AS " . $this->getQuerySelectName();
    }

    public function getQueryFrom()
    {
        $R1 = 'R1_' . $this->id;
        $R2 = 'R2_' . $this->id;

        return "LEFT JOIN ( tracker_changeset_value AS $R1
                    INNER JOIN tracker_changeset_value_text AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = " . $this->id . " )";
    }

    protected function buildMatchExpression(string $field_name, $criteria_value): Option
    {
        $expr = parent::buildMatchExpression($field_name, $criteria_value);
        if ($expr->isNothing()) {
            // else transform into a series of LIKE %word%
            if (is_array($criteria_value)) {
                $split = preg_split('/\s+/', $criteria_value['value']);
            } else {
                $split = preg_split('/\s+/', $criteria_value);
            }
            $words      = [];
            $parameters = [];
            foreach ($split as $w) {
                $words[]      = $field_name . ' LIKE ?';
                $parameters[] = '%' . $this->getDb()->escapeLikeValue($w) . '%';
            }
            $expr = Option::fromValue(
                new ParametrizedSQLFragment(
                    join(' AND ', $words),
                    $parameters
                )
            );
        }
        return $expr;
    }

    protected function getDb(): \ParagonIE\EasyDB\EasyDB
    {
        return \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB();
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }

    public function canBeUsedToSortReport()
    {
        return true;
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
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
        } elseif ($format === Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT) {
            $common_mark_interpreter = CommonMarkInterpreter::build(
                $hp,
                new EnhancedCodeBlockExtension(CodeBlockFeaturesOnArtifact::getInstance())
            );

            $changeset_value = $common_mark_interpreter->getInterpretedContentWithReferences($value, (int) $project_id);
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
        return new TextValueDao();
    }

    protected function getDao()
    {
        return new TextFieldDao();
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

    private function getDefaultFormatForUser(PFUser $user): string
    {
        $user_preference = $user->getPreference(PFUser::EDITION_DEFAULT_FORMAT);

        if (! $user_preference || $user_preference === Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT) {
            return Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT;
        }

        if ($user_preference === Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT) {
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

        return $this->getRichTextarea(null, $format, $value['content']);
    }

     /**
     * Fetch the html code to display the field value in new artifact submission form
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html  = '';
        $value = dgettext('tuleap-tracker', 'Unchanged');

        //check if this field is the title we do not allow to change it
        if ($this->isSemanticTitle()) {
            $html .= '<textarea readonly="readonly" title="' . dgettext('tuleap-tracker', 'This field is the title of the artifact. It is not allowed to masschange it.') . '">' . $value . '</textarea>';
        } else {
            $hp    = Codendi_HTMLPurifier::instance();
            $html .= '<textarea id = field_' . $this->id . ' class="user-mention"
                                maxlength="' . TextValueValidator::MAX_TEXT_SIZE . '"
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
     * @param Artifact                        $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $content = '';

        if ($value) {
            assert($value instanceof Tracker_Artifact_ChangesetValue);
            $format = $value->getFormat();
        } else {
            $default_value = $this->getDefaultValue();
            $format        = $default_value['format'];
        }

        if (isset($submitted_values[$this->getId()])) {
            $content = $submitted_values[$this->getId()]['content'];
            $format  = $submitted_values[$this->getId()]['format'] == Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT ? Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT : Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
        } elseif ($value != null) {
            $content = $value->getText();
        }

        return $this->getRichTextarea($artifact, $format, $content);
    }

    /**
     * @return string
     */
    private function getRichTextarea(?Artifact $artifact, string $format, string $content)
    {
        $tracker = $this->getTracker();
        if (! $tracker) {
            throw new LogicException(self::class . ' # ' . $this->getId() . ' must have a valid tracker');
        }

        $hp = Codendi_HTMLPurifier::instance();

        $rich_textarea_provider = new RichTextareaProvider(
            TemplateRendererFactory::build(),
            new \Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder(
                new FileUploadDataProvider($this->getFrozenFieldDetector(), Tracker_FormElementFactory::instance())
            )
        );

        $html = '<input type="hidden"
             id="artifact[' . $this->id . ']_body_format"
             name="artifact[' . $this->id . '][format]"
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
        );

        return $html;
    }

     /**
     * Fetch data to display the field value in mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     * @param string                          $format   output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text',
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
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $text = $value ? $value->getValue() : '';

        if ($text === '') {
            return $this->getNoValueLabel();
        }

        return '<div class="textarea-value">' . $text . '</div>';
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
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
            $content = $this->getProperty('default_value');
        }
        $html .= '<textarea rows="' . $this->getProperty('rows') . '" cols="' . $this->getProperty('cols') . '" autocomplete="off">';
        $html .=  $hp->purify($content, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</textarea>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Text');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Paragraph, long text field');
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
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
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
                if (! (isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
                    var_dump($this, 'Properties must be "rows" and "cols"');
                    return false;
                }
            } elseif (static::class == 'Tracker_FormElement_Field_String') {
                if (! (isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
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
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
    {
        return (new TextValueValidator())->isValueValid(
            $this,
            $value,
        )->match(function () {
            return true;
        }, function (Fault $fault) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                (string) $fault,
            );

            return false;
        });
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
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        assert($old_value instanceof Tracker_Artifact_ChangesetValue_Text);

        if (is_array($new_value)) {
            return $old_value->getText() !== (string) $new_value['content'];
        }
        return $old_value->getText() !== (string) $new_value;
    }

    /**
     * Transform REST representation of field into something that artifact createArtifact or updateArtifact can proceed
     *
     * @return mixed
     */
    public function getFieldDataFromRESTValueByField(array $value, ?Artifact $artifact = null)
    {
        if ($this->doesValueUseTheByFieldOutput($value)) {
            $text_value = $this->formatValueWithTheByFieldOutput($value);

            return $this->getRestFieldData($text_value);
        }

        return parent::getFieldDataFromRESTValueByField($value, $artifact);
    }

    private function formatValueWithTheByFieldOutput(array $value)
    {
        return [
            'content' => $value['value'],
            'format'  => $value['format'],
        ];
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

        $data = $this->getDefaultValue();

        if (is_array($value) && isset($value['content'])) {
            $data['content'] = $value['content'];
        } else {
            $data['content'] = $value;
        }

        return $data;
    }

    private function isValueAlreadyWellFormatted($value): bool
    {
        return is_array($value) && isset($value['content']) && $this->isFormatValid($value['format']);
    }

    private function isFormatValid(?string $format): bool
    {
        return isset($format)
            && in_array(
                $format,
                [
                    Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT,
                    Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                    Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                ]
            );
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $content     = $this->getRightContent($value);
        $body_format = $this->getRightBodyFormat($artifact, $value);

        if ($body_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $substitutor = new \Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor();
            $content     = $substitutor->substituteURLsInHTML($content, $url_mapping);
        }

        $res = $this->getValueDao()->createWithBodyFormat($changeset_value_id, $content, $body_format) &&
               $this->extractCrossRefs($artifact, $content);

        if ($res) {
            $this->addRawValueToSearchIndex(new ItemToIndexQueueEventBased(EventManager::instance()), $artifact, $content, $body_format);
        }

        return $res;
    }

    private function getRightContent($value)
    {
        return is_array($value) ? $value['content'] : $value;
    }

    private function getRightBodyFormat(Artifact $artifact, $value)
    {
        $last_changeset_value = $this->getLastChangesetValue($artifact);
        assert($last_changeset_value === null || $last_changeset_value instanceof Tracker_Artifact_ChangesetValue_Text);
        $old_format = $last_changeset_value ? $last_changeset_value->getFormat() : null;
        return is_array($value) ? $value['format'] : $old_format;
    }

    public function addChangesetValueToSearchIndex(ItemToIndexQueue $index_queue, Tracker_Artifact_ChangesetValue $changeset_value): void
    {
        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_Text);
        $this->addRawValueToSearchIndex(
            $index_queue,
            $changeset_value->getChangeset()->getArtifact(),
            $changeset_value->getText(),
            $changeset_value->getFormat(),
        );
    }

    private function addRawValueToSearchIndex(ItemToIndexQueue $index_queue, Artifact $artifact, string $content, ?string $body_format): void
    {
        $event_dispatcher = EventManager::instance();
        (new FieldContentIndexer($index_queue, $event_dispatcher))->indexFieldContent(
            $artifact,
            $this,
            $content,
            in_array($body_format, ItemToIndex::ALL_CONTENT_TYPES, true) ? $body_format : ItemToIndex::CONTENT_TYPE_PLAINTEXT,
        );
    }

    /**
     * Validate a required field
     *
     * @param Artifact $artifact        The artifact to check
     * @param mixed    $submitted_value The submitted value
     */
    public function isValidRegardingRequiredProperty(Artifact $artifact, $submitted_value): bool
    {
        if (! $this->isRequired()) {
            return true;
        }

        if (empty($submitted_value)) {
            $this->addRequiredError();
            return false;
        }

        if (
            is_array($submitted_value) &&
            (
                ! isset($submitted_value["content"]) ||
                isset($submitted_value["content"]) && empty($submitted_value["content"])
            )
        ) {
            $this->addRequiredError();
            return false;
        }

        return true;
    }

    protected function extractCrossRefs($artifact, $content)
    {
        return ReferenceManager::instance()->extractCrossRef(
            $content,
            $artifact->getId(),
            Artifact::REFERENCE_NATURE,
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

        $default_value_in_text_format = $this->getProperty('default_value');
        if ($default_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            $default_value = '<p>' . nl2br(htmlentities($default_value_in_text_format)) . '</p>';
        } else {
            $default_value = $default_value_in_text_format;
        }

        return [
            'format'  => $default_format,
            'content' => $default_value,
        ];
    }

    public function isEmpty($value, Artifact $artifact)
    {
        return trim($this->getRightContent($value)) === '';
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitText($this);
    }
}
