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

namespace Tuleap\Tracker\FormElement\Field\CrossReferences;

use Codendi_HTMLPurifier;
use CrossReferenceFactory;
use EventManager;
use FRSFileFactory;
use FRSPackageFactory;
use FRSReleaseFactory;
use Override;
use PFUser;
use ProjectManager;
use ReferenceManager;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tracker_Report_Criteria_Text_ValueDao;
use Tracker_Report_Criteria_ValueDao;
use Tuleap\Forum\ForumDao;
use Tuleap\Forum\ForumRetriever;
use Tuleap\Forum\MessageRetriever;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\News\NewsDao;
use Tuleap\News\NewsRetriever;
use Tuleap\Option\Option;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Reference\ByNature\CrossReferenceByNatureInCoreOrganizer;
use Tuleap\Reference\ByNature\Forum\CrossReferenceForumOrganizer;
use Tuleap\Reference\ByNature\FRS\CrossReferenceFRSOrganizer;
use Tuleap\Reference\ByNature\News\CrossReferenceNewsOrganizer;
use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\ByNature\Wiki\WikiPageFromReferenceValueRetriever;
use Tuleap\Reference\CrossReferenceByDirectionPresenterBuilder;
use Tuleap\Reference\CrossReferencePresenterFactory;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\CrossReference\CrossReferenceFieldRenderer;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\FormElement\View\Reference\CrossReferenceFieldPresenterBuilder;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

final class CrossReferencesField extends TrackerField implements Tracker_FormElement_Field_ReadOnly
{
    public const string REST_REF_INDEX          = 'ref';
    public const string REST_REF_URL            = 'url';
    public const string REST_REF_DIRECTION      = 'direction';
    public const string REST_REF_DIRECTION_IN   = 'in';
    public const string REST_REF_DIRECTION_OUT  = 'out';
    public const string REST_REF_DIRECTION_BOTH = 'both';

    public array $default_properties = [];

    #[Override]
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

    #[Override]
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return $this->getFullRESTValue($user, $changeset);
    }

    #[Override]
    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new \Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
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

    #[Override]
    public function getQuerySelect(): string
    {
        return '';
    }

    #[Override]
    public function getQueryFrom()
    {
        return '';
    }

    #[Override]
    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?array $redirection_parameters = null,
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

    #[Override]
    public function fetchCSVChangesetValue(int $artifact_id, int $changeset_id, mixed $value, ?Tracker_Report $report = null): string
    {
        $html          = '';
        $crossref_fact = $this->getCrossReferencesFactory($artifact_id);

        if ($crossref_fact->getNbReferences()) {
            $html = $crossref_fact->getHTMLCrossRefsForCSVExport();
        }

        return $html;
    }

    #[Override]
    public function fetchCriteriaValue(Tracker_Report_Criteria $criteria): string
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<input type="text" name="criteria[' . $this->id . ']" value="' . $hp->purify($this->getCriteriaValue($criteria), CODENDI_PURIFIER_CONVERT_HTML) . '" />';
    }

    #[Override]
    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    #[Override]
    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return '';
    }

    #[Override]
    public function fetchRawValue(mixed $value): string
    {
        return 'references raw value';
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    #[Override]
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }

    #[Override]
    protected function fetchSubmitValue(array $submitted_values): string
    {
        return '';
    }

    #[Override]
    protected function fetchSubmitValueMasschange(): string
    {
        return '';
    }

    #[Override]
    protected function getValueDao()
    {
        return new CrossReferencesDao();
    }

    #[Override]
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    /**
     * Fetch the value in a specific changeset
     */
    #[Override]
    public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
    {
        //Nothing special to say here
        return '';
    }

    #[Override]
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
     * @param Artifact $artifact The artifact
     * @param int $changeset_value_id The id of the changeset_value
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    #[Override]
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
        //The field is ReadOnly
        return null;
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset The changeset (needed in only few cases like 'lud' field)
     * @param int $value_id The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    #[Override]
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
    #[Override]
    public function getRESTAvailableValues()
    {
        return null;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     * @param array $submitted_values The value already submitted by the user
     */
    #[Override]
    public function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ): string {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     *
     * @return string
     */
    #[Override]
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

        $include_assets = new IncludeViteAssets(
            __DIR__ . '/../../../../scripts/artifact/frontend-assets',
            '/assets/trackers/artifact'
        );
        $GLOBALS['HTML']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset($include_assets, 'src/fields/cross-references-fields.ts')
        );

        return $field_cross_ref_renderer->renderCrossReferences($artifact, $this->getCurrentUser());
    }

    #[Override]
    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    /**
     * Fetch data to display the field value in mail
     */
    #[Override]
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        bool $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        string $format = 'text',
    ): string {
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
    #[Override]
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<div>' . dgettext('tuleap-tracker', 'Display in & out references') . '</div>';
        return $html;
    }

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Cross References');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the cross references for the artifact');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/both_arrows.png');
    }

    #[Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
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
    #[Override]
    public function testImport()
    {
        return true;
    }

    /**
     * Validate a field
     *
     * @param mixed $submitted_value The submitted value
     */
    #[Override]
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
     * @param mixed $value data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    #[Override]
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
    #[Override]
    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

    /**
     * Fetch the element for the submit masschange form
     *
     * @return string html
     */
    #[Override]
    public function fetchSubmitMasschange()
    {
        $html = $this->fetchSubmitValueMassChange();
        return $html;
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitCrossReferences($this);
    }

    #[Override]
    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
