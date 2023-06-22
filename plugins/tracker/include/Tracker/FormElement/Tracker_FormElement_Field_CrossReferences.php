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

use Tuleap\ConcurrentVersionsSystem\CvsDao;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Forum\ForumDao;
use Tuleap\Forum\ForumRetriever;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\News\NewsDao;
use Tuleap\News\NewsRetriever;
use Tuleap\Option\Option;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Reference\ByNature\ConcurrentVersionsSystem\CrossReferenceCvsOrganizer;
use Tuleap\Reference\ByNature\CrossReferenceByNatureInCoreOrganizer;
use Tuleap\Reference\ByNature\Forum\CrossReferenceForumOrganizer;
use Tuleap\Reference\ByNature\News\CrossReferenceNewsOrganizer;
use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\ByNature\Wiki\WikiPageFromReferenceValueRetriever;
use Tuleap\Reference\CrossReferenceByDirectionPresenterBuilder;
use Tuleap\Reference\CrossReferencePresenterFactory;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\CrossReference\CrossReferenceFieldRenderer;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\View\Reference\CrossReferenceFieldPresenterBuilder;
use Tuleap\Reference\ByNature\FRS\CrossReferenceFRSOrganizer;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_CrossReferences extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly
{
    public const REST_REF_INDEX          = 'ref';
    public const REST_REF_URL            = 'url';
    public const REST_REF_DIRECTION      = 'direction';
    public const REST_REF_DIRECTION_IN   = 'in';
    public const REST_REF_DIRECTION_OUT  = 'out';
    public const REST_REF_DIRECTION_BOTH = 'both';

    public $default_properties = [];

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if (! $this->isUsed()) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        //Only filter query if criteria is valuated
        $criteria_value = $this->getCriteriaValue($criteria);

        if ($criteria_value === '' || $criteria_value === null) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        $a = 'A_' . $this->id;
        return Option::fromValue(
            ParametrizedFromWhere::fromParametrizedFrom(
                new ParametrizedFrom(
                    " INNER JOIN cross_references AS $a
                         ON (artifact.id = $a.source_id AND $a.source_type = ? AND $a.target_id = ?
                             OR
                             artifact.id = $a.target_id AND $a.target_type = ? AND $a.source_id = ?
                         )",
                    [Artifact::REFERENCE_NATURE, $criteria_value, Artifact::REFERENCE_NATURE, $criteria_value],
                )
            )
        );
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
        $crf = new CrossReferenceFactory(
            $changeset->getArtifact()->getId(),
            Artifact::REFERENCE_NATURE,
            $this->getTracker()->getGroupId()
        );
        $crf->fetchDatas();

        $list = [];
        $refs = $crf->getFormattedCrossReferences();
        if (! empty($refs['target'])) {
            foreach ($refs['target'] as $refTgt) {
                $list[] = [
                    self::REST_REF_INDEX     => $refTgt['ref'],
                    self::REST_REF_URL       => $refTgt['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_OUT,
                ];
            }
        }
        if (! empty($refs['source'])) {
            foreach ($refs['source'] as $refSrc) {
                $list[] = [
                    self::REST_REF_INDEX     => $refSrc['ref'],
                    self::REST_REF_URL       => $refSrc['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_IN,
                ];
            }
        }
        if (! empty($refs['both'])) {
            foreach ($refs['both'] as $refBoth) {
                $list[] = [
                    self::REST_REF_INDEX     => $refBoth['ref'],
                    self::REST_REF_URL       => $refBoth['url'],
                    self::REST_REF_DIRECTION => self::REST_REF_DIRECTION_BOTH,
                ];
            }
        }

        return $list;
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    public function getQueryFrom()
    {
        return '';
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
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
        $crossref_factory = new CrossReferenceFactory($artifact_id, Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_factory->fetchDatas();

        return $crossref_factory;
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report = null)
    {
        $html          = '';
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
        if (! $value) {
            $value = '';
        }
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" name="criteria[' . $this->id . ']" value="' . $hp->purify($this->getCriteriaValue($criteria), CODENDI_PURIFIER_CONVERT_HTML) . '" />';
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
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
        CreatedFileURLMapping $url_mapping,
    ) {
       //The field is ReadOnly
        return false;
    }

    /**
     * Keep the value
     *
     * @param Artifact                        $artifact                The artifact
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
     * @param Artifact                        $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    public function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
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
        $cross_ref_field_presenter_builder = new CrossReferenceFieldPresenterBuilder(
            new CrossReferenceByDirectionPresenterBuilder(
                EventManager::instance(),
                ReferenceManager::instance(),
                new CrossReferencePresenterFactory(
                    new CrossReferencesDao(),
                ),
                ProjectManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new CrossReferenceByNatureInCoreOrganizer(
                    new CrossReferenceWikiOrganizer(
                        ProjectManager::instance(),
                        new WikiPageFromReferenceValueRetriever(),
                    ),
                    new CrossReferenceCvsOrganizer(
                        ProjectManager::instance(),
                        new CvsDao(),
                        new TlpRelativeDatePresenterBuilder(),
                        UserManager::instance(),
                        UserHelper::instance(),
                    ),
                    new CrossReferenceFRSOrganizer(
                        new FRSPackageFactory(),
                        new FRSReleaseFactory(),
                        new FRSFileFactory()
                    ),
                    new CrossReferenceForumOrganizer(
                        ProjectManager::instance(),
                        new MessageRetriever(),
                        new ForumRetriever(
                            new ForumDao(),
                        )
                    ),
                    new CrossReferenceNewsOrganizer(
                        new NewsRetriever(
                            new NewsDao(),
                        )
                    )
                ),
            )
        );

        $field_cross_ref_renderer = new CrossReferenceFieldRenderer(
            TemplateRendererFactory::build(),
            $cross_ref_field_presenter_builder
        );

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../frontend-assets',
            '/assets/trackers'
        );
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('cross-references-fields.js'));

        return $field_cross_ref_renderer->renderCrossReferences($artifact, $this->getCurrentUser());
    }

    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return '';
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
        $output = '';

        $crf = new CrossReferenceFactory($artifact->getId(), Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
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
                $refs   = $crf->getFormattedCrossReferences();
                $src    = '';
                $tgt    = '';
                $both   = '';
                $output = PHP_EOL;
                if (! empty($refs['target'])) {
                    foreach ($refs['target'] as $refTgt) {
                        $tgt .= $refTgt['ref'];
                        $tgt .= PHP_EOL;
                        $tgt .= $refTgt['url'];
                        $tgt .= PHP_EOL;
                    }
                    $output .= ' -> Target : ' . PHP_EOL . $tgt;
                    $output .= PHP_EOL;
                }
                if (! empty($refs['source'])) {
                    foreach ($refs['source'] as $refSrc) {
                        $src .= $refSrc['ref'];
                        $src .= PHP_EOL;
                        $src .= $refSrc['url'];
                        $src .= PHP_EOL;
                    }
                    $output .= ' -> Source : ' . PHP_EOL . $src;
                    $output .= PHP_EOL;
                }
                if (! empty($refs['both'])) {
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
        $html  = '';
        $html .= '<div>' . dgettext('tuleap-tracker', 'Display in & out references') . '</div>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Cross References');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the cross references for the artifact');
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
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html          = '';
        $crossref_fact = new CrossReferenceFactory($artifact->getId(), Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupId());
        $crossref_fact->fetchDatas();
        if ($crossref_fact->getNbReferences()) {
            $html .= $crossref_fact->getHTMLDisplayCrossRefs($with_links = false, $condensed = true);
        } else {
            $html .= '<div>' . dgettext('tuleap-tracker', 'References list is empty') . '</div>';
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
     * @param mixed $submitted_value      The submitted value
     */
    public function validateFieldWithPermissionsAndRequiredStatus(
        Artifact $artifact,
        $submitted_value,
        PFUser $user,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        ?bool $is_submission = null,
    ): bool {
        return true;
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
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
