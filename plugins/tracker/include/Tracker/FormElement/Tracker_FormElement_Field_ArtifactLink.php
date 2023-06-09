<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Option\Option;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\PossibleParentsRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinksToRender;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinksToRenderForPerTrackerTable;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkValueSaver;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\DisplayArtifactLinkEvent;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\FieldDataBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\PossibleParentSelectorRenderer;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\PostSaveNewChangesetLinkParentArtifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RequestDataAugmentor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueEmptyChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\ArtifactInTypeTablePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\CustomColumn\CSVOutputStrategy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\CustomColumn\HTMLOutputStrategy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\CustomColumn\ValueFormatter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeTablePresenter;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

#[ConfigKeyCategory('Tracker')]
class Tracker_FormElement_Field_ArtifactLink extends Tracker_FormElement_Field // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    #[FeatureFlagConfigKey("Feature flag to hide by default reverse links in artifact view (legacy behaviour)")]
    public const HIDE_REVERSE_LINKS_KEY = 'hide_reverse_links_by_default';

    public const TYPE                    = 'art_link';
    public const CREATE_NEW_PARENT_VALUE = -1;
    public const NEW_VALUES_KEY          = 'new_values';
    public const TYPE_IS_CHILD           = '_is_child';
    public const FAKE_TYPE_IS_PARENT     = '_is_parent';
    public const NO_TYPE                 = '';
    public const FIELDS_DATA_PARENT_KEY  = 'parent';

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    private ?ChangesetValueArtifactLinkDao $cached_changeset_value_dao = null;

    /**
     * Display the html form in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text"
                         value="' .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '" autocomplete="off" />';
        $html .= '<br />';
        $html .= '<a href="#">bug #123</a><br />';
        $html .= '<a href="#">bug #321</a><br />';
        $html .= '<a href="#">story #10234</a>';
        return $html;
    }

    /**
     * Display the field value as a criteria
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
    public function fetchCriteriaValue($criteria)
    {
        $html = '<input type="text" name="criteria[' . $this->id . ']" id="tracker_report_criteria_' . $this->id . '" value="';
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            $hp    = Codendi_HTMLPurifier::instance();
            $html .= $hp->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }
        $html .= '" />';
        return $html;
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        $arr    = [];
        $values = $this->getChangesetValues($this->getCurrentUser(), $changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getLink();
        }
        $html = implode(', ', $arr);
        return $html;
    }

    public function fetchChangesetValueForType(
        $artifact_id,
        $changeset_id,
        $value,
        $type,
        $format,
        $report = null,
        $from_aid = null,
    ) {
        $value_formatter = new ValueFormatter(
            Tracker_FormElementFactory::instance(),
            new HTMLOutputStrategy(Codendi_HTMLPurifier::instance())
        );

        $current_user = $this->getCurrentUser();

        return $value_formatter->fetchFormattedValue(
            $current_user,
            $this->getChangesetValues($current_user, $changeset_id),
            $type,
            $format
        );
    }

    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        $arr    = [];
        $values = $this->getChangesetValues($this->getCurrentUser(), $changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getArtifactId();
        }

        return implode(',', $arr);
    }

    public function fetchCSVChangesetValueWithType($changeset_id, $type, $format)
    {
        $value_formatter = new ValueFormatter(
            Tracker_FormElementFactory::instance(),
            new CSVOutputStrategy(Codendi_HTMLPurifier::instance())
        );

        $current_user = $this->getCurrentUser();

        return $value_formatter->fetchFormattedValue(
            $current_user,
            $this->getChangesetValues($current_user, $changeset_id),
            $type,
            $format
        );
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        $artifact_id_array = $value->getArtifactIds();
        return implode(", ", $artifact_id_array);
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
     * @return array
     * @deprecated
     */
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        return [];
    }

    public function getFieldDataFromRESTValueByField($value, ?Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

    /**
     * Get the field data (REST or CSV) for artifact submission
     *
     * @param string   $value    The rest field value
     * @param Artifact $artifact The artifact the value is to be added/removed
     *
     * @return array
     */
    public function getFieldData($value, ?Artifact $artifact = null)
    {
        $submitted_ids = $this->getFieldDataBuilder()->getArrayOfIdsFromString($value);
        return $this->getDataLikeWebUI($submitted_ids, [$value], $artifact);
    }

    public function getFieldDataFromCSVValue($csv_value, ?Artifact $artifact = null)
    {
        return $this->getFieldData($csv_value, $artifact);
    }

    /**
     * @param array $submitted_ids
     * @param array $submitted_values
     *
     * @return array
     */
    private function getDataLikeWebUI(
        array $submitted_ids,
        array $submitted_values,
        ?Artifact $artifact = null,
    ) {
        $existing_links = $this->getArtifactLinkIdsOfLastChangeset($artifact);
        $new_values     = array_diff($submitted_ids, $existing_links);
        $removed_values = array_diff($existing_links, $submitted_ids);

        return $this->getFieldDataBuilder()->getDataLikeWebUI($new_values, $removed_values, $submitted_values);
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        $user_manager   = UserManager::instance();
        $user           = $user_manager->getCurrentUser();
        $parent_tracker = $this->getTracker()->getParent();

        if ($artifact->getParent($user) || ! $parent_tracker) {
            return '';
        }

        $prefill_parent = '';
        $name           = 'artifact[' . $this->id . ']';
        $current_user   = $this->getCurrentUser();
        $can_create     = false;

        return $this->renderParentSelector($prefill_parent, $name, $this->getPossibleParentSelector($current_user, $can_create));
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        $prefill_parent = '';
        $name           = 'artifact[' . $this->id . ']';
        $parent_tracker = $this->getTracker()->getParent();
        $current_user   = $this->getCurrentUser();
        $can_create     = false;

        if (! $parent_tracker) {
            return '';
        }

        if (isset($submitted_values['disable_artifact_link_field']) && $submitted_values['disable_artifact_link_field']) {
            return '';
        }

        return $this->renderParentSelector($prefill_parent, $name, $this->getPossibleParentSelector($current_user, $can_create));
    }

    private function getArtifactLinkIdsOfLastChangeset(?Artifact $artifact = null)
    {
        $link_ids = [];

        $current_user = $this->getCurrentUser();

        if ($artifact && $artifact->getLastChangeset()) {
            foreach ($this->getChangesetValues($current_user, (int) $artifact->getLastChangeset()->getId()) as $link_info) {
                $link_ids[] = $link_info->getArtifactId();
            }
        }

        return $link_ids;
    }

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;

                $match_expression = $this->buildMatchExpression("$b.artifact_id", $criteria_value);

                return Option::fromValue(
                    new ParametrizedFrom(
                        " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ? )
                         INNER JOIN tracker_changeset_value_artifactlink AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND " . $match_expression->sql . "
                         ) ",
                        [
                            $this->id,
                            ...$match_expression->parameters,
                        ]
                    )
                );
            }
        }

        return Option::nothing(ParametrizedFrom::class);
    }

    /**
     * @var string
     */
    protected $pattern = '[+\-]*[0-9]+';
    protected function cast($value)
    {
        return (int) $value;
    }

    private function buildMatchExpression(string $field_name, string $criteria_value): ParametrizedSQLFragment
    {
        $matches = [];
        if (preg_match('/\/(.*)\//', $criteria_value, $matches)) {
            // If it is sourrounded by /.../ then assume a regexp
            return new ParametrizedSQLFragment($field_name . " RLIKE ?", [$matches[1]]);
        }

        $matches = [];
        if (preg_match("/^(<|>|>=|<=)\s*($this->pattern)\$/", $criteria_value, $matches)) {
            // It's < or >,  = and a number then use as is
            $number = (string) ($this->cast($matches[2]));
            return new ParametrizedSQLFragment($field_name . ' ' . $matches[1] . ' ?', [$number]);
        } elseif (preg_match("/^($this->pattern)\$/", $criteria_value, $matches)) {
            // It's a number so use  equality
            $number = $this->cast($matches[1]);
            return new ParametrizedSQLFragment($field_name . ' = ?', [$number]);
        } elseif (preg_match("/^($this->pattern)\s*-\s*($this->pattern)\$/", $criteria_value, $matches)) {
            // it's a range number1-number2
            $min  = (string) ($this->cast($matches[1]));
            $max  = (string) ($this->cast($matches[2]));
            $expr = $field_name . ' >= ' . $matches[1] . ' AND ' . $field_name . ' <= ' . $matches[2];
            return new ParametrizedSQLFragment($field_name . ' >= ? AND ' . $field_name . ' <= ?', [$min, $max]);
        } else {
            // Invalid syntax - no condition
            return new ParametrizedSQLFragment('1', []);
        }
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    public function getQueryFrom()
    {
        return '';
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_ArtifactLink_ValueDao();
    }

    private function renderParentSelector(
        string $prefill_parent,
        string $name,
        \Tuleap\Tracker\Artifact\PossibleParentSelector $possible_parents_selector,
    ): string {
        $renderer = PossibleParentSelectorRenderer::buildWithDefaultTemplateRenderer();
        return $renderer->render($name, $prefill_parent, $possible_parents_selector);
    }

    private function getPossibleParentSelector(
        PFUser $user,
        bool $can_create,
    ): \Tuleap\Tracker\Artifact\PossibleParentSelector {
        $retriever = new PossibleParentsRetriever($this->getArtifactFactory(), EventManager::instance());

        return $retriever->getPossibleArtifactParents(
            $this->getTracker(),
            $user,
            0,
            0,
            $can_create,
        );
    }

    /**
     * Fetch the html widget for the field
     *
     * @param Artifact $artifact               Artifact on which we operate
     * @param string   $name                   The name, if any
     * @param string   $prefill_new_values     Prefill new values field (what the user has submitted, if any)
     * @param array    $prefill_removed_values Pre-remove values (what the user has submitted, if any)
     * @param string   $prefill_parent         Prefilled parent (what the user has submitted, if any) - Only valid on submit
     * @param bool     $read_only              True if the user can't add or remove links
     *
     * @return string html
     */
    private function fetchHtmlWidget(
        Artifact $artifact,
        $name,
        ArtifactLinksToRender $artifact_links_to_render,
        $prefill_new_values,
        $prefill_removed_values,
        $prefill_type,
        $prefill_edited_types,
        $prefill_parent,
        $read_only,
        array $additional_classes,
        $from_aid = null,
        $reverse_artifact_links = false,
    ) {
        $current_user = $this->getCurrentUser();
        $html         = '';
        if (! $read_only) {
            $html = '<div class="tracker_formelement_read_and_edit" data-test="artifact-link-section">';
        }

        if ($reverse_artifact_links) {
            $html .= '<div class="artifact-link-value-reverse">';
            if (ForgeConfig::getFeatureFlag(self::HIDE_REVERSE_LINKS_KEY)) {
                $html .= '<a href="" class="btn" id="display-tracker-form-element-artifactlink-reverse" data-test="display-reverse-links">' . dgettext('tuleap-tracker', 'Display reverse artifact links') . '</a>';
                $html .= '<div id="tracker-form-element-artifactlink-reverse" data-test="reverse-link-section" style="display: none">';
            } else {
                $html .= '<div id="tracker-form-element-artifactlink-reverse" data-test="reverse-link-section">';
            }
        } else {
            $html .= '<div class="artifact-link-value">';
        }

        $html .= '<h5 class="artifack_link_subtitle">' . $this->getWidgetTitle($reverse_artifact_links) . '</h5>';

        $html_name_new = '';

        if ($name) {
            $html_name_new = 'name="' . $name . '[new_values]"';
        }

        $hp              = Codendi_HTMLPurifier::instance();
        $read_only_class = 'read-only';

        if (! $read_only) {
            $read_only_class = '';
            $classes         = implode(" ", $additional_classes);
            $html           .= '<section class="tracker_formelement_read_and_edit_edition_section tracker-form-element-artifactlink-section ' . $hp->purify($classes) . '">';
            $html           .= '<div>';
            $html           .= '<div><span class="input-append"><input type="text"
                             ' . $html_name_new . '
                             class="tracker-form-element-artifactlink-new"
                             size="40"
                             data-test="artifact-link-submit"
                             data-preview-label="' . $hp->purify(dgettext('tuleap-tracker', 'Preview')) . '"
                             value="' .  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  . '"
                             title="' . dgettext('tuleap-tracker', 'Enter artifact ids separated with a comma') . '" />';

            $possible_parents_selector = null;
            if ($artifact->getParentWithoutPermissionChecking() === null) {
                $can_create                = $artifact->getId() === -1;
                $possible_parents_selector = $this->getPossibleParentSelector($current_user, $can_create);
            }

            if ($artifact->getTracker()->isProjectAllowedToUseType()) {
                $renderer = new \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeSelectorRenderer(
                    $this->getTypePresenterFactory(),
                    $this->getTemplateRenderer(),
                );
                $html    .= $renderer->renderToString($artifact, $prefill_type, $name, $possible_parents_selector);
            }
            $html .= '</span>';
            $html .= '</div>';

            if ($possible_parents_selector) {
                $html .= $this->renderParentSelector($prefill_parent, $name, $possible_parents_selector);
            }
            $html .= '</div>';
            $html .= '</section>'; // end of tracker_formelement_read_and_edit_edition_section
        }

        $html .= '<div class="tracker-form-element-artifactlink-list ' . $read_only_class . '" data-test="artifact-link-section">';
        if ($artifact_links_to_render->hasArtifactLinksToDisplay()) {
            $this_project_id = $this->getTracker()->getProject()->getGroupId();
            foreach ($artifact_links_to_render->getArtifactLinksForPerTrackerDisplay() as $artifact_links_per_tracker) {
                /** @var ArtifactLinksToRenderForPerTrackerTable $artifact_links_per_tracker */
                $renderer = $artifact_links_per_tracker->getRenderer();
                if ($renderer === null) {
                    $html .= dgettext('tuleap-tracker', 'No reports available');
                    continue;
                }

                $html .= '<div class="tracker-form-element-artifactlink-trackerpanel">';

                $tracker = $artifact_links_per_tracker->getTracker();
                $project = $tracker->getProject();

                $project_name = '';
                if ($project->getGroupId() != $this_project_id) {
                    $project_name  = ' (<abbr title="' . $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    $project_name .= $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML);
                    $project_name .= '</abbr>)';
                }
                $html .= '<h2 class="tracker-form-element-artifactlink-tracker_' . $tracker->getId() . '">';
                $html .= $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . $project_name;
                $html .= '</h2>';

                $json_encoded_data = json_encode(
                    [
                        'artifact_id'            => $artifact->getId(),
                        'tracker_id'             => $tracker->getId(),
                        'reverse_artifact_links' => $reverse_artifact_links,
                        'read_only'              => $read_only,
                        'from_aid'               => $from_aid,
                        'prefill_removed_values' => $prefill_removed_values,
                        'prefill_edited_types'   => $prefill_edited_types,
                    ]
                );

                $html .= '<div
                        class="tracker-form-element-artifactlink-renderer-async"
                        data-field-id="' . (int) $this->getId() . '"
                        data-renderer-data="' . Codendi_HTMLPurifier::instance()->purify($json_encoded_data) . '"></div></div>';
            }

            $html .= $this->fetchTypeTables($artifact_links_to_render, $reverse_artifact_links);
        } else {
            $html .= $this->getNoValueLabelForLinks($artifact);
        }
        $html .= '</div>';

        if ($reverse_artifact_links) {
            $html .= '</div>';
        }
        $html .= '</div>';
        if (! $read_only) {
            $html .= '</div>';
        }

        return $html;
    }

    protected function getNoValueLabelForLinks(Artifact $artifact): string
    {
        if (count($this->getReverseLinks($artifact->getId())) > 0) {
            return "<span class='empty_value has-reverse-links'>" . dgettext('tuleap-tracker', 'Empty') . "</span>";
        }

        return $this->getNoValueLabel();
    }

    private function fetchRendererAsArtifactLink(
        ArtifactLinksToRenderForPerTrackerTable $artifact_links_per_tracker,
        $read_only,
        $prefill_removed_values,
        $prefill_edited_types,
        $reverse_artifact_links,
        $from_aid,
    ) {
        $renderer = $artifact_links_per_tracker->getRenderer();
        if (! $renderer) {
            return '';
        }

        $matching_ids = $artifact_links_per_tracker->getMatchingIDs();

        return $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $prefill_removed_values, $prefill_edited_types, $reverse_artifact_links, false, $from_aid);
    }

    private function fetchTypeTables(ArtifactLinksToRender $artifact_links_to_render, $is_reverse_artifact_links)
    {
        static $type_tables_cache = [];
        if (isset($type_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links])) {
            return $type_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links];
        }
        $html              = '';
        $template_renderer = $this->getTemplateRenderer();
        foreach ($artifact_links_to_render->getArtifactLinksForPerTypeDisplay() as $artifact_links_per_type) {
            $html .= $template_renderer->renderToString(
                'artifactlink-type-table',
                new TypeTablePresenter(
                    $artifact_links_per_type->getTypePresenter(),
                    $artifact_links_per_type->getArtifactLinks(),
                    $is_reverse_artifact_links,
                    $this,
                    $this->areLinksDeletable(
                        $artifact_links_per_type->getTypePresenter(),
                        $is_reverse_artifact_links,
                    )
                )
            );
        }
        $type_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links] = $html;
        return $html;
    }

    private function areLinksDeletable(TypePresenter $type_presenter, bool $is_reverse_artifact_links): bool
    {
        $event = EventManager::instance()->dispatch(
            new DisplayArtifactLinkEvent($type_presenter)
        );

        return (! $is_reverse_artifact_links && $event->canLinkBeModified());
    }

    /**
     *
     * @param bool $reverse_artifact_links
     */
    private function getWidgetTitle($reverse_artifact_links)
    {
        if ($reverse_artifact_links) {
            return dgettext('tuleap-tracker', 'Reverse artifact links');
        }

        return dgettext('tuleap-tracker', 'Artifact links');
    }

    /**
     * Process the request
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                           $current_user    The user who mades the request
     *
     * @return void
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        switch ($request->get('func')) {
            case 'fetch-artifacts':
                $read_only              = false;
                $prefill_removed_values = [];
                $prefill_edited_types   = [];
                $only_rows              = true;
                $this_project_id        = $this->getTracker()->getProject()->getGroupId();
                $is_reverse             = false;
                $hp                     = Codendi_HTMLPurifier::instance();

                $ugroups = $current_user->getUgroups($this_project_id, []);

                $ids     = $request->get('ids'); //2, 14, 15
                $tracker = [];
                $result  = [];
                if ($this->getTracker()->isProjectAllowedToUseType()) {
                    $type_shortname = $request->get('type');
                    $type_presenter = $this->getTypePresenterFactory()->getFromShortname($type_shortname);
                }
                //We must retrieve the last changeset ids of each artifact id.
                $dao = new Tracker_ArtifactDao();
                foreach ($dao->searchLastChangesetIds($ids, $ugroups, $current_user->isSuperUser()) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker    = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project    = $tracker->getProject();

                    if ($tracker->userCanView() && ! $tracker->isDeleted()) {
                        if ($this->getTracker()->isProjectAllowedToUseType()) {
                            $matching_ids['type'] = [];
                            foreach (explode(',', $matching_ids['id']) as $id) {
                                $matching_ids['type'][$id] = $type_presenter;
                            }
                        }
                        $trf    = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key          = $this->id . '_' . $report->id . '_' . $renderer->getId();
                                    $result[$key] = $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $is_reverse, $prefill_removed_values, $prefill_edited_types, $only_rows);
                                    $head         = '<div class="tracker-form-element-artifactlink-trackerpanel">';

                                    $project_name = '';
                                    if ($project->getGroupId() != $this_project_id) {
                                        $project_name  = ' (<abbr title="' . $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
                                        $project_name .= $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML);
                                        $project_name .= '</abbr>)';
                                    }
                                    $head .= '<h2 class="tracker-form-element-artifactlink-tracker_' . $tracker->getId() . '">';
                                    $head .= $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . $project_name;
                                    $head .= '</h2>';
                                    //if ($artifact) {
                                    //    $title = $hp->purify('link a '. $tracker->getItemName(), CODENDI_PURIFIER_CONVERT_HTML);
                                    //    $head .= '<a href="'.TRACKER_BASE_URL.'/?tracker='.$tracker_id.'&func=new-artifact-link&id='.$artifact->getId().'" class="tracker-form-element-artifactlink-link-new-artifact">'. 'create a new '.$hp->purify($tracker->getItemName(), CODENDI_PURIFIER_CONVERT_HTML)  .'</a>';
                                    //}
                                    $result[$key]['head'] = $head . $result[$key]['head'];
                                    break;
                                }
                            }
                        }
                    }
                }

                $this->appendTypeTable($request, $result, $is_reverse);
                if ($result) {
                    $head = [];
                    $rows = [];
                    foreach ($result as $key => $value) {
                        $head[$key] = $value["head"];
                        $rows[$key] = $value["rows"];
                    }
                    $GLOBALS['HTML']->sendJSON(['head' => $head, 'rows' => $rows]);
                }
                exit();
            case 'fetch-aggregates':
                $read_only              = false;
                $prefill_removed_values = [];
                $only_rows              = true;
                $only_one_column        = false;
                $extracolumn            = Tracker_Report_Renderer_Table::EXTRACOLUMN_UNLINK;
                $read_only              = true;
                $use_data_from_db       = false;

                $ugroups = $current_user->getUgroups($this->getTracker()->getGroupId(), []);
                $ids     = $request->get('ids'); //2, 14, 15
                $tracker = [];
                $json    = ['tabs' => []];
                $dao     = new Tracker_ArtifactDao();
                foreach ($dao->searchLastChangesetIds($ids, $ugroups, $current_user->isSuperUser()) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker    = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project    = $tracker->getProject();
                    if ($tracker->userCanView()) {
                        if ($this->getTracker()->isProjectAllowedToUseType()) {
                            $matching_ids['type'] = [];
                        }
                        $trf    = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key            = $this->id . '_' . $report->id . '_' . $renderer->getId();
                                    $columns        = $renderer->getTableColumns($only_one_column, $use_data_from_db);
                                    $json['tabs'][] = [
                                        'key' => $key,
                                        'src' => $renderer->fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $use_data_from_db, $read_only),
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }
                $GLOBALS['HTML']->sendJSON($json);
                exit();
            case 'artifactlink-renderer-async':
                session_write_close();
                if (! $request->isAjax()) {
                    return;
                }

                if (! $request->get('renderer_data')) {
                    return;
                }

                $renderer_data = json_decode($request->get('renderer_data'), true);
                if (! $renderer_data) {
                    return;
                }

                $expected_keys                  = array_flip([
                    'artifact_id',
                    'tracker_id',
                    'reverse_artifact_links',
                    'read_only',
                    'prefill_removed_values',
                    'prefill_edited_types',
                    'from_aid',
                ]);
                $are_expected_keys_part_of_data = empty(array_diff_key($expected_keys, $renderer_data));
                if (! $are_expected_keys_part_of_data) {
                    return;
                }

                $artifact_id = $renderer_data['artifact_id'];
                $artifact    = $this->getArtifactFactory()->getArtifactByIdUserCanView($current_user, $artifact_id);
                if (! $artifact) {
                    return;
                }

                $target_tracker_id = $renderer_data['tracker_id'];
                $tracker           = $this->getTrackerFactory()->getTrackerById($target_tracker_id);
                if (! $tracker->userCanView($current_user)) {
                    return;
                }

                if ($renderer_data['reverse_artifact_links']) {
                    $artifact_links_to_render = $this->getReverseArtifactLinksToRender($artifact);
                } else {
                    $artifact_links_to_render = $this->getArtifactLinksToRenderFromChangesetValue(
                        $artifact->getValue($this)
                    );
                }

                $artifact_links_per_tracker = $artifact_links_to_render->getArtifactLinksForAGivenTracker($tracker);
                if (! $artifact_links_per_tracker) {
                    return;
                }

                echo $this->fetchRendererAsArtifactLink(
                    $artifact_links_per_tracker,
                    $renderer_data['read_only'],
                    $renderer_data['prefill_removed_values'],
                    $renderer_data['prefill_edited_types'],
                    $renderer_data['reverse_artifact_links'],
                    $renderer_data['from_aid']
                );
                break;
            default:
                parent::process($layout, $request, $current_user);
                break;
        }
    }

    /**
     * Fetch the html widget for the field
     *
     * @param string $name                   The name, if any
     * @param array  $artifact_links         The current artifact links
     * @param string $prefill_new_values     Prefill new values field (what the user has submitted, if any)
     * @param bool   $read_only              True if the user can't add or remove links
     *
     * @return string html
     */
    protected function fetchHtmlWidgetMasschange($name, $artifact_links, $prefill_new_values, $read_only)
    {
        $html          = '';
        $html_name_new = '';
        if ($name) {
            $html_name_new = 'name="' . $name . '[new_values]"';
        }
        $hp = Codendi_HTMLPurifier::instance();
        if (! $read_only) {
            $html .= '<input type="text"
                             ' . $html_name_new . '
                             value="' .  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  . '"
                             title="' . dgettext('tuleap-tracker', 'Enter artifact ids separated with a comma') . '" />';
            $html .= '<br />';
        }
        if ($artifact_links) {
            $html .= '<ul class="tracker-form-element-artifactlink-list">';
            foreach ($artifact_links as $artifact_link_info) {
                $html .= '<li>';
                $html .= $artifact_link_info->getLink();
                $html .= '</li>';
            }
            $html .= '</ul>';
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
        $links_tab         = $this->fetchLinks($artifact, $this->getArtifactLinksToRenderFromChangesetValue($value), $submitted_values);
        $reverse_links_tab = $this->fetchReverseLinks($artifact);

        return $links_tab . $reverse_links_tab;
    }

    private function fetchLinks(
        Artifact $artifact,
        ArtifactLinksToRender $artifact_links_to_render,
        array $submitted_values,
    ) {
        if (isset($submitted_values[$this->getId()])) {
            $submitted_value = $submitted_values[$this->getId()];
        }

        $prefill_new_values = '';
        if (isset($submitted_value['new_values'])) {
            $prefill_new_values = $submitted_value['new_values'];
        }

        $prefill_removed_values = [];
        if (isset($submitted_value['removed_values'])) {
            $prefill_removed_values = $submitted_value['removed_values'];
        }

        $prefill_type = '';
        if (isset($submitted_value['type'])) {
            $prefill_type = $submitted_value['type'];
        }

        $prefill_edited_types = [];
        if (isset($submitted_value['types'])) {
            $prefill_edited_types = $submitted_value['types'];
        }

        $read_only      = false;
        $name           = 'artifact[' . $this->id . ']';
        $from_aid       = $artifact->getId();
        $prefill_parent = '';

        return $this->fetchHtmlWidget(
            $artifact,
            $name,
            $artifact_links_to_render,
            $prefill_new_values,
            $prefill_removed_values,
            $prefill_type,
            $prefill_edited_types,
            $prefill_parent,
            $read_only,
            [],
            $from_aid
        );
    }

    private function getArtifactLinksToRenderFromChangesetValue(?Tracker_Artifact_ChangesetValue $value)
    {
        $artifact_links = [];
        if ($value !== null) {
            $artifact_links = $value->getValue();
        }
        return new ArtifactLinksToRender(
            $this->getCurrentUser(),
            $this,
            $this->getTrackerFactory(),
            Tracker_ReportFactory::instance(),
            $this->getTypePresenterFactory(),
            ...$artifact_links
        );
    }

    private function getReverseArtifactLinksToRender(Artifact $artifact)
    {
        $reverse_links = $this->getReverseLinks($artifact->getId());

        return new ArtifactLinksToRender(
            $this->getCurrentUser(),
            $this,
            $this->getTrackerFactory(),
            Tracker_ReportFactory::instance(),
            $this->getTypePresenterFactory(),
            ...$reverse_links
        );
    }

    private function fetchReverseLinks(Artifact $artifact)
    {
        $from_aid = $artifact->getId();

        $reverse_artifact_links_to_render = $this->getReverseArtifactLinksToRender($artifact);

        return $this->fetchHtmlWidget(
            $artifact,
            '',
            $reverse_artifact_links_to_render,
            '',
            '',
            '',
            [],
            '',
            true,
            [],
            $from_aid,
            true
        );
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        ?ArtifactLinksToRender $artifact_links_to_render = null,
    ) {
        if ($artifact_links_to_render === null) {
            $artifact_links_to_render = $this->getArtifactLinksToRenderFromChangesetValue($value);
        }
        $links_tab_read_only = $this->fetchLinksReadOnly($artifact, $artifact_links_to_render);
        $reverse_links_tab   = $this->fetchReverseLinks($artifact);

        return $links_tab_read_only . $reverse_links_tab;
    }

    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValue($artifact, $value, $submitted_values) .
            "<div class='tracker_hidden_edition_field' data-field-id=" . $this->getId() . '></div>';
    }

    private function fetchLinksReadOnly(Artifact $artifact, ArtifactLinksToRender $artifact_links_to_render)
    {
        $read_only              = true;
        $name                   = '';
        $prefill_new_values     = '';
        $prefill_removed_values = [];
        $prefill_type           = '';
        $prefill_edited_types   = [];
        $prefill_parent         = '';
        $from_aid               = $artifact->getId();

        return $this->fetchHtmlWidget(
            $artifact,
            $name,
            $artifact_links_to_render,
            $prefill_new_values,
            $prefill_removed_values,
            $prefill_type,
            $prefill_edited_types,
            $prefill_parent,
            $read_only,
            [],
            $from_aid
        );
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $html               = '';
        $prefill_new_values = '';
        if (isset($submitted_values[$this->getId()]['new_values'])) {
            $prefill_new_values = $submitted_values[$this->getId()]['new_values'];
        } elseif ($this->hasDefaultValue()) {
            $prefill_new_values = $this->getDefaultValue();
        }
        $prefill_parent = '';
        if (isset($submitted_values[$this->getId()]['parent'][0]) && is_numeric($submitted_values[$this->getId()]['parent'][0])) {
            $prefill_parent = $submitted_values[$this->getId()]['parent'][0];
        }
        $prefill_type = '';
        if (isset($submitted_values[$this->getId()]['type'])) {
            $prefill_type = $submitted_values[$this->getId()]['type'];
        }
        $prefill_edited_types = [];
        if (isset($submitted_values[$this->getId()]['types'])) {
            $prefill_edited_types = $submitted_values[$this->getId()]['types'];
        }
        $read_only              = false;
        $name                   = 'artifact[' . $this->id . ']';
        $prefill_removed_values = [];
        $artifact_links         = [];

        // Well, shouldn't be here but API doesn't provide a Null Artifact on creation yet
        // Here to avoid having to pass null arg for fetchHtmlWidget
        $artifact = new Artifact(-1, $this->tracker_id, $this->getCurrentUser()->getId(), 0, false);

        $artifact_links_to_render = new ArtifactLinksToRender(
            $this->getCurrentUser(),
            $this,
            $this->getTrackerFactory(),
            Tracker_ReportFactory::instance(),
            $this->getTypePresenterFactory(),
            ...$artifact_links
        );

        return $this->fetchHtmlWidget(
            $artifact,
            $name,
            $artifact_links_to_render,
            $prefill_new_values,
            $prefill_removed_values,
            $prefill_type,
            $prefill_edited_types,
            $prefill_parent,
            $read_only,
            ["tracker_formelement_artifact_link_editable_on_submit"]
        );
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html               = '';
        $prefill_new_values = dgettext('tuleap-tracker', 'Unchanged');
        $read_only          = false;
        $name               = 'artifact[' . $this->id . ']';
        $artifact_links     = [];

        return $this->fetchHtmlWidgetMasschange($name, $artifact_links, $prefill_new_values, $read_only);
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     *
     * @return string
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value != null) {
            $html           = '<ul>';
            $artifact_links = $value->getValue();
            foreach ($artifact_links as $artifact_link_info) {
                $html .= '<li>' . $artifact_link_info->getLabel() . '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * @return ArtifactLinkFieldValueDao
     */
    protected function getValueDao()
    {
        return new ArtifactLinkFieldValueDao();
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
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
        if (empty($value) || ! $value->getValue()) {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $artifactlink_infos = $value->getValue();
                $url                = [];
                foreach ($artifactlink_infos as $artifactlink_info) {
                    if ($ignore_perms || $artifactlink_info->userCanView($user)) {
                        $url[] = $artifactlink_info->getLink();
                    }
                }
                return implode(' , ', $url);
            default:
                $output             = PHP_EOL;
                $artifactlink_infos = $value->getValue();
                foreach ($artifactlink_infos as $artifactlink_info) {
                    if ($ignore_perms || $artifactlink_info->userCanView($user)) {
                        $output .= $artifactlink_info->getLabel();
                        $output .= PHP_EOL;
                    }
                }
                break;
        }
        return $output;
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
        // never used...
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
        $rows                   = $this->getValueDao()->searchById($value_id, $this->id);
        $artifact_links         = $this->getArtifactLinkInfos($rows);
        $reverse_artifact_links = [];

        if ($changeset) {
            $reverse_artifact_links = $this->getReverseLinks($changeset->getArtifact()->getId());
        }

        return new Tracker_Artifact_ChangesetValue_ArtifactLink(
            $value_id,
            $changeset,
            $this,
            $has_changed,
            $artifact_links,
            $reverse_artifact_links
        );
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    public function getReverseLinks($artifact_id): array
    {
        $links_data = $this->getValueDao()->searchReverseLinksById($artifact_id);

        return $this->getArtifactLinkInfos($links_data);
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    private function getArtifactLinkInfos($data)
    {
        $artifact_links = [];
        while ($row = $data->getRow()) {
            $artifact_links[$row['artifact_id']] = new Tracker_ArtifactLinkInfo(
                $row['artifact_id'],
                $row['keyword'],
                $row['group_id'],
                $row['tracker_id'],
                $row['last_changeset_id'],
                $row['nature']
            );
        }

        return $artifact_links;
    }

    /**
     * @var array
     */
    protected $artifact_links_by_changeset = [];

    /**
     *
     * @param int $changeset_id
     *
     * @return Tracker_ArtifactLinkInfo[]
     */
    protected function getChangesetValues(PFUser $user, $changeset_id): array
    {
        if (! isset($this->artifact_links_by_changeset[$changeset_id])) {
            $this->artifact_links_by_changeset[$changeset_id] = [];

            $dao = $this->getChangesetValueArtifactLinkDao();
            foreach ($dao->searchChangesetValues($this->id, $changeset_id) as $row) {
                $artifact_link_info = new Tracker_ArtifactLinkInfo(
                    $row['artifact_id'],
                    $row['keyword'],
                    $row['group_id'],
                    $row['tracker_id'],
                    $row['last_changeset_id'],
                    $row['nature']
                );

                if (! $artifact_link_info->userCanView($user)) {
                    continue;
                }

                $this->artifact_links_by_changeset[$row['changeset_id']][] = $artifact_link_info;
            }
        }
        return $this->artifact_links_by_changeset[$changeset_id];
    }

    private function getChangesetValueArtifactLinkDao(): ChangesetValueArtifactLinkDao
    {
        if (! $this->cached_changeset_value_dao) {
            $this->cached_changeset_value_dao = new ChangesetValueArtifactLinkDao();
        }

        return $this->cached_changeset_value_dao;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        if (! $old_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink) {
            return false;
        }

        $submitted_value = $this->getSubmittedValueConvertor()->convert(
            $new_value,
            $old_value
        );

        return $old_value->hasChanges($submitted_value);
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Artifact Link');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Links to other artifacts');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/artifact-chain.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/artifact-chain--plus.png');
    }

    /**
     * @return bool say if the field is a unique one
     */
    public static function getFactoryUniqueField()
    {
        return true;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Artifact $artifact The artifact
     * @param array    $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Artifact $artifact, $value)
    {
        $this->has_errors = ! $this->validate($artifact, $value);

        return ! $this->has_errors;
    }

    /**
     * Validate a required field
     *
     * @param Artifact $artifact        The artifact to check
     * @param mixed    $submitted_value The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Artifact $artifact, $submitted_value)
    {
        if ((! is_array($submitted_value) || empty($value['new_values'])) && $this->isRequired()) {
            if (! $this->isEmpty($submitted_value, $artifact)) {
                // Field is required but there are values, so field is valid
                $this->has_errors = false;
            } else {
                $this->addRequiredError();
                return false;
            }
        }

        return true;
    }

    /**
     * @param array|null $submitted_value
     *
     * @return bool true if the submitted value is empty
     */
    public function isEmpty($submitted_value, Artifact $artifact)
    {
        if ($submitted_value === null) {
            $submitted_value = [];
        }

        return $this->getSubmittedValueEmptyChecker()->isSubmittedValueEmpty(
            $submitted_value,
            $this,
            $artifact,
        );
    }

    /**
     * For legacy testing purpose
     */
    protected function getSubmittedValueEmptyChecker(): SubmittedValueEmptyChecker
    {
        return new SubmittedValueEmptyChecker();
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param string   $value    data coming from the request. Should be artifact id separated by comma
     *
     * @return bool true if the value is considered ok
     * @deprecated Use ArtifactLinkValidator instead
     *
     */
    protected function validate(Artifact $artifact, $value)
    {
        return true;
    }

    public function setArtifactFactory(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @return Tracker_ArtifactFactory
     */
    private function getArtifactFactory()
    {
        if (! $this->artifact_factory) {
            $this->artifact_factory = Tracker_ArtifactFactory::instance();
        }
        return $this->artifact_factory;
    }

    public function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    /**
     * @return Tracker_HierarchyFactory
     */
    protected function getHierarchyFactory()
    {
        return Tracker_HierarchyFactory::instance();
    }

    /**
     * @see Tracker_FormElement_Field::postSaveNewChangeset()
     */
    public function postSaveNewChangeset(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ): void {
        $queue = $this->getPostNewChangesetQueue();
        $queue->execute($artifact, $submitter, $new_changeset, $fields_data, $previous_changeset);
    }

    private function getPostNewChangesetQueue(): Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetQueue
    {
        $queue = new Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetQueue();
        $queue->add($this->getProcessChildrenTriggersCommand());
        $queue->add($this->getPostSaveNewChangesetLinkParentArtifact());

        return $queue;
    }

    /**
     * @protected for testing purpose
     */
    protected function getProcessChildrenTriggersCommand(): Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand
    {
        return new Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand(
            $this,
            $this->getWorkflowFactory()->getTriggerRulesManager()
        );
    }

    /**
     * @protected for testing purpose
     */
    protected function getPostSaveNewChangesetLinkParentArtifact(): PostSaveNewChangesetLinkParentArtifact
    {
        return new PostSaveNewChangesetLinkParentArtifact(
            new ParentLinkAction(
                $this->getArtifactFactory(),
            )
        );
    }

    public function saveNewChangeset(
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $old_changeset,
        int $new_changeset_id,
        $submitted_value,
        PFUser $submitter,
        bool $is_submission,
        bool $bypass_permissions,
        CreatedFileURLMapping $url_mapping,
    ) {
        $previous_changesetvalue = $this->getPreviousChangesetValue($old_changeset);

        $value = $this->getNormalizedSubmittedValue($submitted_value);

        $convertor       = $this->getSubmittedValueConvertor();
        $submitted_value = $convertor->convert(
            $value,
            $previous_changesetvalue
        );

        return parent::saveNewChangeset(
            $artifact,
            $old_changeset,
            $new_changeset_id,
            $submitted_value,
            $submitter,
            $is_submission,
            $bypass_permissions,
            $url_mapping
        );
    }

    /**
     * Sometimes, for example during a post action for a trigger, the workflow pass null as submitted value.
     * ArtifactLinks don't like very much this null so force it to a decent, empty value.
     */
    private function getNormalizedSubmittedValue($value)
    {
        if (is_null($value)) {
            $value = ['new_values' => ''];
        }

        return $value;
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $submitted_value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $saver = $this->getArtifactLinkValueSaver();

        return $saver->saveValue(
            $this,
            $this->getCurrentUser(),
            $artifact,
            $changeset_value_id,
            $submitted_value
        );
    }

    /** @return ArtifactLinkValueSaver */
    private function getArtifactLinkValueSaver()
    {
        return new ArtifactLinkValueSaver(
            Tracker_ArtifactFactory::instance(),
            $this->getValueDao(),
            new Tracker_ReferenceManager(
                ReferenceManager::instance(),
                Tracker_ArtifactFactory::instance()
            ),
            EventManager::instance(),
            new ArtifactLinksUsageDao(),
            $this->getTrackerFactory()->getTriggerRulesManager()
        );
    }

    private function getSubmittedValueConvertor(): SubmittedValueConvertor
    {
        return new SubmittedValueConvertor(
            Tracker_ArtifactFactory::instance()
        );
    }

    /**
     * Retrieve linked artifacts according to user's permissions
     *
     * @param Tracker_Artifact_Changeset $changeset The changeset you want to retrieve artifact from
     * @param PFUser                       $user      The user who will see the artifacts
     *
     * @return Artifact[]
     */
    public function getLinkedArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user)
    {
        $artifacts       = [];
        $changeset_value = $changeset->getValue($this);
        if ($changeset_value) {
            foreach ($changeset_value->getArtifactIds() as $id) {
                $this->addArtifactUserCanViewFromId($artifacts, $id, $user);
            }
        }
        return $artifacts;
    }

    /**
     * Retrieve sliced linked artifacts according to user's permissions
     *
     * This is nearly the same as a paginated list however, for performance
     * reasons, the total size may be different than the sum of total paginated
     * artifacts.
     *
     * Example to illustrate the difference between paginated and sliced:
     *
     * Given that artifact links are [12, 13, 24, 39, 65, 69]
     * And that the user cannot see artifact #39
     * When I request linked artifacts by bunchs of 2
     * Then I get [[12, 13], [24], [65, 69]]  # instead of [[12, 13], [24, 65], [69]]
     * And total size will be 6               # instead of 5
     *
     * @param Tracker_Artifact_Changeset $changeset The changeset you want to retrieve artifact from
     * @param PFUser                     $user      The user who will see the artifacts
     * @param int                        $limit     The number of artifact to fetch
     * @param int                        $offset    The offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getSlicedLinkedArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user, $limit, $offset)
    {
        $changeset_value = $changeset->getValue($this);
        if (! $changeset_value) {
            return new Tracker_Artifact_PaginatedArtifacts([], 0);
        }

        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);
        $artifact_ids = $changeset_value->getArtifactIds();
        $size         = count($artifact_ids);

        $artifacts = [];
        foreach (array_slice($artifact_ids, $offset, $limit) as $id) {
            $this->addArtifactUserCanViewFromId($artifacts, $id, $user);
        }

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    private function addArtifactUserCanViewFromId(array &$artifacts, $id, PFUser $user): void
    {
        $artifact = $this->getArtifactFactory()->getArtifactById($id);
        if ($artifact && $artifact->userCanView($user)) {
            $artifacts[] = $artifact;
        }
    }

    /**
     * If request come with a 'parent', it should be store in a cache
     * that will be called after the artifact update to create the
     * right _is_child link
     *
     * Please note that it only work on artifact creation.
     *
     * @param array $fields_data
     */
    public function augmentDataFromRequest(&$fields_data)
    {
        $request_data_augmentor = new RequestDataAugmentor();

        $request_data_augmentor->augmentDataFromRequest(
            $this,
            $fields_data
        );
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitArtifactLink($this);
    }

    /**
     * @return TypePresenterFactory
     */
    protected function getTypePresenterFactory()
    {
        return new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao());
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
    }

    private function appendTypeTable(Codendi_Request $request, array &$result, bool $is_reverse_artifact_links)
    {
        if (! $this->getTracker()->isProjectAllowedToUseType()) {
            return;
        }

        $type_shortname = $request->get('type');
        if (! $type_shortname) {
            return;
        }

        $type_presenter        = $this->getTypePresenterFactory()->getFromShortname($type_shortname);
        $key                   = "type_$type_shortname";
        $art_factory           = $this->getArtifactFactory();
        $artifact_html_classes = 'additional';
        $type_html             = '';
        $head_html             = '';
        $ids                   = $request->get('ids');

        foreach (explode(',', $ids) as $id) {
            $artifact = $art_factory->getArtifactById(trim($id));

            $are_links_deletable = $this->areLinksDeletable(
                $type_presenter,
                $is_reverse_artifact_links,
            );

            if (! is_null($artifact) && $artifact->getTracker()->isActive()) {
                $type_html .= $this->getTemplateRenderer()->renderToString(
                    'artifactlink-type-table-row',
                    new ArtifactInTypeTablePresenter(
                        $artifact,
                        $artifact_html_classes,
                        $this,
                        $are_links_deletable,
                    )
                );
            }
        }

        if ($type_html !== '') {
            $head_html = $this->getTemplateRenderer()->renderToString(
                'artifactlink-type-table-head',
                TypeTablePresenter::buildForHeader($type_presenter, $this, $are_links_deletable)
            );

            $result[$key] = ['head' => $head_html, 'rows' => $type_html];
        } else {
            $result[$key] = [];
        }
    }

    private function getFieldDataBuilder()
    {
        return new FieldDataBuilder();
    }
}
