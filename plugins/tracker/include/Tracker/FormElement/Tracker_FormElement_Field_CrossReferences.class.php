<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_FormElement_Field_CrossReferences extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly
{

    public const REST_REF_INDEX          = 'ref';
    public const REST_REF_URL            = 'url';
    public const REST_REF_DIRECTION      = 'direction';
    public const REST_REF_DIRECTION_IN   = 'in';
    public const REST_REF_DIRECTION_OUT  = 'out';
    public const REST_REF_DIRECTION_BOTH = 'both';

    public $default_properties = array();

    public function getCriteriaFrom($criteria)
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $criteria_value = CodendiDataAccess::instance()->quoteSmart($criteria_value);
                $a = 'A_' . $this->id;
                return " INNER JOIN cross_references AS $a
                         ON (artifact.id = $a.source_id AND $a.source_type = '" . Tracker_Artifact::REFERENCE_NATURE . "' AND $a.target_id = $criteria_value
                             OR
                             artifact.id = $a.target_id AND $a.target_type = '" . Tracker_Artifact::REFERENCE_NATURE . "' AND $a.source_id = $criteria_value
                         )
                ";
            }
        }
        return '';
    }

    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return $this->getFullRESTValue($user, $changeset);
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            $this->getCrossReferenceListForREST($changeset)
        );
        return $artifact_field_value_full_representation;
    }

    private function getCrossReferenceListForREST(Tracker_Artifact_Changeset $changeset)
    {
        $crf  = new CrossReferenceFactory(
            $changeset->getArtifact()->getId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $this->getTracker()->getGroupId()
        );
        $crf->fetchDatas();

        $list = array();
        $refs = $crf->getFormattedCrossReferences();
        if (! empty($refs['target'])) {
            foreach ($refs['target'] as $refTgt) {
                $list[] = array(
                    self::REST_REF_INDEX     => $refTgt['ref'],
                    self::REST_REF_URL       => $refTgt['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_OUT,
                );
            }
        }
        if (! empty($refs['source'])) {
            foreach ($refs['source'] as $refSrc) {
                $list[] = array(
                    self::REST_REF_INDEX     => $refSrc['ref'],
                    self::REST_REF_URL       => $refSrc['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_IN,
                );
            }
        }
        if (! empty($refs['both'])) {
            foreach ($refs['both'] as $refBoth) {
                $list[] = array(
                    self::REST_REF_INDEX     => $refBoth['ref'],
                    self::REST_REF_URL       => $refBoth['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_BOTH,
                );
            }
        }

        return $list;
    }

    public function getCriteriaWhere($criteria)
    {
        return '';
    }

    public function getQuerySelect()
    {
        return '';
    }

    public function getQueryFrom()
    {
        return '';
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        $crossref_fact = $this->getCrossReferencesFactory($artifact_id);

        if ($crossref_fact->getNbReferences()) {
            $html = $crossref_fact->getHTMLDisplayCrossRefs($with_links = true, $condensed = true);
        } else {
            $html = '';
        }
        return $html;
    }

    private function getCrossReferencesFactory($artifact_id)
    {
        $crossref_factory = new CrossReferenceFactory($artifact_id, Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_factory->fetchDatas();

        return $crossref_factory;
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report = null)
    {
        $html = '';
        $crossref_fact = $this->getCrossReferencesFactory($artifact_id);

        if ($crossref_fact->getNbReferences()) {
            $html = $crossref_fact->getHTMLCrossRefsForCSVExport();
        }

        return $html;
    }

    /**
     * Display the field value as a criteria
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria)
    {
        $value = $this->getCriteriaValue($criteria);
        if (!$value) {
            $value = '';
        }
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" name="criteria[' . $this->id . ']" value="' . $hp->purify($this->getCriteriaValue($criteria), CODENDI_PURIFIER_CONVERT_HTML) . '" />';
    }

    public function fetchArtifactForOverlay(Tracker_Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return '';
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return 'references raw value';
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        return '';
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        return '';
    }

    protected function getValueDao()
    {
        return new CrossReferenceDao();
    }

    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    /**
     * Fetch the value to display changes in followups
     *
     * @param Tracker_Artifact $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     *
     * @return string
     */
    public function fetchFollowUp($artifact, $from, $to)
    {
        //don't display anything in follow-up
        return '';
    }

    /**
     * Fetch the value in a specific changeset
     *
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        //Nothing special to say here
        return '';
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
    ) {
       //The field is ReadOnly
        return false;
    }

    /**
     * Keep the value
     *
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
        //The field is ReadOnly
        return null;
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
        return null;
    }

    /**
     * Get available values of this field for REST usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getRESTAvailableValues()
    {
        return null;
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
    public function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
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
        $html = '';
        $crossref_fact = new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs();
        } else {
            $html .= '<div>' . "<span class='empty_value'>" . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'ref_list_empty') . "</span>" . '</div>';
        }
        return $html;
    }

    public function fetchArtifactCopyMode(Tracker_Artifact $artifact, array $submitted_values)
    {
        return '';
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
        $output = '';

        $crf  = new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crf->fetchDatas();

        switch ($format) {
            case 'html':
                if ($crf->getNbReferences()) {
                    $output .= $crf->getHTMLCrossRefsForMail();
                } else {
                    $output .= '-';
                }
                break;
            default:
                $refs = $crf->getFormattedCrossReferences();
                $src  = '';
                $tgt  = '';
                $both = '';
                $output = PHP_EOL;
                if (!empty($refs['target'])) {
                    foreach ($refs['target'] as $refTgt) {
                        $tgt .= $refTgt['ref'];
                        $tgt .= PHP_EOL;
                        $tgt .= $refTgt['url'];
                        $tgt .= PHP_EOL;
                    }
                    $output .= ' -> Target : ' . PHP_EOL . $tgt;
                    $output .= PHP_EOL;
                }
                if (!empty($refs['source'])) {
                    foreach ($refs['source'] as $refSrc) {
                        $src .= $refSrc['ref'];
                        $src .= PHP_EOL;
                        $src .= $refSrc['url'];
                        $src .= PHP_EOL;
                    }
                    $output .= ' -> Source : ' . PHP_EOL . $src;
                    $output .= PHP_EOL;
                }
                if (!empty($refs['both'])) {
                    foreach ($refs['both'] as $refBoth) {
                        $both .= $refBoth['ref'];
                        $both .= PHP_EOL;
                        $both .= $refBoth['url'];
                        $both .= PHP_EOL;
                    }
                    $output .= ' -> Both   : ' . PHP_EOL . $both;
                    $output .= PHP_EOL;
                }
                break;
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '';
        $html .= '<div>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'display_references') . '</div>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'crossreferences_label');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'crossreferences_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        $crossref_fact = new CrossReferenceFactory($artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs($with_links = false, $condensed = true);
        } else {
            $html .= '<div>' . $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'ref_list_empty') . '</div>';
        }
        return $html;
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracler is ok
     */
    public function testImport()
    {
        return true;
    }

     /**
     * Validate a field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $submitted_value      The submitted value
     * @param Tracker_Artifact_ChangesetValue $last_changeset_value The last changeset value of the field (give null if no old value)
     *
     * @return bool true on success or false on failure
     */
    public function validateFieldWithPermissionsAndRequiredStatus(
        Tracker_Artifact $artifact,
        $submitted_value,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        $is_submission = null
    ) {
        return true;
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        //No need to validate artifact id (read only for all)
        return true;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

     /**
     * Fetch the element for the submit masschange form
     *
     * @return string html
     */
    public function fetchSubmitMasschange()
    {
        $html = $this->fetchSubmitValueMassChange();
        return $html;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitCrossReferences($this);
    }
}
