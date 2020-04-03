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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinksToRender;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinksToRenderForPerTrackerTable;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkValueSaver;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\FieldDataBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\ArtifactInNatureTablePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn\CSVOutputStrategy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn\HTMLOutputStrategy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn\ValueFormatter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureSelectorPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureTablePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollection;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_FormElement_Field_ArtifactLink extends Tracker_FormElement_Field
{
    public const TYPE                    = 'art_link';
    public const CREATE_NEW_PARENT_VALUE = -1;
    public const NEW_VALUES_KEY          = 'new_values';
    public const NATURE_IS_CHILD         = '_is_child';
    public const NO_NATURE               = '';

    /**
     * Display some information at the top of the artifact link field value
     *
     * Parameters:
     *   'html'                   => output string html
     *   'artifact'               => input Tracker_Artifact
     *   'current_user'           => input PFUser
     *   'read_only'              => input boolean
     *   'reverse_artifact_links' => input boolean
     *   'additional_classes'     => input String[]
     */
    public const PREPEND_ARTIFACTLINK_INFORMATION = 'prepend_artifactlink_information';

    /**
     * Allow to add command to the queue that is processed after a changeset is created.
     * Add PostSaveNewChangesetCommand objects to the queue.
     *
     * Parameters:
     *    'queue' => input/output Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetQueue
     *    'field' => input Tracker_FormElement_Field
     */
    public const GET_POST_SAVE_NEW_CHANGESET_QUEUE = 'get_post_save_new_changeset_queue';

    /**
     * Called just after augmentDataFromRequest has been called.
     *
     * Parameters:
     *    'fields_data' => input/output array
     *    'field'       => input Tracker_FormElement_Field
     */
    public const AFTER_AUGMENT_DATA_FROM_REQUEST = 'after_augment_data_from_request';

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var SourceOfAssociationCollection | null
     *
     * @use getSourceOfAssociationCollection()
     */
    private $cache_source_of_association_collection = null;

    /** @return SourceOfAssociationCollection */
    private function getSourceOfAssociationCollection()
    {
        if (! $this->cache_source_of_association_collection) {
            $this->cache_source_of_association_collection = new SourceOfAssociationCollection();
        }

        return $this->cache_source_of_association_collection;
    }

    /**
     * Display the html form in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
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
            $hp = Codendi_HTMLPurifier::instance();
            $html .= $hp->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }
        $html .= '" />';
        return $html;
    }

    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        $arr = array();
        $values = $this->getChangesetValues($changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getLink();
        }
        $html = implode(', ', $arr);
        return $html;
    }

    public function fetchChangesetValueForNature(
        $artifact_id,
        $changeset_id,
        $value,
        $nature,
        $format,
        $report = null,
        $from_aid = null
    ) {
        $value_formatter = new ValueFormatter(
            Tracker_FormElementFactory::instance(),
            new HTMLOutputStrategy(Codendi_HTMLPurifier::instance())
        );

        return $value_formatter->fetchFormattedValue(
            UserManager::instance()->getCurrentUser(),
            $this->getChangesetValues($changeset_id),
            $nature,
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
        $arr = array();
        $values = $this->getChangesetValues($changeset_id);
        foreach ($values as $artifact_link_info) {
            $arr[] = $artifact_link_info->getArtifactId();
        }

        return implode(',', $arr);
    }

    public function fetchCSVChangesetValueWithNature($changeset_id, $nature, $format)
    {
        $value_formatter = new ValueFormatter(
            Tracker_FormElementFactory::instance(),
            new CSVOutputStrategy(Codendi_HTMLPurifier::instance())
        );

        return $value_formatter->fetchFormattedValue(
            UserManager::instance()->getCurrentUser(),
            $this->getChangesetValues($changeset_id),
            $nature,
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
     * @see Tracker_FormElement_Field::getFieldDataFromRESTValue()
     * @param array $value
     * @return array
     * @throws Exception
     */
    public function getFieldDataFromRESTValue(array $value, ?Tracker_Artifact $artifact = null)
    {
        if (array_key_exists('links', $value) && is_array($value['links'])) {
            $submitted_ids = $this->getFieldDataBuilder()->getArrayOfIdsFromArray($value['links']);

            return $this->getDataLikeWebUI($submitted_ids, $value['links'], $artifact);
        }
        throw new Tracker_FormElement_InvalidFieldValueException(
            'Value should be \'links\' and an array of {"id": integer, ["type": string]}'
        );
    }

    public function getFieldDataFromRESTValueByField($value, ?Tracker_Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

    /**
     * Get the field data (REST or CSV) for artifact submission
     *
     * @param string           $string_value The rest field value
     * @param Tracker_Artifact $artifact     The artifact the value is to be added/removed
     *
     * @return array
     */
    public function getFieldData($string_value, ?Tracker_Artifact $artifact = null)
    {
        $submitted_ids = $this->getFieldDataBuilder()->getArrayOfIdsFromString($string_value);
        return $this->getDataLikeWebUI($submitted_ids, array($string_value), $artifact);
    }

    public function getFieldDataFromCSVValue($csv_value, ?Tracker_Artifact $artifact = null)
    {
        return $this->getFieldData($csv_value, $artifact);
    }

    /**
     * @param array $submitted_ids
     * @param array $submitted_values
     *
     * @return array
     */
    private function getDataLikeWebUI(array $submitted_ids, array $submitted_values, ?Tracker_Artifact $artifact = null)
    {
        $existing_links   = $this->getArtifactLinkIdsOfLastChangeset($artifact);
        $new_values       = array_diff($submitted_ids, $existing_links);
        $removed_values   = array_diff($existing_links, $submitted_ids);

        return $this->getFieldDataBuilder()->getDataLikeWebUI($new_values, $removed_values, $submitted_values);
    }

    public function fetchArtifactForOverlay(Tracker_Artifact $artifact, array $submitted_values)
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

        return $this->fetchParentSelector($prefill_parent, $name, $parent_tracker, $current_user, $can_create);
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

        return $this->fetchParentSelector($prefill_parent, $name, $parent_tracker, $current_user, $can_create);
    }

    private function getArtifactLinkIdsOfLastChangeset(?Tracker_Artifact $artifact = null)
    {
        $link_ids = [];

        if ($artifact && $artifact->getLastChangeset()) {
            foreach ($this->getChangesetValues($artifact->getLastChangeset()->getId()) as $link_info) {
                $link_ids[] = $link_info->getArtifactId();
            }
        }

        return $link_ids;
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria)
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;
                return " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_artifactlink AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND " . $this->buildMatchExpression("$b.artifact_id", $criteria_value) . "
                         ) ";
            }
        }
        return '';
    }
    protected $pattern = '[+\-]*[0-9]+';
    protected function cast($value)
    {
        return (int) $value;
    }
    protected function buildMatchExpression($field_name, $criteria_value)
    {
        $expr = '';
        $matches = array();
        if (preg_match('/\/(.*)\//', $criteria_value, $matches)) {
            // If it is sourrounded by /.../ then assume a regexp
            $expr = $field_name . " RLIKE " . $this->getCriteriaDao()->da->quoteSmart($matches[1]);
        }
        if (!$expr) {
            $matches = array();
            if (preg_match("/^(<|>|>=|<=)\s*($this->pattern)\$/", $criteria_value, $matches)) {
                // It's < or >,  = and a number then use as is
                $matches[2] = (string) ($this->cast($matches[2]));
                $expr = $field_name . ' ' . $matches[1] . ' ' . $matches[2];
            } elseif (preg_match("/^($this->pattern)\$/", $criteria_value, $matches)) {
                // It's a number so use  equality
                $matches[1] = $this->cast($matches[1]);
                $expr = $field_name . ' = ' . $matches[1];
            } elseif (preg_match("/^($this->pattern)\s*-\s*($this->pattern)\$/", $criteria_value, $matches)) {
                // it's a range number1-number2
                $matches[1] = (string) ($this->cast($matches[1]));
                $matches[2] = (string) ($this->cast($matches[2]));
                $expr = $field_name . ' >= ' . $matches[1] . ' AND ' . $field_name . ' <= ' . $matches[2];
            } else {
                // Invalid syntax - no condition
                $expr = '1';
            }
        }
        return $expr;
    }

    /**
     * Get the "where" statement to allow search with this field
     *
     * @param Tracker_Report_Criteria $criteria
     *
     * @return string
     */
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

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao
     */
    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_ArtifactLink_ValueDao();
    }

    private function fetchParentSelector($prefill_parent, $name, Tracker $parent_tracker, PFUser $user, $can_create)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $possible_parents_getr = new Tracker_Artifact_PossibleParentsRetriever($this->getArtifactFactory());
        $html     = '';
        $html    .= '<p>';
        list($label, $paginated_possible_parents, $display_selector) = $possible_parents_getr->getPossibleArtifactParents($parent_tracker, $user, 0, 0);
        $possible_parents = $paginated_possible_parents->getArtifacts();
        if ($display_selector) {
            $html .= '<label>';
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_choose_parent', $purifier->purify($parent_tracker->getItemName()));
            $html .= '<select name="' . $purifier->purify($name) . '[parent]">';
            $html .= '<option value="">' . $GLOBALS['Language']->getText('global', 'please_choose_dashed') . '</option>';
            if ($can_create) {
                $html .= '<option value="' . self::CREATE_NEW_PARENT_VALUE . '">' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_create_new_parent') . '</option>';
            }
            $html .= $this->fetchArtifactParentsOptions($prefill_parent, $label, $possible_parents);
            $html .= '</select>';
            $html .= '</label>';
        } elseif (count($possible_parents) > 0) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_will_have_as_parent', array($possible_parents[0]->fetchDirectLinkToArtifactWithTitle()));
        }
        $html .= '</p>';
        return $html;
    }

    private function fetchArtifactParentsOptions($prefill_parent, $label, array $possible_parents)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        if ($possible_parents) {
            $html .= '<optgroup label="' . $purifier->purify($label) . '">';
            foreach ($possible_parents as $possible_parent) {
                $selected = '';
                if ($possible_parent->getId() == $prefill_parent) {
                    $selected = ' selected="selected"';
                }
                $html .= '<option value="' . $possible_parent->getId() . '"' . $selected . '>' . $possible_parent->getXRefAndTitle() . '</option>';
            }
            $html .= '</optgroup>';
        }
        return $html;
    }

    /**
     * Fetch the html widget for the field
     *
     * @param Tracker_Artifact $artifact               Artifact on which we operate
     * @param string           $name                   The name, if any
     * @param string           $prefill_new_values     Prefill new values field (what the user has submitted, if any)
     * @param array            $prefill_removed_values Pre-remove values (what the user has submitted, if any)
     * @param string           $prefill_parent         Prefilled parent (what the user has submitted, if any) - Only valid on submit
     * @param bool             $read_only              True if the user can't add or remove links
     *
     * @return string html
     */
    private function fetchHtmlWidget(
        Tracker_Artifact $artifact,
        $name,
        ArtifactLinksToRender $artifact_links_to_render,
        $prefill_new_values,
        $prefill_removed_values,
        $prefill_nature,
        $prefill_edited_natures,
        $prefill_parent,
        $read_only,
        array $additional_classes,
        $from_aid = null,
        $reverse_artifact_links = false
    ) {
        $current_user = $this->getCurrentUser();
        $html = '';
        if (! $read_only) {
            $html = '<div class="tracker_formelement_read_and_edit">';
        }

        if ($reverse_artifact_links) {
            $html .= '<div class="artifact-link-value-reverse">';
            $html .= '<a href="" class="btn" id="display-tracker-form-element-artifactlink-reverse">' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_display_reverse') . '</a>';
            $html .= '<div id="tracker-form-element-artifactlink-reverse" style="display: none">';
        } else {
            $html .= '<div class="artifact-link-value">';
        }

        EventManager::instance()->processEvent(
            self::PREPEND_ARTIFACTLINK_INFORMATION,
            array(
                'html'                   => &$html,
                'artifact'               => $artifact,
                'current_user'           => $current_user,
                'read_only'              => $read_only,
                'reverse_artifact_links' => $reverse_artifact_links,
                'additional_classes'     => $additional_classes
            )
        );

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
            $html            .= '<section class="tracker_formelement_read_and_edit_edition_section tracker-form-element-artifactlink-section ' . $hp->purify($classes) . '">';
            $html            .= '<div>';
            $html            .= '<div><span class="input-append" style="display:inline;"><input type="text"
                             ' . $html_name_new . '
                             class="tracker-form-element-artifactlink-new"
                             size="40"
                             data-preview-label="' . $hp->purify(dgettext('tuleap-tracker', 'Preview')) . '"
                             value="' .  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  . '"
                             title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_help') . '" />';
            if ($artifact->getTracker()->isProjectAllowedToUseNature()) {
                $natures        = $this->getNaturePresenterFactory()->getAllUsableTypesInProject($artifact->getTracker()->getProject());
                $natures_presenter = array();
                foreach ($natures as $nature) {
                    $natures_presenter[] = array(
                        'shortname'     => $nature->shortname,
                        'forward_label' => $nature->forward_label,
                        'is_selected'   => ($nature->shortname == $prefill_nature)
                    );
                }
                $html          .= $this->getTemplateRenderer()->renderToString(
                    'artifactlink-nature-selector',
                    new NatureSelectorPresenter($natures_presenter, $name . '[nature]', 'tracker-form-element-artifactlink-new nature-selector')
                );
            }
            $html .= '</span>';
            $html .= '</div>';

            $is_submit      = $artifact->getId() == -1;
            $parent_tracker = $this->getTracker()->getParent();

            if ($parent_tracker && $is_submit) {
                $can_create   = true;
                $html .= $this->fetchParentSelector($prefill_parent, $name, $parent_tracker, $current_user, $can_create);
            }
            $html .= '</div>';
            $html .= '</section>'; // end of tracker_formelement_read_and_edit_edition_section
        }

        $html .= '<div class="tracker-form-element-artifactlink-list ' . $read_only_class . '">';
        if ($artifact_links_to_render->hasArtifactLinksToDisplay()) {
            $this_project_id = $this->getTracker()->getProject()->getGroupId();
            foreach ($artifact_links_to_render->getArtifactLinksForPerTrackerDisplay() as $artifact_links_per_tracker) {
                /** @var ArtifactLinksToRenderForPerTrackerTable $artifact_links_per_tracker */
                $renderer = $artifact_links_per_tracker->getRenderer();
                if ($renderer === null) {
                    $html .= $GLOBALS['Language']->getText('plugin_tracker', 'no_reports_available');
                    continue;
                }

                $html .= '<div class="tracker-form-element-artifactlink-trackerpanel">';

                $tracker = $artifact_links_per_tracker->getTracker();
                $project = $tracker->getProject();

                $project_name = '';
                if ($project->getGroupId() != $this_project_id) {
                    $project_name = ' (<abbr title="' . $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    $project_name .= $hp->purify($project->getUnixName(), CODENDI_PURIFIER_CONVERT_HTML);
                    $project_name .= '</abbr>)';
                }
                $html   .= '<h2 class="tracker-form-element-artifactlink-tracker_' . $tracker->getId() . '">';
                $html   .= $hp->purify($tracker->getName(), CODENDI_PURIFIER_CONVERT_HTML) . $project_name;
                $html   .= '</h2>';

                $json_encoded_data = json_encode(
                    [
                        'artifact_id'            => $artifact->getId(),
                        'tracker_id'             => $tracker->getId(),
                        'reverse_artifact_links' => $reverse_artifact_links,
                        'read_only'              => $read_only,
                        'from_aid'               => $from_aid,
                        'prefill_removed_values' => $prefill_removed_values,
                        'prefill_edited_natures' => $prefill_edited_natures
                    ]
                );

                $html .= '<div
                        class="tracker-form-element-artifactlink-renderer-async"
                        data-field-id="' . (int) $this->getId() . '"
                        data-renderer-data="' . Codendi_HTMLPurifier::instance()->purify($json_encoded_data) . '"></div></div>';
            }

            $html .= $this->fetchNatureTables($artifact_links_to_render, $reverse_artifact_links);
        } else {
            $html .= $this->getNoValueLabel();
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

    private function fetchRendererAsArtifactLink(
        ArtifactLinksToRenderForPerTrackerTable $artifact_links_per_tracker,
        $read_only,
        $prefill_removed_values,
        $prefill_edited_natures,
        $reverse_artifact_links,
        $from_aid
    ) {
        $renderer = $artifact_links_per_tracker->getRenderer();
        if (! $renderer) {
            return '';
        }

        $matching_ids = $artifact_links_per_tracker->getMatchingIDs();

        return $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $prefill_removed_values, $prefill_edited_natures, $reverse_artifact_links, false, $from_aid);
    }

    private function fetchNatureTables(ArtifactLinksToRender $artifact_links_to_render, $is_reverse_artifact_links)
    {
        static $nature_tables_cache = [];
        if (isset($nature_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links])) {
            return $nature_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links];
        }
        $html              = '';
        $template_renderer = $this->getTemplateRenderer();
        foreach ($artifact_links_to_render->getArtifactLinksForPerNatureDisplay() as $artifact_links_per_nature) {
            $html .= $template_renderer->renderToString(
                'artifactlink-nature-table',
                new NatureTablePresenter(
                    $artifact_links_per_nature->getNaturePresenter(),
                    $artifact_links_per_nature->getArtifactLinks(),
                    $is_reverse_artifact_links,
                    $this
                )
            );
        }
        $nature_tables_cache[spl_object_hash($artifact_links_to_render)][$is_reverse_artifact_links] = $html;
        return $html;
    }

    /**
     *
     * @param bool $reverse_artifact_links
     */
    private function getWidgetTitle($reverse_artifact_links)
    {
        if ($reverse_artifact_links) {
            return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_reverse_title');
        }

        return $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_title');
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
                $prefill_removed_values = array();
                $prefill_edited_natures = array();
                $only_rows              = true;
                $this_project_id        = $this->getTracker()->getProject()->getGroupId();
                $is_reverse             = false;
                $hp                     = Codendi_HTMLPurifier::instance();

                $ugroups = $current_user->getUgroups($this_project_id, array());

                $ids     = $request->get('ids'); //2, 14, 15
                $tracker = array();
                $result  = array();
                if ($this->getTracker()->isProjectAllowedToUseNature()) {
                    $nature_shortname      = $request->get('nature');
                    $nature_presenter      = $this->getNaturePresenterFactory()->getFromShortname($nature_shortname);
                }
                //We must retrieve the last changeset ids of each artifact id.
                $dao = new Tracker_ArtifactDao();
                foreach ($dao->searchLastChangesetIds($ids, $ugroups, $current_user->isSuperUser()) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project = $tracker->getProject();

                    if ($tracker->userCanView() && ! $tracker->isDeleted()) {
                        if ($this->getTracker()->isProjectAllowedToUseNature()) {
                            $matching_ids['nature'] = array();
                            foreach (explode(',', $matching_ids['id']) as $id) {
                                $matching_ids['nature'][$id] = $nature_presenter;
                            }
                        }
                        $trf = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key = $this->id . '_' . $report->id . '_' . $renderer->getId();
                                    $result[$key] = $renderer->fetchAsArtifactLink($matching_ids, $this->getId(), $read_only, $is_reverse, $prefill_removed_values, $prefill_edited_natures, $only_rows);
                                    $head = '<div class="tracker-form-element-artifactlink-trackerpanel">';

                                    $project_name = '';
                                    if ($project->getGroupId() != $this_project_id) {
                                        $project_name = ' (<abbr title="' . $hp->purify($project->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML) . '">';
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

                $this->appendNatureTable($request, $result);
                if ($result) {
                    $head = array();
                    $rows = array();
                    foreach ($result as $key => $value) {
                        $head[$key] = $value["head"];
                        $rows[$key] = $value["rows"];
                    }
                    header('Content-type: application/json');
                    echo json_encode(array('head' => $head, 'rows' => $rows));
                }
                exit();
                break;
            case 'fetch-aggregates':
                $read_only              = false;
                $prefill_removed_values = array();
                $only_rows              = true;
                $only_one_column        = false;
                $extracolumn            = Tracker_Report_Renderer_Table::EXTRACOLUMN_UNLINK;
                $read_only              = true;
                $use_data_from_db       = false;

                $ugroups = $current_user->getUgroups($this->getTracker()->getGroupId(), array());
                $ids     = $request->get('ids'); //2, 14, 15
                $tracker = array();
                $json = array('tabs' => array());
                $dao = new Tracker_ArtifactDao();
                foreach ($dao->searchLastChangesetIds($ids, $ugroups, $current_user->isSuperUser()) as $matching_ids) {
                    $tracker_id = $matching_ids['tracker_id'];
                    $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
                    $project = $tracker->getProject();
                    if ($tracker->userCanView()) {
                        if ($this->getTracker()->isProjectAllowedToUseNature()) {
                            $matching_ids['nature'] = array();
                        }
                        $trf = Tracker_ReportFactory::instance();
                        $report = $trf->getDefaultReportsByTrackerId($tracker->getId());
                        if ($report) {
                            $renderers = $report->getRenderers();
                            // looking for the first table renderer
                            foreach ($renderers as $renderer) {
                                if ($renderer->getType() === Tracker_Report_Renderer::TABLE) {
                                    $key = $this->id . '_' . $report->id . '_' . $renderer->getId();
                                    $columns        = $renderer->getTableColumns($only_one_column, $use_data_from_db);
                                    $json['tabs'][] = array(
                                        'key' => $key,
                                        'src' => $renderer->fetchAggregates($matching_ids, $extracolumn, $only_one_column, $columns, $use_data_from_db, $read_only),
                                    );
                                    break;
                                }
                            }
                        }
                    }
                }
                header('Content-type: application/json');
                echo json_encode($json);
                exit();
                break;
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

                $artifact_id      = $renderer_data['artifact_id'];
                $artifact = $this->getArtifactFactory()->getArtifactByIdUserCanView($current_user, $artifact_id);
                if (! $artifact) {
                    return;
                }

                $target_tracker_id = $renderer_data['tracker_id'];
                $tracker = $this->getTrackerFactory()->getTrackerById($target_tracker_id);
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
                    $renderer_data['prefill_edited_natures'],
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
        $html = '';
        $html_name_new = '';
        if ($name) {
            $html_name_new = 'name="' . $name . '[new_values]"';
        }
        $hp = Codendi_HTMLPurifier::instance();
        if (!$read_only) {
            $html .= '<input type="text"
                             ' . $html_name_new . '
                             value="' .  $hp->purify($prefill_new_values, CODENDI_PURIFIER_CONVERT_HTML)  . '"
                             title="' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'formelement_artifactlink_help') . '" />';
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
        $links_tab         = $this->fetchLinks($artifact, $this->getArtifactLinksToRenderFromChangesetValue($value), $submitted_values);
        $reverse_links_tab = $this->fetchReverseLinks($artifact);

        return $links_tab . $reverse_links_tab;
    }

    private function fetchLinks(
        Tracker_Artifact $artifact,
        ArtifactLinksToRender $artifact_links_to_render,
        array $submitted_values
    ) {
        if (isset($submitted_values[$this->getId()])) {
            $submitted_value = $submitted_values[$this->getId()];
        }

        $prefill_new_values = '';
        if (isset($submitted_value['new_values'])) {
            $prefill_new_values = $submitted_value['new_values'];
        }

        $prefill_removed_values = array();
        if (isset($submitted_value['removed_values'])) {
            $prefill_removed_values = $submitted_value['removed_values'];
        }

        $prefill_nature = '';
        if (isset($submitted_value['nature'])) {
            $prefill_nature = $submitted_value['nature'];
        }

        $prefill_edited_natures = array();
        if (isset($submitted_value['natures'])) {
            $prefill_edited_natures = $submitted_value['natures'];
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
            $prefill_nature,
            $prefill_edited_natures,
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
            $this->getNaturePresenterFactory(),
            ...$artifact_links
        );
    }

    private function getReverseArtifactLinksToRender(Tracker_Artifact $artifact)
    {
        $reverse_links = $this->getReverseLinks($artifact->getId());

        return new ArtifactLinksToRender(
            $this->getCurrentUser(),
            $this,
            $this->getTrackerFactory(),
            Tracker_ReportFactory::instance(),
            $this->getNaturePresenterFactory(),
            ...$reverse_links
        );
    }

    private function fetchReverseLinks(Tracker_Artifact $artifact)
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
            array(),
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
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        ?ArtifactLinksToRender $artifact_links_to_render = null
    ) {
        if ($artifact_links_to_render === null) {
            $artifact_links_to_render = $this->getArtifactLinksToRenderFromChangesetValue($value);
        }
        $links_tab_read_only = $this->fetchLinksReadOnly($artifact, $artifact_links_to_render);
        $reverse_links_tab   = $this->fetchReverseLinks($artifact);

        return $links_tab_read_only . $reverse_links_tab;
    }

    public function fetchArtifactCopyMode(Tracker_Artifact $artifact, array $submitted_values)
    {
        return '';
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValue($artifact, $value, $submitted_values) .
            "<div class='tracker_hidden_edition_field' data-field-id=" . $this->getId() . '></div>';
    }

    private function fetchLinksReadOnly(Tracker_Artifact $artifact, ArtifactLinksToRender $artifact_links_to_render)
    {
        $read_only              = true;
        $name                   = '';
        $prefill_new_values     = '';
        $prefill_removed_values = array();
        $prefill_nature         = '';
        $prefill_edited_natures = array();
        $prefill_parent         = '';
        $from_aid               = $artifact->getId();

        return $this->fetchHtmlWidget(
            $artifact,
            $name,
            $artifact_links_to_render,
            $prefill_new_values,
            $prefill_removed_values,
            $prefill_nature,
            $prefill_edited_natures,
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
        $html = '';
        $prefill_new_values = '';
        if (isset($submitted_values[$this->getId()]['new_values'])) {
            $prefill_new_values = $submitted_values[$this->getId()]['new_values'];
        } elseif ($this->hasDefaultValue()) {
            $prefill_new_values = $this->getDefaultValue();
        }
        $prefill_parent = '';
        if (isset($submitted_values[$this->getId()]['parent'])) {
            $prefill_parent = $submitted_values[$this->getId()]['parent'];
        }
        $prefill_nature = '';
        if (isset($submitted_values[$this->getId()]['nature'])) {
            $prefill_nature = $submitted_values[$this->getId()]['nature'];
        }
        $prefill_edited_natures = array();
        if (isset($submitted_values[$this->getId()]['natures'])) {
            $prefill_edited_natures = $submitted_values[$this->getId()]['natures'];
        }
        $read_only              = false;
        $name                   = 'artifact[' . $this->id . ']';
        $prefill_removed_values = array();
        $artifact_links         = array();

        // Well, shouldn't be here but API doesn't provide a Null Artifact on creation yet
        // Here to avoid having to pass null arg for fetchHtmlWidget
        $artifact = new Tracker_Artifact(-1, $this->tracker_id, $this->getCurrentUser()->getId(), 0, false);

        $artifact_links_to_render = new ArtifactLinksToRender(
            $this->getCurrentUser(),
            $this,
            $this->getTrackerFactory(),
            Tracker_ReportFactory::instance(),
            $this->getNaturePresenterFactory(),
            ...$artifact_links
        );

        return $this->fetchHtmlWidget(
            $artifact,
            $name,
            $artifact_links_to_render,
            $prefill_new_values,
            $prefill_removed_values,
            $prefill_nature,
            $prefill_edited_natures,
            $prefill_parent,
            $read_only,
            ["tracker_formelement_artifact_link_editable_on_submit"]
        );
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
        $html = '';
        $prefill_new_values     = dgettext('tuleap-tracker', 'Unchanged');
        $read_only              = false;
        $name                   = 'artifact[' . $this->id . ']';
        $artifact_links         = array();

        return $this->fetchHtmlWidgetMasschange($name, $artifact_links, $prefill_new_values, $read_only);
    }


    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     *
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value != null) {
            $html = '<ul>';
            $artifact_links = $value->getValue();
            foreach ($artifact_links as $artifact_link_info) {
                $html .= '<li>' . $artifact_link_info->getLabel() . '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /**
     * @return Tracker_FormElement_Field_Value_ArtifactLinkDao
     */
    protected function getValueDao()
    {
        return new Tracker_FormElement_Field_Value_ArtifactLinkDao();
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param bool $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
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
        if (empty($value) || !$value->getValue()) {
            return '-';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $artifactlink_infos = $value->getValue();
                $url = array();
                foreach ($artifactlink_infos as $artifactlink_info) {
                    if ($ignore_perms || $artifactlink_info->userCanView($user)) {
                        $url[] = $artifactlink_info->getLink();
                    }
                }
                return implode(' , ', $url);
            default:
                $output = PHP_EOL;
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
        // never used...
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
        $reverse_artifact_links = array();

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

    private function getReverseLinks($artifact_id)
    {
        $links_data = $this->getValueDao()->searchReverseLinksById($artifact_id);

        return $this->getArtifactLinkInfos($links_data);
    }

    /**
     * @return array
     */
    private function getReverseLinksIds($artifact_id)
    {
        $reverse_links_infos = $this->getReverseLinks($artifact_id);

        $reverse_links_ids = [];
        foreach ($reverse_links_infos as $reverse_link_info) {
            $reverse_links_ids[] = $reverse_link_info->getArtifactId();
        }

        return $reverse_links_ids;
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    private function getArtifactLinkInfos($data)
    {
        $artifact_links = array();
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
     * @return array
     */
    protected $artifact_links_by_changeset = array();

    /**
     *
     * @param int $changeset_id
     *
     * @return Tracker_ArtifactLinkInfo[]
     */
    protected function getChangesetValues($changeset_id)
    {
        if (!isset($this->artifact_links_by_changeset[$changeset_id])) {
            $this->artifact_links_by_changeset[$changeset_id] = array();

            $da = CodendiDataAccess::instance();
            $field_id     = $da->escapeInt($this->id);
            $changeset_id = $da->escapeInt($changeset_id);
            $sql = "SELECT cv.changeset_id, cv.has_changed, val.*, a.tracker_id, a.last_changeset_id
                    FROM tracker_changeset_value_artifactlink AS val
                         INNER JOIN tracker_artifact AS a ON(a.id = val.artifact_id)
                         INNER JOIN tracker AS t ON(t.id = a.tracker_id AND t.deletion_date IS NULL)
                         INNER JOIN groups ON (t.group_id = groups.group_id)
                         INNER JOIN tracker_changeset_value AS cv
                         ON ( val.changeset_value_id = cv.id
                          AND cv.field_id = $field_id
                          AND cv.changeset_id = $changeset_id
                         )
                    WHERE groups.status = 'A'
                    ORDER BY val.artifact_id";
            $dao = new DataAccessObject();
            foreach ($dao->retrieve($sql) as $row) {
                $this->artifact_links_by_changeset[$row['changeset_id']][] = new Tracker_ArtifactLinkInfo(
                    $row['artifact_id'],
                    $row['keyword'],
                    $row['group_id'],
                    $row['tracker_id'],
                    $row['last_changeset_id'],
                    $row['nature']
                );
            }
        }
        return $this->artifact_links_by_changeset[$changeset_id];
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        if (! $old_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink) {
            return false;
        }

        $source_of_association_collection_dev_null = new SourceOfAssociationCollection();
        $submitted_value = $this->getSubmittedValueConvertor()->convert(
            $new_value,
            $source_of_association_collection_dev_null,
            $artifact,
            $old_value
        );

        return $old_value->hasChanges($submitted_value);
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifact_link_label');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'artifact_link_description');
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
     * @param Tracker_Artifact $artifact The artifact
     * @param array            $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Tracker_Artifact $artifact, $value)
    {
        $this->has_errors = ! $this->validate($artifact, $value);

        return ! $this->has_errors;
    }

    /**
     * Validate a required field
     *
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $value      The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Tracker_Artifact $artifact, $value)
    {
        if ((! is_array($value) || empty($value['new_values'])) && $this->isRequired()) {
            if (! $this->isEmpty($value, $artifact)) {
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
     * Say if the submitted value is empty
     * if no last changeset values and empty submitted values : empty
     * if not empty last changeset values and empty submitted values : not empty
     * if empty new values and not empty last changeset values and not empty removed values have the same size: empty
     *
     * @param array            $submitted_value
     *
     * @return bool true if the submitted value is empty
     */
    public function isEmpty($submitted_value, Tracker_Artifact $artifact)
    {
        $hasNoNewValues           = empty($submitted_value['new_values']);
        $hasNoLastChangesetValues = true;
        $last_changeset_values    = array();
        $last_changeset_value     = $this->getLastChangesetValue($artifact);

        if ($last_changeset_value) {
            $last_changeset_values    = $last_changeset_value->getArtifactIds();
            $hasNoLastChangesetValues = empty($last_changeset_values);
        }

        $hasLastChangesetValues   = !$hasNoLastChangesetValues;

        if (
            ($hasNoLastChangesetValues &&
            $hasNoNewValues) ||
             ($hasLastChangesetValues &&
             $hasNoNewValues &&
                $this->allLastChangesetValuesRemoved($last_changeset_values, $submitted_value))
        ) {
            return true;
        }
        return false;
    }

    /**
     * Say if all values of the changeset have been removed
     *
     * @param array $last_changeset_values
     * @param array $submitted_value
     *
     * @return bool true if all values have been removed
     */
    private function allLastChangesetValuesRemoved($last_changeset_values, $submitted_value)
    {
        return !empty($submitted_value['removed_values'])
            && count($last_changeset_values) == count($submitted_value['removed_values']);
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param string           $value    data coming from the request. Should be artifact id separated by comma
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        return $this->getArtifactLinkValidator()->isValid($value, $artifact, $this);
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
        if (!$this->artifact_factory) {
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
        Tracker_Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        $queue = $this->getPostNewChangesetQueue();
        $queue->execute($artifact, $submitter, $new_changeset, $previous_changeset);
    }

    private function getPostNewChangesetQueue()
    {
        $queue = new Tracker_FormElement_Field_ArtifactLink_PostSaveNewChangesetQueue();
        $queue->add($this->getUpdateLinkingDirectionCommand());
        $queue->add($this->getProcessChildrenTriggersCommand());

        EventManager::instance()->processEvent(
            self::GET_POST_SAVE_NEW_CHANGESET_QUEUE,
            array(
                'field' => $this,
                'queue' => $queue
            )
        );

        return $queue;
    }

    /**
     * @protected for testing purpose
     */
    protected function getProcessChildrenTriggersCommand()
    {
        return new Tracker_FormElement_Field_ArtifactLink_ProcessChildrenTriggersCommand(
            $this,
            $this->getWorkflowFactory()->getTriggerRulesManager()
        );
    }

    private function getUpdateLinkingDirectionCommand()
    {
        return new Tracker_FormElement_Field_ArtifactLink_UpdateLinkingDirectionCommand($this->getSourceOfAssociationCollection());
    }

    public function saveNewChangeset(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_Changeset $old_changeset,
        int $new_changeset_id,
        $submitted_value,
        PFUser $submitter,
        bool $is_submission,
        bool $bypass_permissions,
        CreatedFileURLMapping $url_mapping
    ) {
        $previous_changesetvalue = $this->getPreviousChangesetValue($old_changeset);

        $value = $this->getNormalizedSubmittedValue($submitted_value);

        $convertor       = $this->getSubmittedValueConvertor();
        $submitted_value = $convertor->convert(
            $value,
            $this->getSourceOfAssociationCollection(),
            $artifact,
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
            $value = array('new_values' => '');
        }

        return $value;
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $submitted_value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping
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
            new ArtifactLinksUsageDao()
        );
    }

    /** @return SubmittedValueConvertor */
    private function getSubmittedValueConvertor()
    {
        return new SubmittedValueConvertor(
            Tracker_ArtifactFactory::instance(),
            new SourceOfAssociationDetector(
                Tracker_HierarchyFactory::instance()
            )
        );
    }

    /**
     * Retrieve linked artifacts according to user's permissions
     *
     * @param Tracker_Artifact_Changeset $changeset The changeset you want to retrieve artifact from
     * @param PFUser                       $user      The user who will see the artifacts
     *
     * @return Tracker_Artifact[]
     */
    public function getLinkedArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user)
    {
        $artifacts = array();
        $changeset_value = $changeset->getValue($this);
        if ($changeset_value) {
            foreach ($changeset_value->getArtifactIds() as $id) {
                $this->addArtifactUserCanViewFromId($artifacts, $id, $user);
            }
        }
        return $artifacts;
    }

    /**
     * Retrieve linked artifacts and reverse linked artifacts according to user's permissions
     *
     * @return Tracker_Artifact[]
     */
    public function getLinkedAndReverseArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user)
    {
        $artifacts        = [];
        $changeset_value  = $changeset->getValue($this);
        $all_artifact_ids = $this->getReverseLinksIds($changeset->getArtifact()->getId());

        if ($changeset_value) {
            $all_artifact_ids = array_unique(array_merge($all_artifact_ids, $changeset_value->getArtifactIds()));
        }

        foreach ($all_artifact_ids as $id) {
            $this->addArtifactUserCanViewFromId($artifacts, $id, $user);
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
            return new Tracker_Artifact_PaginatedArtifacts(array(), 0);
        }

        assert($changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);
        $artifact_ids = $changeset_value->getArtifactIds();
        $size = count($artifact_ids);

        $artifacts = array();
        foreach (array_slice($artifact_ids, $offset, $limit) as $id) {
            $this->addArtifactUserCanViewFromId($artifacts, $id, $user);
        }

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /** @return Tracker_Artifact|null */
    private function addArtifactUserCanViewFromId(array &$artifacts, $id, PFUser $user)
    {
        $artifact = $this->getArtifactFactory()->getArtifactById($id);
        if ($artifact && $artifact->userCanView($user)) {
            $artifacts[] = $artifact;
        }
    }

    /**
     * If request come with a 'parent', it should be automagically transformed as
     * 'new_values'.
     * Please note that it only work on artifact creation.
     *
     * @param type $fields_data
     */
    public function augmentDataFromRequest(&$fields_data)
    {
        $new_values = array();

        if ($this->getTracker()->isProjectAllowedToUseNature()) {
            $this->addNewValuesInNaturesArray($fields_data);
        }

        if (! empty($fields_data[$this->getId()]['parent'])) {
            $parent = intval($fields_data[$this->getId()]['parent']);
            if ($parent > 0) {
                if (isset($fields_data[$this->getId()]['new_values'])) {
                    $new_values = array_filter(explode(',', $fields_data[$this->getId()]['new_values']));
                }
                $new_values[]                              = $parent;
                $fields_data[$this->getId()]['new_values'] = implode(',', $new_values);
            }
        }

        EventManager::instance()->processEvent(
            self::AFTER_AUGMENT_DATA_FROM_REQUEST,
            array(
                'fields_data' => &$fields_data,
                'field'       => $this
            )
        );
    }

    private function addNewValuesInNaturesArray(&$fields_data)
    {
        if (! isset($fields_data[$this->getId()]['new_values'])) {
            return;
        }

        $new_values = $fields_data[$this->getId()]['new_values'];

        if (! isset($fields_data[$this->getId()]['nature'])) {
            $fields_data[$this->getId()]['nature'] = self::NO_NATURE;
        }

        if (trim($new_values) != '') {
            $art_id_array = explode(',', $new_values);
            foreach ($art_id_array as $artifact_id) {
                $artifact_id = trim($artifact_id);
                if (! isset($fields_data[$this->getId()]['natures'][$artifact_id])) {
                    $fields_data[$this->getId()]['natures'][$artifact_id] = $fields_data[$this->getId()]['nature'];
                }
            }
        }
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitArtifactLink($this);
    }

    /**
     * @return NaturePresenterFactory
     */
    protected function getNaturePresenterFactory()
    {
        return new NaturePresenterFactory(new NatureDao(), new ArtifactLinksUsageDao());
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
    }

    private function appendNatureTable(Codendi_Request $request, array &$result)
    {
        if (! $this->getTracker()->isProjectAllowedToUseNature()) {
            return;
        }

        $nature_shortname = $request->get('nature');
        if (! $nature_shortname) {
            return;
        }

        $nature_presenter      = $this->getNaturePresenterFactory()->getFromShortname($nature_shortname);
        $key                   = "nature_$nature_shortname";
        $art_factory           = $this->getArtifactFactory();
        $artifact_html_classes = 'additional';
        $nature_html           = '';
        $head_html             = '';
        $ids                   = $request->get('ids');

        foreach (explode(',', $ids) as $id) {
            $artifact = $art_factory->getArtifactById(trim($id));

            if (!is_null($artifact) && $artifact->getTracker()->isActive()) {
                $nature_html .= $this->getTemplateRenderer()->renderToString(
                    'artifactlink-nature-table-row',
                    new ArtifactInNatureTablePresenter($artifact, $artifact_html_classes, $this)
                );
            }
        }

        if ($nature_html !== '') {
            $head_html = $this->getTemplateRenderer()->renderToString(
                'artifactlink-nature-table-head',
                NatureTablePresenter::buildForHeader($nature_presenter, $this)
            );

            $result[$key] = array('head' => $head_html, 'rows' => $nature_html);
        } else {
            $result[$key] = array();
        }
    }

    /**
     * @return ArtifactLinkValidator
     */
    private function getArtifactLinkValidator()
    {
        return new ArtifactLinkValidator(
            $this->getArtifactFactory(),
            $this->getNaturePresenterFactory(),
            new ArtifactLinksUsageDao()
        );
    }

    private function getFieldDataBuilder()
    {
        return new FieldDataBuilder();
    }
}
