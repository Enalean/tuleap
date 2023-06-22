<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\DB\DBFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Option\Option;
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\CachingTrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentInformationRetriever;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupEnabledDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Masschange\MasschangeUpdater;
use Tuleap\Tracker\Report\AdditionalCriteria\CommentCriterionPresenter;
use Tuleap\Tracker\Report\AdditionalCriteria\CommentCriterionValueRetriever;
use Tuleap\Tracker\Report\AdditionalCriteria\CommentCriterionValueSaver;
use Tuleap\Tracker\Report\AdditionalCriteria\CommentDao;
use Tuleap\Tracker\Report\Event\TrackerReportDeleted;
use Tuleap\Tracker\Report\Event\TrackerReportProcessAdditionalQuery;
use Tuleap\Tracker\Report\Event\TrackerReportSetToPrivate;
use Tuleap\Tracker\Report\ExpertModePresenter;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Parser;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError;
use Tuleap\Tracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchableCollectorVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Report\Query\Advanced\SizeValidatorVisitor;
use Tuleap\Tracker\Report\Query\CommentFromWhereBuilder;
use Tuleap\Tracker\Report\Query\CommentFromWhereBuilderFactory;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\ParametrizedAndFromWhere;
use Tuleap\Tracker\Report\Query\QueryDao;
use Tuleap\Tracker\Report\TrackerCreationSuccess\SuccessPresenter;
use Tuleap\Tracker\Report\TrackerReportConfig;
use Tuleap\Tracker\Report\TrackerReportConfigDao;

/**
 * Set of criteria + set of Renderer to search and display artifacts
 */
class Tracker_Report implements Tracker_Dispatchable_Interface
{
    /**
     * Get the various criteria that may enhance a report
     *
     * Parameters:
     *  'array_of_html_criteria' string[]                (OUT) html code to be included in the criteria list
     *  'tracker'                Tracker                 (IN)  the current tracker
     *  'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
     *  'user'                   PFUser                  (IN)  the current user
     */
    public final const TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA = 'tracker_event_report_display_additional_criteria';

    /**
     * We want to save in database additional criteria
     *
     * Parameters:
     * 'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
     * 'report'                 Tracker_Report                       (IN)
     */
    public final const TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA = 'tracker_event_report_save_additional_criteria';

    /**
     * We want to save in database additional criteria
     *
     * Parameters:
     * 'additional_criteria_values'    array($key => $value) (OUT)
     * 'report'                        Tracker_Report        (IN)
     */
    public final const TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA = 'tracker_event_report_load_additional_criteria';

    public const ACTION_SAVE            = 'report-save';
    public const ACTION_SAVEAS          = 'report-saveas';
    public const ACTION_REPLACE         = 'report-replace';
    public const ACTION_DELETE          = 'report-delete';
    public const ACTION_SCOPE           = 'report-scope';
    public const ACTION_DEFAULT         = 'report-default';
    public const ACTION_CLEANSESSION    = 'clean-session';
    public const TYPE_CRITERIA          = 'criteria';
    public const TYPE_TABLE             = 'table';
    public const COMMENT_CRITERION_NAME = 'comment';
    public const XML_ID_PREFIX          = 'REPORT_';

    /**
     * @var int|string
     */
    public $id;
    public $name;
    public $description;
    public $current_renderer_id;
    public ?int $parent_report_id;
    public $user_id;
    public $group_id;
    /**
     * @readonly
     * @psalm-allow-private-mutation
     */
    public bool $is_default;
    public $tracker_id;
    public $is_query_displayed;
    public bool $is_in_expert_mode;
    public string $expert_query;
    public $updated_by;
    public $updated_at;

    public $renderers;
    public array $criteria;
    public $criterias;
    public $report_session;
    /**
     * @var ParserCacheProxy
     */
    private $parser;
    /**
     * @var InvalidTermCollectorVisitor
     */
    private $collector;

    private ?IProvideParametrizedFromAndWhereSQLFragments $additional_from_where;

    private string $expert_query_from_db;

    /**
     * Constructor
     * @param int|string     $id The id of the report
     * @param string  $name The name of the report
     * @param string  $description The description of the report
     * @param int     $current_renderer_id The current Renderer id to display
     * @param int|null $user_id The owner of the report (null if scope = project)
     * @param bool    $is_default true if the report is the default one
     * @param int     $tracker_id The id of the tracker to which this Tracker_Report is associated.
     */
    public function __construct(
        $id,
        $name,
        $description,
        $current_renderer_id,
        ?int $parent_report_id,
        $user_id,
        $is_default,
        $tracker_id,
        $is_query_displayed,
        bool $is_in_expert_mode,
        string $expert_query,
        $updated_by,
        $updated_at,
    ) {
        $this->id                   = $id;
        $this->name                 = $name;
        $this->description          = $description;
        $this->current_renderer_id  = $current_renderer_id;
        $this->parent_report_id     = $parent_report_id;
        $this->user_id              = $user_id;
        $this->is_default           = $is_default;
        $this->tracker_id           = $tracker_id;
        $this->is_query_displayed   = $is_query_displayed;
        $this->is_in_expert_mode    = $is_in_expert_mode;
        $this->expert_query         = $expert_query;
        $this->expert_query_from_db = $expert_query;
        $this->updated_by           = $updated_by;
        $this->updated_at           = $updated_at;

        $this->parser = new ParserCacheProxy(new Parser());
    }

    public function setProjectId($id)
    {
        $this->group_id = $id;
    }

    protected function getProjectId()
    {
        if (! $this->group_id) {
            $this->group_id = $this->tracker->getGroupId();
        }
        return $this->group_id;
    }

    public function registerInSession()
    {
        $this->report_session = new Tracker_Report_Session($this->id);
    }

    public function getReportSession()
    {
        return $this->report_session;
    }

    public function setIsInExpertMode(bool $is_in_expert_mode): void
    {
        $this->is_in_expert_mode = $is_in_expert_mode;
    }

    public function setExpertQuery(string $expert_query): void
    {
        $this->expert_query = $expert_query;
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_CriteriaDao();
    }

    private function getCriteriaValueForFormElement(Tracker_FormElement $form_element, $raw_value)
    {
        $form_element_factory = $this->getFormElementFactory();
        $zero_float_pattern   = '/^0{1,}(\.0*)?$/';

        if ($form_element_factory->getType($form_element) === 'int' && $raw_value === '0') {
            return 0;
        } elseif ($form_element_factory->getType($form_element) === 'float' && preg_match($zero_float_pattern, $raw_value)) {
            return 0;
        } else {
            return ! empty($raw_value) ? $raw_value : '';
        }
    }

    /** @return Tracker_Report_Criteria[] */
    public function getCriteria(): array
    {
        $session_criteria = null;
        if (isset($this->report_session)) {
            $session_criteria = &$this->report_session->getCriteria();
        }

        $this->criteria = [];
        $ff             = $this->getFormElementFactory();
        //there is previously stored
        if ($session_criteria) {
            $rank = 0;
            foreach ($session_criteria as $key => $value) {
                if ($value['is_removed'] == 0) {
                    $is_advanced = isset($value['is_advanced']) ? $value['is_advanced'] : 0;
                    $formElement = $ff->getUsedFormElementById($key);
                    if ($formElement !== null) {
                        if ($formElement->userCanRead()) {
                            assert($formElement instanceof Tracker_FormElement_Field);
                            $criteria_value = $this->getCriteriaValueForFormElement($formElement, $value['value']);

                            $formElement->setCriteriaValue($criteria_value, $this->id);
                            $this->criteria[$key] = new Tracker_Report_Criteria(
                                0,
                                $this,
                                $formElement,
                                $rank,
                                $is_advanced
                            );
                            $rank++;
                        }
                    }
                }
            }
        } else {
            //retrieve data from database
            foreach ($this->getCriteriaDao()->searchByReportId($this->id) as $row) {
                if ($formElement = $ff->getFormElementFieldById($row['field_id'])) {
                    if ($formElement->userCanRead()) {
                        $this->criteria[$row['field_id']] = new Tracker_Report_Criteria(
                            $row['id'],
                            $this,
                            $formElement,
                            $row['rank'],
                            $row['is_advanced']
                        );
                        $criterion_value                  = $formElement->getCriteriaValue($this->criteria[$row['field_id']]);
                        $criterion_opts['is_advanced']    = $row['is_advanced'];
                        if (isset($this->report_session)) {
                            $this->report_session->storeCriterion($row['field_id'], $criterion_value, $criterion_opts);
                        }
                    }
                }
            }
        }
        return $this->criteria;
    }

    public function getCriteriaFromDb()
    {
        $this->criteria = [];
        $ff             = $this->getFormElementFactory();
        //retrieve data from database
        foreach ($this->getCriteriaDao()->searchByReportId($this->id) as $row) {
            if ($formElement = $ff->getFormElementById($row['field_id'])) {
                if ($formElement->userCanRead()) {
                    $this->criteria[$row['field_id']] = new Tracker_Report_Criteria(
                        $row['id'],
                        $this,
                        $formElement,
                        $row['rank'],
                        $row['is_advanced']
                    );
                }
            }
        }
        return $this->criteria;
    }

    public function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    /**
     * Sets or adds a criterion to the global report search criteria list
     * @param int $field_id criterion id to be added or set
     * @return Tracker_Report_Criteria
     * @TODO refactor : must be renamed after addCriterion, and return the current criterion
     */
    protected function setCriteria($field_id)
    {
        $ff                        = $this->getFormElementFactory();
        $formElement               = $ff->getFormElementById($field_id);
        $this->criteria[$field_id] = new Tracker_Report_Criteria(
            0,
            $this,
            $formElement,
            0,
            0
        );
        return $this->criteria[$field_id];
    }

    protected $current_user;
    protected function getCurrentUser()
    {
        if (! $this->current_user) {
            $this->current_user = UserManager::instance()->getCurrentUser();
        }
        return $this->current_user;
    }

    protected $permissions_manager;
    private function getPermissionsManager()
    {
        if (! $this->permissions_manager) {
            $this->permissions_manager = PermissionsManager::instance();
        }
        return $this->permissions_manager;
    }

    protected $matching_ids;
    public function getMatchingIds($request = null, $use_data_from_db = false)
    {
        if ($this->is_in_expert_mode) {
            return $this->getMatchingIdsFromExpertQuery($use_data_from_db);
        } else {
            return $this->getMatchingIdsFromCriteria($request, $use_data_from_db);
        }
    }

    /**
     * Given the output of getMatchingIds() which returns an array containing 'artifacts ids' and  'Last changeset ids'
     * as two strings with comma separated values, this method would format such output to an array with artifactIds in keys
     * and last changeset ids in values.
     * @see Tracker_Report::getMatchingIds() for usage
     *
     * @return Array
     */
    private function getLastChangesetIdByArtifactId($matching_ids)
    {
        $artifact_ids       = explode(',', $matching_ids['id']);
        $last_changeset_ids = explode(',', $matching_ids['last_changeset_id']);

        $formatted_matching_ids = [];
        foreach ($artifact_ids as $key => $artifactId) {
            $formatted_matching_ids[$artifactId] = $last_changeset_ids[$key];
        }
        return $formatted_matching_ids;
    }

    /**
     * This method is the opposite of getLastChangesetIdByArtifactId().
     * Given an array with artifactIds in keys and lastChangesetIds on values it would return and array with two elements of type string
     * the first contains comma separated "artifactIds" and the second contains comma separated "lastChangesetIds".
     * @see Tracker_Report::getMatchingIds() for usage
     *
     * @param Array $formattedMatchingIds Matching Id's that will get converted in that format
     *
     * @return Array
     */
    private function implodeMatchingIds($formattedMatchingIds)
    {
        $matchingIds['id']                = '';
        $matchingIds['last_changeset_id'] = '';
        foreach ($formattedMatchingIds as $artifactId => $lastChangesetId) {
            $matchingIds['id']                .= $artifactId . ',';
            $matchingIds['last_changeset_id'] .= $lastChangesetId . ',';
        }
        if (substr($matchingIds['id'], -1) === ',') {
            $matchingIds['id'] = substr($matchingIds['id'], 0, -1);
        }
        if (substr($matchingIds['last_changeset_id'], -1) === ',') {
            $matchingIds['last_changeset_id'] = substr($matchingIds['last_changeset_id'], 0, -1);
        }
        return $matchingIds;
    }

    /**
     * @param Tracker_Report_Criteria[] $criteria
     * @param Tracker_Report_AdditionalCriterion[] $additional_criteria
     *
     * @return string[]
     */
    private function getMatchingIdsFromCriteriaInDb(array $criteria, array $additional_criteria)
    {
        $from_where = Option::nothing(IProvideParametrizedFromAndWhereSQLFragments::class);
        foreach ($criteria as $criterion) {
            $criterion->getFromWhere()->apply(function ($additional_from_where) use (&$from_where) {
                $from_where = $this->addFromWhere($from_where, $additional_from_where);
            });
        }

        $this->getCommentCriterionFromWhere($additional_criteria)
             ->apply(function ($comment_from_where) use (&$from_where) {
                 $from_where = $this->addFromWhere($from_where, $comment_from_where);
             });

        $matching_ids = $this->getMatchingIdsInDb($from_where);

        return $matching_ids;
    }

    /**
     * @param Option<IProvideParametrizedFromAndWhereSQLFragments> $existing
     *
     * @return Option<IProvideParametrizedFromAndWhereSQLFragments>
     */
    private function addFromWhere(Option $existing, IProvideParametrizedFromAndWhereSQLFragments $from_where): Option
    {
        $new_from_where = Option::nothing(IProvideParametrizedFromAndWhereSQLFragments::class);
        $existing->match(
            function (IProvideParametrizedFromAndWhereSQLFragments $existing) use (&$new_from_where, $from_where) {
                $new_from_where = Option::fromValue(new ParametrizedAndFromWhere($existing, $from_where));
            },
            function () use (&$new_from_where, $from_where) {
                $new_from_where = Option::fromValue($from_where);
            },
        );

        return $new_from_where;
    }

    /**
     * @param Tracker_Report_AdditionalCriterion[] $additional_criteria
     *
     * @return Option<IProvideParametrizedFromAndWhereSQLFragments>
     */
    private function getCommentCriterionFromWhere(array $additional_criteria): Option
    {
        $comment_criterion = $this->getAdditionalCommentCriterion($additional_criteria);
        if (! $comment_criterion || (string) $comment_criterion->getValue() === '') {
            return Option::nothing(IProvideParametrizedFromAndWhereSQLFragments::class);
        }

        return Option::fromValue(
            $this->getCommentFromWhereBuilder()
                 ->getFromWhereWithComment($comment_criterion->getValue(), self::COMMENT_CRITERION_NAME)
        );
    }

    /**
     * @return bool true if the report has been modified since the last checkout
     */
    public function isObsolete()
    {
        return isset($this->report_session) && $this->updated_at && ($this->report_session->get('checkout_date') < $this->updated_at);
    }

    public function hasChanged(): bool
    {
        return isset($this->report_session) && $this->report_session->hasChanged();
    }

    /**
     * @return string html the user who has modified the report. Or false if the report has not been modified
     */
    public function getLastUpdaterUserName()
    {
        if ($this->isObsolete()) {
            return UserHelper::instance()->getLinkOnUserFromUserId($this->updated_by);
        }
        return '';
    }

    protected function displayHeader(Tracker_IFetchTrackerSwitcher $layout, $request, $current_user, $report_can_be_modified)
    {
        $header_builder = new Tracker_Report_HeaderRenderer(
            Tracker_ReportFactory::instance(),
            Codendi_HTMLPurifier::instance(),
            $this->getTemplateRenderer()
        );
        $header_builder->displayHeader($layout, $request, $current_user, $this, $report_can_be_modified);
    }

    public function nbPublicReport($reports)
    {
        $i = 0;
        foreach ($reports as $report) {
            if ($report->user_id == null) {
                $i++;
            }
        }
        return $i;
    }

    public function fetchDisplayQuery(array $criteria, array $additional_criteria, $report_can_be_modified, PFUser $current_user)
    {
        $div_class = '';
        if ($this->is_in_expert_mode) {
            $div_class = 'tracker-report-query-undisplayed';
        }
        $html  = '';
        $html .= '<div id="tracker-report-normal-query" class="' . $div_class . '" data-report-id="' . $this->id . '">';
        $html .= '<form action="" method="POST" id="tracker_report_query_form" class="tracker-report-query-form">';
        $html .= '<input type="hidden" name="report" value="' . $this->id . '" />';
        $id    = 'tracker_report_query_' . $this->id;
        $html .= '<h4 class="backlog-planning-search-title ' . Toggler::getClassname($id, $this->is_query_displayed ? true : false) . '" id="' . $id . '">';

        //  Query title
        $html            .= dgettext('tuleap-tracker', 'Search') . '</h4>';
        $used             = [];
        $criteria_fetched = [];
        foreach ($criteria as $criterion) {
            if ($criterion->field->isUsed()) {
                $li = '<li id="tracker_report_crit_' . $criterion->field->getId() . '">';
                if ($current_user->isAnonymous()) {
                    $li .= $criterion->fetchWithoutExpandFunctionnality();
                } else {
                    $li .= $criterion->fetch();
                }
                $li                              .= '</li>';
                $criteria_fetched[]               = $li;
                $used[$criterion->field->getId()] = $criterion->field;
            }
        }
        if ($report_can_be_modified && ! $current_user->isAnonymous()) {
            $html .= '<div class="pull-right">';
            $html .= $this->getExpertModeButton();
            $html .= $this->getAddCriteriaDropdown($used);
            $html .= '</div>';
        }

        $array_of_html_criteria = [];
        $this->getCommentCriterionHtmlContent($additional_criteria, $array_of_html_criteria);

        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA,
            [
                'array_of_html_criteria' => &$array_of_html_criteria,
                'tracker'                => $this->getTracker(),
                'additional_criteria'    => $additional_criteria,
                'user'                   => $current_user,
            ]
        );
        foreach ($array_of_html_criteria as $additional_criteria) {
            $criteria_fetched[] = '<li>' . $additional_criteria . '</li>';
        }
        $html .= '<ul id="tracker_query">' . implode('', $criteria_fetched) . '</ul>';

        $html .= '<div align="center">';
        $html .= '<button type="submit" name="tracker_query_submit" class="btn btn-primary">';
        $html .= '<i class="fa fa-search"></i> ';
        $html .= $GLOBALS['Language']->getText('global', 'btn_search');
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    /**
     * @return Tracker_Report_AdditionalCriterion|null
     */
    private function getAdditionalCommentCriterion(array $additional_criteria)
    {
        foreach ($additional_criteria as $additional_criterion) {
            if ($additional_criterion->getKey() === self::COMMENT_CRITERION_NAME) {
                return $additional_criterion;
            }
        }

        return null;
    }

    private function getCommentCriterionHtmlContent(array $additional_criteria, array &$array_of_html_criteria)
    {
        $comment_criterion = $this->getAdditionalCommentCriterion($additional_criteria);
        if ($comment_criterion) {
            $presenter = new CommentCriterionPresenter($comment_criterion);

            $renderer = TemplateRendererFactory::build()->getRenderer(
                TRACKER_TEMPLATE_DIR . '/report/'
            );

            $array_of_html_criteria[] = $renderer->renderToString('comment-criterion', $presenter);
        }
    }

    private function fetchDisplayQueryExpertMode($report_can_be_modified, PFUser $current_user): string
    {
        $id            = 'tracker-report-expert-query-' . $this->id;
        $class_toggler = Toggler::getClassname($id, $this->is_query_displayed ? true : false);
        $fields        = $this->getFormElementFactory()->getUsedFieldsForExpertModeUserCanRead(
            $this->getTracker(),
            $this->getCurrentUser()
        );

        $is_normal_mode_button_displayed = ($report_can_be_modified && ! $current_user->isAnonymous());
        $is_query_modifiable             = ! $current_user->isAnonymous();

        $tracker_report_expert_query_presenter = new ExpertModePresenter(
            $this->id,
            $class_toggler,
            $this->is_in_expert_mode,
            $this->expert_query,
            $fields,
            $is_normal_mode_button_displayed,
            $is_query_modifiable
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(
            TRACKER_TEMPLATE_DIR . '/report/'
        );

        return $renderer->renderToString('tracker-report-expert-query', $tracker_report_expert_query_presenter);
    }

    private function getExpertModeButton()
    {
        $html  = '<button id="tracker-report-expert-query-button" type="button" class="btn btn-mini tracker-report-query-button">';
        $html .= '<i class="fas fa-random"></i> ';
        $html .= dgettext('tuleap-tracker', 'Expert mode');
        $html .= '</button>';

        return $html;
    }

    private function getAddCriteriaDropdown($used)
    {
        $add_criteria_presenter = new Templating_Presenter_ButtonDropdownsMini(
            'tracker_report_add_criteria_dropdown',
            dgettext('tuleap-tracker', 'Criteria'),
            $this->getFieldsAsDropdownOptions('tracker_report_add_criterion', $used, self::TYPE_CRITERIA)
        );
        $add_criteria_presenter->setIcon('fa fa-eye-slash');

        return $this->getTemplateRenderer()->renderToString('button_dropdowns', $add_criteria_presenter);
    }

    public function getFieldsAsDropdownOptions($id_prefix, array $used, $dropdown_type)
    {
        $fields_for_criteria = [];
        $fields_for_sort     = [];

        foreach ($this->getFormElementFactory()->getFields($this->getTracker()) as $field) {
            if ($dropdown_type === self::TYPE_CRITERIA && ! $field->canBeUsedAsReportCriterion()) {
                continue;
            }
            if ($dropdown_type === self::TYPE_TABLE && ! $field->canBeUsedAsReportColumn()) {
                continue;
            }

            if ($field->userCanRead() && $field->isUsed()) {
                $fields_for_criteria[$field->getId()] = $field;
                $fields_for_sort[$field->getId()]     = strtolower($field->getLabel());
            }
        }
        asort($fields_for_sort);

        $criteria_options          = [];
        $criteria_advanced_options = [];

        foreach ($fields_for_sort as $id => $nop) {
            $option     = new Templating_Presenter_ButtonDropdownsOption(
                $id_prefix . '_' . $id,
                $fields_for_criteria[$id]->getLabel(),
                isset($used[$id]),
                '#'
            );
            $parameters = [
                'data-field-id'      => $id,
                'data-field-is-used' => intval(isset($used[$id])),
            ];
            if ($dropdown_type !== self::TYPE_CRITERIA) {
                $parameters['data-column-id'] = $id;
            }
            $option->setLiParameters($parameters);
            $criteria_options[] = $option;

            if ($this->fieldAllowsCustomColumnForTableReport($fields_for_criteria[$id], $dropdown_type)) {
                $criteria_advanced_options[] = new Templating_Presenter_ButtonDropdownsOptionSubmenu(
                    $id_prefix . '_' . $id,
                    $fields_for_criteria[$id]->getLabel(),
                    $this->getOptionsForCustomColumn($id, $used)
                );
            }
        }

        if (! empty($criteria_advanced_options)) {
            $simple_columns_title = new Templating_Presenter_ButtonDropdownsOptionTitle(
                dgettext('tuleap-tracker', 'Simple columns')
            );
            array_unshift($criteria_options, $simple_columns_title);

            $custom_columns_title = new Templating_Presenter_ButtonDropdownsOptionTitle(
                dgettext('tuleap-tracker', 'Custom columns based on...')
            );
            array_unshift($criteria_advanced_options, $custom_columns_title);
        }

        return array_merge($criteria_options, $criteria_advanced_options);
    }

    private function getOptionsForCustomColumn($id, $used)
    {
        $project = $this->getTracker()->getProject();
        $options = [];
        $types   = $this->getTypePresenterFactory()->getAllTypesEditableInProject($project);

        $column_id = $id . '_';
        $option    = new Templating_Presenter_ButtonDropdownsOption(
            $id,
            dgettext('tuleap-tracker', 'No type'),
            isset($used[$column_id]),
            '#'
        );
        $option->setLiParameters(
            [
                'data-column-id'          => $column_id,
                'data-field-id'           => $id,
                'data-field-is-used'      => intval(isset($used[$column_id])),
                'data-field-artlink-type' => '',
            ]
        );
        $options[] = $option;

        foreach ($types as $type) {
            $column_id    = $id . '_' . $type->shortname;
            $type_is_used = isset($used[$column_id]);

            if (
                $this->getArtifactLinksUsageDao()->isTypeDisabledInProject((int) $project->getID(), $type->shortname) &&
                ! $type_is_used
            ) {
                continue;
            }

            $option = new Templating_Presenter_ButtonDropdownsOption(
                $id,
                $type->forward_label,
                $type_is_used,
                '#'
            );
            $option->setLiParameters(
                [
                    'data-column-id'          => $column_id,
                    'data-field-id'           => $id,
                    'data-field-is-used'      => intval(isset($used[$column_id])),
                    'data-field-artlink-type' => $type->shortname,
                ]
            );
            $options[] = $option;
        }

        return $options;
    }

    /**
     * @return ArtifactLinksUsageDao
     */
    private function getArtifactLinksUsageDao()
    {
        return new ArtifactLinksUsageDao();
    }

    private function fieldAllowsCustomColumnForTableReport(Tracker_FormElement_Field $field, $dropdown_type)
    {
        return $this->getArtifactLinksUsageUpdater()->isProjectAllowedToUseArtifactLinkTypes($this->getTracker()->getProject()) &&
            $dropdown_type === self::TYPE_TABLE &&
            $this->getFormElementFactory()->getType($field) === Tracker_FormElement_Field_ArtifactLink::TYPE;
    }

    /**
     * @return TypePresenterFactory
     */
    private function getTypePresenterFactory()
    {
        return new TypePresenterFactory(new TypeDao(), $this->getArtifactLinksUsageDao());
    }

    /**
     * @return ArtifactLinksUsageUpdater
     */
    private function getArtifactLinksUsageUpdater()
    {
        return new ArtifactLinksUsageUpdater($this->getArtifactLinksUsageDao());
    }

    public function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(
            [
                TRACKER_TEMPLATE_DIR . '/report',
                ForgeConfig::get('codendi_dir') . '/src/templates/common',
            ]
        );
    }

    public function display(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $link_artifact_id       = (int) $request->get('link-artifact-id');
        $report_can_be_modified = ! $link_artifact_id;

        $hp                      = Codendi_HTMLPurifier::instance();
        $current_user            = UserManager::instance()->getCurrentUser();
        $renderer_preference_key = 'tracker_' . $this->tracker_id . '_report_' . $this->id . '_last_renderer';
        $use_data_from_db        = false;

        if ($link_artifact_id) {
            //Store in user preferences
            if ($current_user->getPreference('tracker_' . $this->tracker_id . '_last_report') != $this->id) {
                $current_user->setPreference('tracker_' . $this->tracker_id . '_last_report', (string) $this->id);
            }
        }

        $renderers        = $this->getRenderers();
        $current_renderer = null;
        //search for the current renderer
        if (is_array($request->get('renderer'))) {
            $renderer_id = key($request->get('renderer'));
            if (isset($renderers[$renderer_id])) {
                $current_renderer = $renderers[$renderer_id];
            }
        }
        if (! $current_renderer) {
            foreach ($renderers as $r) {
                if (
                    ! $current_renderer || ($request->get('renderer') == $r->id)
                                       || (! $request->get('renderer') && $r->id == $this->current_renderer_id)
                                       || (! $request->get('renderer') && $r->id == $current_user->getPreference($renderer_preference_key))
                ) {
                    $current_renderer = $r;
                }
            }
        }
        if (! $current_renderer) {
            $current_renderer = current($renderers);
        }
        if ($current_renderer && $current_user->getPreference($renderer_preference_key) != $current_renderer->id) {
            $current_user->setPreference($renderer_preference_key, $current_renderer->id);
        }

        // We need an ArtifactLinkable renderer for ArtifactLink
        if ($link_artifact_id && ! $current_renderer instanceof \Tracker_Report_Renderer_ArtifactLinkable) {
            foreach ($renderers as $r) {
                if ($r instanceof \Tracker_Report_Renderer_ArtifactLinkable) {
                    $current_renderer = $r;
                    break;
                }
            }
        }
        if ($request->get('only-renderer')) {
            echo $current_renderer->fetch($this->getMatchingIds($request, $use_data_from_db), $request, $report_can_be_modified, $current_user);
        } else {
            $html = '';

            //Display Criteria
            $registered_criteria = [];
            $this->getCriteria();
            $session_criteria = $this->report_session->getCriteria();
            if ($session_criteria) {
                foreach ($session_criteria as $key => $session_criterion) {
                    if (! empty($session_criterion['is_removed'])) {
                        continue;
                    }
                    if (! empty($this->criteria[$key])) {
                        $registered_criteria[] = $this->criteria[$key];
                    }
                }
            }
            $additional_criteria = $this->getAdditionalCriteria($use_data_from_db);

            $html .= '<div class="tracker-report-query">';
            $html .= $this->fetchDisplayQuery($registered_criteria, $additional_criteria, $report_can_be_modified, $current_user);
            $html .= $this->fetchDisplayQueryExpertMode($report_can_be_modified, $current_user);
            $html .= '</div>';

            //Display Renderers
            $html .= '<div>';
            $html .= '<ul id="tracker_report_renderers" class="nav nav-tabs">';

            foreach ($renderers as $r) {
                $active = $r->id == $current_renderer->id ? 'tracker_report_renderers-current active dropdown' : '';
                if ($active || ! $link_artifact_id || $r instanceof \Tracker_Report_Renderer_ArtifactLinkable) {
                    $parameters = [
                        'report'   => $this->id,
                        'renderer' => $r->id,
                    ];
                    if ($request->existAndNonEmpty('pv')) {
                        $parameters['pv'] = (int) $request->get('pv');
                    }
                    if ($link_artifact_id) {
                        $parameters['link-artifact-id'] = (int) $link_artifact_id;
                        $parameters['only-renderer']    = 1;
                    }

                    $url   = $active ? '#' : '?' . http_build_query($parameters);
                    $html .= '<li id="tracker_report_renderer_' . $r->id . '"
                                  class="' . $active . '
                                            tracker_report_renderer_tab
                                            tracker_report_renderer_tab_' . $r->getType() . '">
                              <a href="' . $url . '" title="' .  $hp->purify($r->description, CODENDI_PURIFIER_CONVERT_HTML)  . '" ' . ($active ? 'class="dropdown-toggle" data-toggle="dropdown"' : '') . '>';
                    $html .= '<input type="hidden" name="tracker_report_renderer_rank" value="' . (int) $r->rank . '" />';
                    $html .= '<i class="' . $r->getIcon() . '"></i>';
                    $html .= ' ' . $hp->purify($r->name, CODENDI_PURIFIER_CONVERT_HTML);
                    if ($active) {
                        //Check that user can update the renderer
                        if ($report_can_be_modified && ! $current_user->isAnonymous()) {
                            $html .= ' <b class="caret" id="tracker_renderer_updater_handle"></b>';
                        }
                    }
                    $html .= '</a>';
                    if ($report_can_be_modified && ! $current_user->isAnonymous()) {
                        $html .= '<div class="dropdown-menu">' . $this->fetchUpdateRendererForm($r) . '</div>';
                    }
                    $html .= '</li>';
                }
            }

            if ($report_can_be_modified && ! $current_user->isAnonymous()) {
                $html .= '<li class="tracker_report_renderers-add dropdown">
                    <a id="tracker_renderer_add_handle"
                       href="#"
                       class="dropdown-toggle"
                       data-toggle="dropdown">';
                $html .=  '<i class="fa fa-plus"></i>';
                $html .= '</a>';
                $html .= '<div class="dropdown-menu">' . $this->fetchAddRendererForm($current_renderer) . '</div>';
                $html .= '</li>';
            }

            $html .= '</ul>';

            if ($current_renderer) {
                $html .= '<div class="tracker_report_renderer"
                               id="tracker_report_renderer_current"
                               data-renderer-id="' . $hp->purify($current_renderer->getId()) . '"
                               data-report-id="' . $hp->purify($this->id) . '"
                               data-renderer-func="renderer"
                          >';

                if ($current_renderer->description) {
                    $html .= '<p class="tracker_report_renderer_description">';
                    $html .= '<span>' . dgettext('tuleap-tracker', 'Description:') . ' </span>';
                    $html .= $hp->purify($current_renderer->description, CODENDI_PURIFIER_BASIC);
                    $html .= '</p>';
                }

                //  Options menu
                if ($report_can_be_modified && ($options = $current_renderer->getOptionsMenuItems($current_user))) {
                    $html .= '<div id="tracker_renderer_options">';
                    $html .= implode(' ', $options);
                    $html .= '</div>';
                }

                //Warning about Full text in Tracker Report...
                $fts_warning = '';
                $params      = ['html' => &$fts_warning, 'request' => $request, 'group_id' => $this->getProjectId()];
                EventManager::instance()->processEvent('tracker_report_followup_warning', $params);
                $html .= $fts_warning;

                try {
                    $html .= $current_renderer->fetch($this->getMatchingIds($request, false), $request, $report_can_be_modified, $current_user);
                } catch (\Tuleap\Tracker\Report\dao\TooManyMatchingArtifactsException $exception) {
                    BackendLogger::getDefaultLogger()->error('Report failure', ['exception' => $exception]);
                    $html .= '<br><div class="alert alert-error">' . $exception->getMessage() . '</div>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';

            if ($request->get("should-display-created-tracker-modal")) {
                $javascript_assets = new IncludeAssets(
                    __DIR__ . '/../../../frontend-assets',
                    '/assets/trackers'
                );

                $GLOBALS['Response']->includeFooterJavascriptFile($javascript_assets->getFileURL('tracker-creation-success.js'));

                $renderer = TemplateRendererFactory::build()->getRenderer(
                    TRACKER_TEMPLATE_DIR  . '/tracker-creation/'
                );

                $html .= $renderer->renderToString(
                    'tracker-creation-success',
                    new SuccessPresenter($this->getTracker())
                );
            }

            $this->displayHeader($layout, $request, $current_user, $report_can_be_modified);

            echo $html;

            if ($report_can_be_modified) {
                $this->getTracker()->displayFooter($layout);
                exit();
            }
        }
    }

    public function getRenderers()
    {
        return Tracker_Report_RendererFactory::instance()->getReportRenderersByReport($this);
    }

    /**
     * @return SizeValidatorVisitor
     */
    private function getSizeValidator()
    {
        $report_config = new TrackerReportConfig(
            new TrackerReportConfigDao()
        );

        return new SizeValidatorVisitor($report_config->getExpertQueryLimit());
    }

    protected function orderRenderersByRank($renderers)
    {
        $array_rank = [];
        foreach ($renderers as $field_id => $properties) {
            $array_rank[$field_id] = $properties->rank;
        }
        asort($array_rank);
        $renderers_sort = [];
        foreach ($array_rank as $id => $rank) {
            $renderers_sort[$id] = $renderers[$id];
        }
        return $renderers_sort;
    }

    protected function getRendererFactory()
    {
        return Tracker_Report_RendererFactory::instance();
    }

    protected function _fetchAddCriteria($used)
    {
        $html = '';

        $options = '';
        foreach ($this->getTracker()->getFormElements() as $formElement) {
            if ($formElement->userCanRead()) {
                $options .= $formElement->fetchAddCriteria($used);
            }
        }
        if ($options) {
            $html .= '<select name="add_criteria" id="tracker_report_add_criteria" autocomplete="off">';
            $html .= '<option selected="selected" value="">' . '-- ' . dgettext('tuleap-tracker', 'Criteria') . '</option>';
            $html .= $options;
            $html .= '</select>';
        }
        return $html;
    }

    /**
     * Say if the report is public
     * @psalm-mutation-free
     */
    public function isPublic(): bool
    {
        return empty($this->user_id);
    }

    /**
     * Only owners of a report can update it.
     * owner = report->user_id
     * or if null, owner = tracker admin or site admins
     * @param PFUser $user the user who wants to update the report
     * @return bool
     */
    public function userCanUpdate($user)
    {
        if (! $this->isBelongingToATracker()) {
            return false;
        }

        if ($this->user_id) {
            return $this->user_id == $user->getId();
        } else {
            $tracker = $this->getTracker();
            return $user->isSuperUser() || $tracker->userIsAdmin($user);
        }
    }

    private function isBelongingToATracker()
    {
        return $this->getTracker() != null;
    }

    protected $tracker;
    public function getTracker(): Tracker
    {
        if (! $this->tracker) {
            $this->tracker = TrackerFactory::instance()->getTrackerById($this->tracker_id);
        }
        if ($this->tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        return $this->tracker;
    }

    public function setTracker(Tracker $tracker)
    {
        $this->tracker    = $tracker;
        $this->tracker_id = $tracker->getId();
    }

    /**
     * hide or show the criteria
     */
    public function toggleQueryDisplay()
    {
        $this->is_query_displayed = ! $this->is_query_displayed;
        return $this;
    }

    /**
     * Remove a formElement from criteria
     * @param int $formElement_id the formElement used for the criteria
     */
    public function removeCriteria($formElement_id)
    {
        $criteria = $this->getCriteria();
        if (isset($criteria[$formElement_id])) {
            if ($this->getCriteriaDao()->delete($this->id, $formElement_id)) {
                $criteria[$formElement_id]->delete();
                unset($criteria[$formElement_id]);
            }
        }
        return $this;
    }

    public function addCriteria(Tracker_Report_Criteria $criteria): int
    {
        $id = $this->getCriteriaDao()->create($this->id, $criteria->field->id, $criteria->is_advanced);
        return $id;
    }

    public function deleteAllCriteria()
    {
        $this->getCriteriaDao()->deleteAll($this->id);
    }

    /**
     * Toggle the state 'is_advanced' of a criteria
     * @param int $formElement_id the formElement used for the criteria
     */
    public function toggleAdvancedCriterion($formElement_id)
    {
        $advanced          = 1;
        $session_criterion = $this->report_session->getCriterion($formElement_id);
        if (! empty($session_criterion['is_advanced'])) {
            $advanced = 0;
        }
        $this->report_session->updateCriterion($formElement_id, '', ['is_advanced' => $advanced]);
        return $this;
    }

    /**
     * Store the criteria value
     * NOTICE : if a criterion does not exist it is not created
     * @param array $criteria_values
     */
    public function updateCriteriaValues($criteria_values)
    {
        $ff = $this->getFormElementFactory();
        foreach ($criteria_values as $formElement_id => $new_value) {
            $session_criterion = $this->report_session->getCriterion($formElement_id);
            if ($session_criterion) {
                if ($field = $ff->getFormElementById($formElement_id)) {
                    $this->report_session->storeCriterion($formElement_id, $field->getFormattedCriteriaValue($new_value));
                }
            }
        }
    }

    public function updateAdditionalCriteriaValues($additional_criteria_values)
    {
        foreach ($additional_criteria_values as $key => $new_value) {
            $additional_criterion = new Tracker_Report_AdditionalCriterion($key, $new_value);
            $this->report_session->storeAdditionalCriterion($additional_criterion);
        }
    }

    /**
     * Process the request for the specified renderer
     * @param int $renderer_id
     * @param Request $request
     * @return ReportRenderer
     */
    public function processRendererRequest($renderer_id, Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $store_in_session = true)
    {
        $rrf = Tracker_Report_RendererFactory::instance();
        if ($renderer = $rrf->getReportRendererByReportAndId($this, $renderer_id, $store_in_session)) {
            $renderer->process($layout, $request, $current_user);
        }
    }

    /**
     * Delete a renderer from the report
     * @param mixed the renderer to remove (Tracker_Report_Renderer or the id as int)
     */
    public function deleteRenderer($renderer)
    {
        $rrf = Tracker_Report_RendererFactory::instance();
        if (! is_a($renderer, 'Tracker_Report_Renderer')) {
            $renderer_id = (int) $renderer;
            $renderer    = $rrf->getReportRendererByReportAndId($this, $renderer_id);
        }
        if ($renderer) {
            $renderer_id = $renderer->id;
            $renderer->delete();
            $rrf->delete($renderer_id);
        }
        return $this;
    }

    /**
     * Move a renderer at a specific position
     *
     * @param mixed $renderer the renderer to remove (Tracker_Report_Renderer or the id as int)
     * @param int   $position the new position
     */
    public function moveRenderer($renderer, $position)
    {
        $rrf = Tracker_Report_RendererFactory::instance();
        if (! $renderer instanceof \Tracker_Report_Renderer) {
            $renderer_id = (int) $renderer;
            $renderer    = $rrf->getReportRendererByReportAndId($this, $renderer_id);
        }
        if ($renderer) {
            $rrf->move($renderer->id, $this, $position);
        }
        return $this;
    }

    /**
     * Add a new renderer to the report
     *
     * @param string $name
     * @param string $description
     *
     * @return int the id of the new renderer
     */
    public function addRenderer($name, $description, $type)
    {
        $rrf = Tracker_Report_RendererFactory::instance();
        return $rrf->create($this, $name, $description, $type);
    }

    public function addRendererInSession($name, $description, $type)
    {
        $rrf = Tracker_Report_RendererFactory::instance();
        return $rrf->createInSession($this, $name, $description, $type);
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        if ($this->isObsolete()) {
            /**
             * @psalm-taint-escape header
             */
            $is_obsolete_header = 'X-Codendi-Tracker-Report-IsObsolete: ' . $this->getLastUpdaterUserName();
            header($is_obsolete_header);
        }
        $hp      = Codendi_HTMLPurifier::instance();
        $tracker = $this->getTracker();

        if ($request->exist('tracker') && $request->get('tracker') != $tracker->getId()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The request is not valid.')
            );

            $GLOBALS['Response']->redirect('?' . http_build_query([
                'tracker'   => $tracker->getId(),
            ]));
        }

        switch ($request->get('func')) {
            case 'display-masschange-form':
                if ($tracker->userIsAdmin($current_user)) {
                    $masschange_aids = [];
                    $renderer_table  =  $request->get('renderer_table');

                    if (! empty($renderer_table['masschange_checked'])) {
                        $masschange_aids = $request->get('masschange_aids');
                    } elseif (! empty($renderer_table['masschange_all'])) {
                        $masschange_aids_all = $this->getMatchingIds($request);
                        if ($masschange_aids_all) {
                            $masschange_aids = explode(',', $masschange_aids_all['id']);
                        }
                    }

                    if (empty($masschange_aids)) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'No artifacts have been selected'));
                        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $tracker->getId());
                    }
                    $tracker->displayMasschangeForm($layout, $current_user, $masschange_aids);
                } else {
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $tracker->getId());
                }
                break;
            case 'update-masschange-aids':
                $form_element_factory = $this->getFormElementFactory();
                $masschange_updater   = new MasschangeUpdater(
                    $tracker,
                    $this,
                    new Tracker_MasschangeDataValueExtractor($form_element_factory),
                    new Tracker_RuleFactory(new Tracker_RuleDao()),
                    $form_element_factory,
                    Tracker_ArtifactFactory::instance(),
                    new Tracker_ArtifactDao(),
                    EventManager::instance()
                );
                $masschange_updater->updateArtifacts($current_user, $request);
                break;
            case 'remove-criteria':
                if ($request->get('field') && ! $current_user->isAnonymous()) {
                    $this->report_session->removeCriterion($request->get('field'));
                    $this->report_session->setHasChanged();
                }
                break;
            case 'add-criteria':
                if ($request->get('field') && ! $current_user->isAnonymous()) {
                    //TODO: make sure that the formElement exists and the user can read it
                    if ($request->isAjax()) {
                        $criteria = $this->getCriteria();
                        $field_id = $request->get('field');
                        $this->setCriteria($field_id);
                        $this->report_session->storeCriterion($field_id, '', ['is_advanced' => 0, 'is_removed' => 0]);
                        $this->report_session->setHasChanged();
                        echo $this->criteria[$field_id]->fetch();
                    }
                }
                break;
            case 'toggle-advanced':
                if ($request->get('field') && ! $current_user->isAnonymous()) {
                    $this->toggleAdvancedCriterion($request->get('field'));
                    $this->report_session->setHasChanged();
                    if ($request->isAjax()) {
                        $criteria = $this->getCriteria();
                        if (isset($criteria[$request->get('field')])) {
                            echo $criteria[$request->get('field')]->fetch();
                        }
                    }
                }
                break;
            case self::ACTION_CLEANSESSION:
                $this->report_session->clean();
                $GLOBALS['Response']->redirect('?' . http_build_query([
                    'tracker'   => $this->tracker_id,
                ]));
                break;
            case 'renderer':
                if ($request->get('renderer')) {
                    $store_in_session = true;
                    if ($request->exist('store_in_session')) {
                        $store_in_session = (bool) $request->get('store_in_session');
                    }
                    $this->processRendererRequest($request->get('renderer'), $layout, $request, $current_user, $store_in_session);
                }
                break;
            case 'rename-renderer':
                if ($request->get('new_name') == '') {
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Renderer name is mandatory.'));
                } elseif (! $current_user->isAnonymous() && (int) $request->get('renderer') && trim($request->get('new_name'))) {
                    $this->report_session->renameRenderer((int) $request->get('renderer'), trim($request->get('new_name')), trim($request->get('new_description')));
                    $this->report_session->setHasChanged();
                }
                $GLOBALS['Response']->redirect('?' . http_build_query([
                    'report'   => $this->id,
                ]));
                break;
            case 'delete-renderer':
                if (! $current_user->isAnonymous() && (int) $request->get('renderer')) {
                    $this->report_session->removeRenderer((int) $request->get('renderer'));
                    $this->report_session->setHasChanged();
                    $GLOBALS['Response']->redirect('?' . http_build_query([
                        'report'   => $this->id,
                    ]));
                }
                break;
            case 'move-renderer':
                if (! $current_user->isAnonymous() && (int) $request->get('renderer')) {
                    if ($request->isAjax()) {
                        $this->report_session->moveRenderer($request->get('tracker_report_renderers'));
                        $this->report_session->setHasChanged();
                    } else {
                        if ($request->get('move-renderer-direction')) {
                            $this->moveRenderer((int) $request->get('renderer'), $request->get('move-renderer-direction'));
                            $GLOBALS['Response']->redirect('?' . http_build_query([
                                'report'   => $this->id,
                            ]));
                        }
                    }
                }
                break;
            case 'add-renderer':
                $new_name        = trim($request->get('new_name'));
                $new_description = trim($request->get('new_description'));
                $new_type        = trim($request->get('new_type'));
                if (! $current_user->isAnonymous() && $new_name) {
                    $new_renderer_id = $this->addRendererInSession($new_name, $new_description, $new_type);
                    $GLOBALS['Response']->redirect('?' . http_build_query([
                        'report'   => $this->id,
                        'renderer' => $new_renderer_id ? $new_renderer_id : '',
                    ]));
                }
                break;
            case self::ACTION_SAVE:
                Tracker_ReportFactory::instance()->save($this);
                $this->saveCriteria();
                $this->saveAdditionalCriteria();
                $this->saveRenderers();
                //Clean session
                $this->report_session->cleanNamespace();

                $GLOBALS['Response']->addFeedback('info', '<a href="?report=' . $this->id . '">' . $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) . '</a> has been saved.', CODENDI_PURIFIER_DISABLED);
                $GLOBALS['Response']->redirect('?' . http_build_query([
                    'report'   => $this->id,
                ]));
                break;
            case self::ACTION_SAVEAS:
                $redirect_to_report_id = $this->id;
                $report_copy_name      = trim($request->get('report_copy_name'));
                if ($report_copy_name) {
                    $new_report                    = Tracker_ReportFactory::instance()->duplicateReportSkeleton($this, $this->tracker_id, $current_user->getId());
                    $new_report->name              = $report_copy_name;
                    $new_report->user_id           = $current_user->getId();
                    $new_report->is_in_expert_mode = $this->is_in_expert_mode;
                    $new_report->expert_query      = $this->expert_query;
                    Tracker_ReportFactory::instance()->save($new_report);
                    $GLOBALS['Response']->addFeedback('info', '<a href="?report=' . $new_report->id . '">' . $hp->purify($new_report->name, CODENDI_PURIFIER_CONVERT_HTML) . '</a> has been created.', CODENDI_PURIFIER_DISABLED);
                    $redirect_to_report_id = $new_report->id;
                    //copy parent tracker session content
                    $this->report_session->copy($this->id, $redirect_to_report_id);
                    //clean current session namespace
                    $this->report_session->cleanNamespace();
                    //save session content into db
                    $new_report->saveCriteria();
                    $new_report->saveAdditionalCriteria();
                    $new_report->saveRenderers();
                    $new_report->report_session->cleanNamespace();
                } else {
                    $GLOBALS['Response']->addFeedback('error', 'Invalid copy name', CODENDI_PURIFIER_DISABLED);
                }

                $GLOBALS['Response']->redirect('?' . http_build_query([
                    'report'   => $redirect_to_report_id,
                ]));
                break;
            case self::ACTION_DELETE:
                $this->delete();
                $GLOBALS['Response']->redirect('?' . http_build_query([
                    'tracker'   => $this->tracker_id,
                ]));
                break;
            case self::ACTION_SCOPE:
                if ($this->getTracker()->userIsAdmin($current_user) && (! $this->user_id || $this->user_id == $current_user->getId())) {
                    if ($request->exist('report_scope_public')) {
                        $is_scope_public = $request->get('report_scope_public');
                        $old_user_id     = $this->user_id;
                        if ($is_scope_public && $this->user_id == $current_user->getId()) {
                            $this->user_id = null;
                        } elseif (! $is_scope_public && ! $this->user_id) {
                            $this->user_id = $current_user->getId();
                        }
                        if ($this->user_id != $old_user_id) {
                            Tracker_ReportFactory::instance()->save($this);

                            if (! $is_scope_public) {
                                $event = new TrackerReportSetToPrivate($this);
                                EventManager::instance()->processEvent($event);
                            }
                        }
                    }
                    $GLOBALS['Response']->redirect('?' . http_build_query([
                        'report' => $this->id,
                    ]));
                }
                break;
            case self::ACTION_DEFAULT:
                if ($this->getTracker()->userIsAdmin($current_user)) {
                    if ($request->exist('report_default')) {
                        if ($request->get('report_default')) {
                            $this->is_default = true;
                        } else {
                            $this->is_default = false;
                        }
                    }
                    $this->setDefaultReport();
                    $GLOBALS['Response']->redirect('?' . http_build_query([
                        'report'   => $this->id,
                    ]));
                }
                break;
            case 'store-expert-mode':
                if (! $current_user->isAnonymous()) {
                    if ($request->isPost()) {
                        $this->report_session->storeExpertMode();
                        $this->report_session->setHasChanged();
                        $this->is_in_expert_mode = true;
                    }
                }
                break;
            case 'store-normal-mode':
                if (! $current_user->isAnonymous()) {
                    if ($request->isPost()) {
                        $this->report_session->storeNormalMode();
                        $this->report_session->setHasChanged();
                        $this->is_in_expert_mode = false;
                    }
                }
                break;
            default:
                if ($request->exist('tracker_query_submit')) {
                    $criteria_values = $request->get('criteria');
                    if (! empty($criteria_values)) {
                        $this->updateCriteriaValues($criteria_values);
                    }

                    $additional_criteria_values = $request->get('additional_criteria');
                    if (! empty($additional_criteria_values)) {
                        $this->updateAdditionalCriteriaValues($additional_criteria_values);
                    }

                    $this->report_session->setHasChanged();
                }
                if ($request->exist('tracker_expert_query_submit') && ! $current_user->isAnonymous()) {
                    $expert_query = $request->get('expert_query');
                    $this->report_session->storeExpertQuery($expert_query);

                    if ($this->expert_query !== $expert_query) {
                        $this->expert_query = $expert_query;
                        $this->report_session->setHasChanged();
                    }
                }
                if ($this->is_in_expert_mode && $this->expert_query) {
                    try {
                        $this->validateExpertQuery();
                    } catch (SearchablesDoNotExistException $exception) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            $exception->getMessage()
                        );
                    } catch (SearchablesAreInvalidException $exception) {
                        foreach ($exception->getErrorMessages() as $message) {
                            $GLOBALS['Response']->addFeedback(
                                Feedback::ERROR,
                                $message
                            );
                        }
                    } catch (SyntaxError $exception) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            dgettext('tuleap-tracker', 'Error during parsing expert query')
                        );
                    } catch (LimitSizeIsExceededException $exception) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            dgettext('tuleap-tracker', 'The query is considered too complex to be executed by the server.
                                Please simplify it (e.g remove comparisons) to continue.')
                        );
                    }
                }
                $this->display($layout, $request, $current_user);
                break;
        }
    }

    public function setDefaultReport(): void
    {
        $default_report = Tracker_ReportFactory::instance()->getDefaultReportByTrackerId($this->tracker_id);
        if ($default_report) {
            $default_report->is_default = false;
            Tracker_ReportFactory::instance()->save($default_report);
        }
        Tracker_ReportFactory::instance()->save($this);
    }

    /**
     * NOTICE: make sure you are in the correct session namespace
     */
    public function saveCriteria()
    {
        //populate $this->criteria
        $this->getCriteria();
        //Delete criteria value
        foreach ($this->criteria as $c) {
            if ($c->field->getCriteriaValue($c)) {
                $c->field->delete($c->id);
            }
        }
        //Delete criteria in the db
        $this->deleteAllCriteria();

        $session_criteria = $this->report_session->getCriteria();
        if (is_array($session_criteria)) {
            foreach ($session_criteria as $key => $session_criterion) {
                if (! empty($session_criterion['is_removed'])) {
                    continue;
                }

                if (isset($this->criteria[$key])) {
                    $c  = $this->criteria[$key];
                    $id = $this->addCriteria($c);
                    $c->setId($id);
                    $c->updateValue($session_criterion['value']);
                }
            }
        }
    }

    public function saveAdditionalCriteria()
    {
        $additional_criteria = $this->getAdditionalCriteria(false);

        $this->saveCommentCriterion($additional_criteria);

        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_REPORT_SAVE_ADDITIONAL_CRITERIA,
            [
                'additional_criteria' => $additional_criteria,
                'report'              => $this,
            ]
        );
    }

    private function saveCommentCriterion(array $additional_criteria)
    {
        $saver             = new CommentCriterionValueSaver(new CommentDao());
        $comment_criterion = $this->getAdditionalCommentCriterion($additional_criteria);
        if ($comment_criterion) {
            $saver->saveValueForReport($this, $comment_criterion);
        }
    }

    /**
     * Save report renderers
     * NOTICE: make sure you are in the correct session namespace
     *
     * @return void
     */
    public function saveRenderers()
    {
        $rrf = Tracker_Report_RendererFactory::instance();

        //Get the renderers in the session and in the db
        $renderers_session = $this->getRenderers();
        $renderers_db      = $rrf->getReportRenderersByReportFromDb($this);

        //Delete renderers in db if they are deleted in the session
        foreach ($renderers_db as $renderer_db_key => $renderer_db) {
            if (! isset($renderers_session[$renderer_db_key])) {
                $this->deleteRenderer($renderer_db_key);
            }
        }

        //Create or update renderers in db
        if (is_array($renderers_session)) {
            foreach ($renderers_session as $renderer_key => $renderer) {
                if (! isset($renderers_db[$renderer_key])) {
                    // this is a new renderer
                    $renderer->create();
                } else {
                    // this is an old renderer
                    $rrf->save($renderer);
                    $renderer->update();
                }
            }
        }
    }

    /**
     * Delete the report and its renderers
     */
    protected function delete()
    {
        //Delete user preferences
        $dao = new UserPreferencesDao();
        $dao->deleteByPreferenceNameAndValue('tracker_' . $this->tracker_id . '_last_report', (string) $this->id);

        //Delete criteria
        foreach ($this->getCriteria() as $criteria) {
            $this->removeCriteria($criteria->field->id);
        }

        //Delete renderers
        foreach ($this->getRenderers() as $renderer) {
            $this->deleteRenderer($renderer);
        }

        //clean session
        $this->report_session->cleanNamespace();

        //Delete me
        Tracker_ReportFactory::instance()->delete($this->id);

        $event = new TrackerReportDeleted($this);
        EventManager::instance()->processEvent($event);
    }

    public function duplicate($from_report, $formElement_mapping, MappingRegistry $mapping_registry)
    {
        //Duplicate criteria
        Tracker_Report_CriteriaFactory::instance()->duplicate($from_report, $this, $formElement_mapping);

        //Duplicate renderers
        Tracker_Report_RendererFactory::instance()->duplicate($from_report, $this, $formElement_mapping, $mapping_registry);
    }

    /**
     * Transforms Report into a SimpleXMLElement
     *
     * @param SimpleXMLElement $roott the node to which the Report is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $roott, $xmlMapping): void
    {
        $root = $roott->addChild('report');
        $root->addAttribute('id', self::XML_ID_PREFIX . $this->id);

        // only add if different from default values
        if (! $this->is_default) {
            $root->addAttribute('is_default', '0');
        }
        if (! $this->is_query_displayed) {
            $root->addAttribute('is_query_displayed', $this->is_query_displayed);
        }
        if ($this->is_in_expert_mode) {
            $root->addAttribute('is_in_expert_mode', "1");
        }
        if ($this->expert_query) {
            $root->addAttribute('expert_query', $this->expert_query);
        }

        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'name', $this->name);
        // only add if not empty
        if ($this->description) {
            $cdata->insert($root, 'description', $this->description);
        }
        $child = $root->addChild('criterias');
        foreach ($this->getCriteria() as $criteria) {
            if ($criteria->field->isUsed()) {
                $grandchild = $child->addChild('criteria');
                $criteria->exportToXML($grandchild, $xmlMapping);
            }
        }
        $child = $root->addChild('renderers');
        foreach ($this->getRenderers() as $renderer) {
            $grandchild = $child->addChild('renderer');
            $renderer->exportToXML($grandchild, $xmlMapping);
        }
    }

    protected $dao;
    /**
     * @return Tracker_ReportDao
     */
    public function getDao()
    {
        if (! $this->dao) {
            $this->dao = new Tracker_ReportDao();
        }
        return $this->dao;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Tracker_Report_AdditionalCriterion[]
     */
    public function getAdditionalCriteria(bool $use_data_from_db): array
    {
        $session_additional_criteria = null;
        if (! $use_data_from_db && isset($this->report_session)) {
            $session_additional_criteria = $this->report_session->getAdditionalCriteria();
        }

        $additional_criteria = [];
        if ($session_additional_criteria) {
            foreach ($session_additional_criteria as $key => $additional_criterion_value) {
                $additional_criterion      = new Tracker_Report_AdditionalCriterion($key, $additional_criterion_value['value']);
                $additional_criteria[$key] = $additional_criterion;
            }
        } else {
            $additional_criteria_values = [
                self::COMMENT_CRITERION_NAME => [
                    'value' => $this->getCommentCriterionValueFromDatabase(),
                ],
            ];

            EventManager::instance()->processEvent(
                self::TRACKER_EVENT_REPORT_LOAD_ADDITIONAL_CRITERIA,
                [
                    'additional_criteria_values' => &$additional_criteria_values,
                    'report'                     => $this,
                ]
            );
            foreach ($additional_criteria_values as $key => $additional_criterion_value) {
                $additional_criterion      = new Tracker_Report_AdditionalCriterion($key, $additional_criterion_value['value']);
                $additional_criteria[$key] = $additional_criterion;
                if (isset($this->report_session)) {
                    $this->report_session->storeAdditionalCriterion($additional_criterion);
                }
            }
        }

        return $additional_criteria;
    }

    /**
     * @return string|null
     */
    private function getCommentCriterionValueFromDatabase()
    {
        $retriever = $this->getCommentCriterionValueRetriever();

        return $retriever->getValueForReport($this);
    }

    /**
     * @return CommentCriterionValueRetriever
     */
    protected function getCommentCriterionValueRetriever()
    {
        return new CommentCriterionValueRetriever(new CommentDao());
    }

    /**
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     */
    public function validateExpertQuery()
    {
        $validator = new ExpertQueryValidator(
            $this->parser,
            $this->getSizeValidator()
        );
        $validator->validateExpertQuery(
            $this->expert_query,
            new InvalidSearchablesCollectionBuilder($this->getCollector())
        );
    }

    private function fetchUpdateRendererForm(Tracker_Report_Renderer $renderer)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $update_renderer  = '';
        $update_renderer .= '<form action="" method="POST">';
        $update_renderer .= '<input type="hidden" name="report" value="' . $this->id . '" />';
        $update_renderer .= '<input type="hidden" name="renderer" value="' . (int) $renderer->id . '" />';
        $update_renderer .= '
            <label class="radio">
                <input type="radio" name="func" value="rename-renderer" id="tracker_renderer_updater_rename" />
                ' . dgettext('tuleap-tracker', 'Update') . '
            </label>
            <div class="tracker-renderer-details">
               <label for="tracker_renderer_updater_rename_name">' . dgettext('tuleap-tracker', 'Name') . '</label>
               <input type="text"
                      name="new_name"
                      id="tracker_renderer_updater_rename_name"
                      value="' .  $hp->purify($renderer->name, CODENDI_PURIFIER_CONVERT_HTML)  . '" /><br />
               <label for="tracker_renderer_updater_rename_description">' . dgettext('tuleap-tracker', 'Description') . '</label>
               <textarea
                      name="new_description"
                      rows="5"
                      cols="30"
                      id="tracker_renderer_updater_rename_description"
                      >' .  $hp->purify($renderer->description, CODENDI_PURIFIER_CONVERT_HTML)  . '</textarea>
            </div>
        ';
        $update_renderer .= '<label class="radio"><input type="radio" name="func" value="delete-renderer" id="tracker_renderer_updater_delete" />' . dgettext('tuleap-tracker', 'Delete') . '</label>';
        $update_renderer .= '<br/>';
        $update_renderer .= '<input type="submit" class="btn btn-primary" value="' .  $hp->purify($GLOBALS['Language']->getText('global', 'btn_submit'), CODENDI_PURIFIER_CONVERT_HTML)  . '" onclick="if ($(\'tracker_renderer_updater_delete\').checked) return confirm(\'' . dgettext('tuleap-tracker', 'Are you sure that you want to delete it?') . '\');"/> ';
        $update_renderer .= '</form>';

        return $update_renderer;
    }

    private function fetchAddRendererForm($current_renderer)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $current_renderer_id = ($current_renderer) ? (int) $current_renderer->id : '';

        $add_renderer  = '';
        $add_renderer .= '<form action="" method="POST">';
        $add_renderer .= '<input type="hidden" name="report" value="' . $this->id . '" />';
        $add_renderer .= '<input type="hidden" name="renderer" value="' . $current_renderer_id . '" />';
        $add_renderer .= '<input type="hidden" name="func" value="add-renderer" />';
        $rrf           = Tracker_Report_RendererFactory::instance();
        $types         = $rrf->getTypes();
        if (count($types) > 1) { //No need to ask for type if there is only one
            $type = '<select name="new_type" id="tracker_renderer_add_type">';
            foreach ($types as $key => $label) {
                $type .= '<option value="' . $key . '">' .  $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML)  . '</option>';
            }
            $type .= '</select>';
        } else {
            $type = current($types);
        }
        $add_renderer .= '<p><strong>' . dgettext('tuleap-tracker', 'Add a new') . ' ' . $type . '</strong></p>';
        $add_renderer .= '<p>';
        $add_renderer .= '<label for="tracker_renderer_add_name">' . dgettext('tuleap-tracker', 'Name') . '</label>
                         <input type="text" name="new_name" value="" id="tracker_renderer_add_name" />';

        $add_renderer .= '<label for="tracker_renderer_add_description">' . dgettext('tuleap-tracker', 'Description') . '</label>
                         <textarea
                            name="new_description"
                            id="tracker_renderer_add_description"
                            rows="5"
                            cols="30"></textarea>';

        $add_renderer .= '</p>';
        $add_renderer .= '<input type="submit" class="btn btn-primary" value="' .  $hp->purify($GLOBALS['Language']->getText('global', 'btn_submit'), CODENDI_PURIFIER_CONVERT_HTML)  . '" onclick="if (!$(\'tracker_renderer_add_name\').getValue()) { alert(\'' . dgettext('tuleap-tracker', 'Name is mandatory') . '\'); return false;}"/> ';
        $add_renderer .= '</form>';

        return $add_renderer;
    }

    private function getMatchingIdsFromCriteria($request, $use_data_from_db)
    {
        if (! $this->matching_ids) {
            $user = $this->getCurrentUser();
            if ($use_data_from_db) {
                $criteria = $this->getCriteriaFromDb();
            } else {
                $criteria = $this->getCriteria();
            }

            $additional_criteria = $this->getAdditionalCriteria($use_data_from_db);
            $this->matching_ids  = $this->getMatchingIdsFromCriteriaInDb($criteria, $additional_criteria);

            $event = new TrackerReportProcessAdditionalQuery(
                $this,
                $this->getTracker(),
                $user,
                $additional_criteria
            );
            EventManager::instance()->processEvent($event);

            $result           = $event->getResult();
            $search_performed = $event->isSearchPerformed();

            if ($search_performed) {
                $joiner = new Tracker_Report_ResultJoiner();

                $this->matching_ids = $this->implodeMatchingIds(
                    $joiner->joinResults(
                        $this->getLastChangesetIdByArtifactId($this->matching_ids),
                        $result
                    )
                );
            }
        }

        return $this->matching_ids;
    }

    private function getMatchingIdsFromExpertQuery(bool $use_data_from_db)
    {
        if ($this->matching_ids) {
            return $this->matching_ids;
        }

        if ($use_data_from_db) {
            $expert_query = $this->expert_query_from_db;
        } else {
            $expert_query = $this->expert_query;
        }

        if (empty($expert_query)) {
            $this->matching_ids = $this->getUnfilteredMatchingIds();

            return $this->matching_ids;
        }

        try {
            $expression = $this->parser->parse($expert_query);

            if ($this->canExecuteExpertQuery($expression)) {
                $from_where = Option::fromValue(
                    $this->getQueryBuilder()->buildFromWhere($expression, $this->getTracker(), $this->getCurrentUser())
                );

                $this->matching_ids = $this->getMatchingIdsInDb($from_where);

                return $this->matching_ids;
            }
        } catch (SyntaxError $e) {
        }

        $this->matching_ids = $this->getNoMatchingIds();

        return $this->matching_ids;
    }

    /**
     * @param Option<IProvideParametrizedFromAndWhereSQLFragments> $from_where
     *
     * @return array{id: string, last_changeset_id: string}
     */
    private function getMatchingIdsInDb(Option $from_where): array
    {
        $matching_ids = $this->getNoMatchingIds();

        $dao                  = new QueryDao();
        $tracker              = $this->getTracker();
        $user                 = $this->getCurrentUser();
        $group_id             = (int) $tracker->getGroupId();
        $permissions          = $this->getPermissionsManager()->getPermissionsAndUgroupsByObjectid($tracker->getId());
        $contributor_field    = $tracker->getContributorField();
        $contributor_field_id = $contributor_field ? $contributor_field->getId() : null;

        if (isset($this->additional_from_where)) {
            $from_where = $this->addFromWhere($from_where, $this->additional_from_where);
        }

        $matching_ids_result = $dao->searchMatchingIds(
            $group_id,
            $tracker->getId(),
            $from_where,
            $user,
            $permissions,
            $contributor_field_id
        );
        if ($matching_ids_result) {
            $matching_ids['id']                = implode(',', array_column($matching_ids_result, 'id'));
            $matching_ids['last_changeset_id'] = implode(',', array_column($matching_ids_result, 'last_changeset_id'));
        }

        return $matching_ids;
    }

    private function getNoMatchingIds(): array
    {
        return ['id' => '', 'last_changeset_id' => ''];
    }

    private function getUnfilteredMatchingIds()
    {
        return $this->getMatchingIdsInDb(Option::nothing(IProvideParametrizedFromAndWhereSQLFragments::class));
    }

    private function canExecuteExpertQuery($parsed_query)
    {
        try {
            $this->getSizeValidator()->checkSizeOfTree($parsed_query);
        } catch (LimitSizeIsExceededException $e) {
            return false;
        }

        $invalid_searchables_collection = $this->getInvalidSearchablesInExpertQuery($parsed_query);

        return ! $invalid_searchables_collection->hasInvalidSearchable();
    }

    /**
     * @return InvalidSearchablesCollection
     */
    private function getInvalidSearchablesInExpertQuery($parsed_query)
    {
        $invalid_searchables_collection = new InvalidSearchablesCollection();
        $this->getCollector()->collectErrors($parsed_query, $invalid_searchables_collection);

        return $invalid_searchables_collection;
    }

    public function getMatchingIdsWithAdditionalFromWhere(IProvideParametrizedFromAndWhereSQLFragments $from_where)
    {
        $this->additional_from_where = $from_where;
        $matching_ids                = $this->getMatchingIds();
        unset($this->additional_from_where);

        return $matching_ids;
    }

    private function getCollector()
    {
        if (! isset($this->collector)) {
            $this->collector = new InvalidTermCollectorVisitor(
                new InvalidFields\EqualComparisonVisitor(),
                new InvalidFields\NotEqualComparisonVisitor(),
                new InvalidFields\LesserThanComparisonVisitor(),
                new InvalidFields\GreaterThanComparisonVisitor(),
                new InvalidFields\LesserThanOrEqualComparisonVisitor(),
                new InvalidFields\GreaterThanOrEqualComparisonVisitor(),
                new InvalidFields\BetweenComparisonVisitor(),
                new InvalidFields\InComparisonVisitor(),
                new InvalidFields\NotInComparisonVisitor(),
                new InvalidFields\ArtifactLink\ArtifactLinkTypeChecker(
                    new TypePresenterFactory(
                        new TypeDao(),
                        new ArtifactLinksUsageDao(),
                    ),
                ),
                new InvalidMetadata\EqualComparisonChecker(),
                new InvalidMetadata\NotEqualComparisonChecker(),
                new InvalidMetadata\LesserThanComparisonChecker(),
                new InvalidMetadata\GreaterThanComparisonChecker(),
                new InvalidMetadata\LesserThanOrEqualComparisonChecker(),
                new InvalidMetadata\BetweenComparisonChecker(),
                new InvalidMetadata\InComparisonChecker(),
                new InvalidMetadata\NotInComparisonChecker(),
                new InvalidSearchableCollectorVisitor(
                    $this->getFormElementFactory(),
                    $this->getTracker(),
                    $this->getCurrentUser()
                )
            );
        }

        return $this->collector;
    }

    private function getCommentFromWhereBuilder(): CommentFromWhereBuilder
    {
        $factory = new CommentFromWhereBuilderFactory(
            new PermissionChecker(new CachingTrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentInformationRetriever(new TrackerPrivateCommentUGroupEnabledDao())))
        );

        return $factory->buildCommentFromWhereBuilderForTracker(
            $this->getCurrentUser(),
            $this->getTracker()
        );
    }

    private function getQueryBuilder(): QueryBuilderVisitor
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        return new QueryBuilderVisitor(
            new QueryBuilder\EqualFieldComparisonVisitor($db),
            new QueryBuilder\NotEqualFieldComparisonVisitor($db),
            new QueryBuilder\LesserThanFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanFieldComparisonVisitor(),
            new QueryBuilder\LesserThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\BetweenFieldComparisonVisitor(),
            new QueryBuilder\InFieldComparisonVisitor(),
            new QueryBuilder\NotInFieldComparisonVisitor(),
            new QueryBuilder\FromWhereSearchableVisitor($this->getFormElementFactory()),
            new QueryBuilder\MetadataEqualComparisonFromWhereBuilder($this->getCommentFromWhereBuilder()),
            new QueryBuilder\MetadataNotEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataBetweenComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataInComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataNotInComparisonFromWhereBuilder(),
            new QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder(Tracker_ArtifactFactory::instance()),
            new QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder(Tracker_ArtifactFactory::instance()),
        );
    }
}
