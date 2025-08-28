<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use Codendi_HTMLPurifier;
use Codendi_Mail;
use ErrorChart;
use EventManager;
use HTTPRequest;
use Override;
use PFUser;
use SystemEventManager;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactFactory;
use Tracker_Chart_BurndownView;
use Tracker_Chart_Data_Burndown;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_HierarchyFactory;
use Tracker_IDisplayTrackerLayout;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\BurndownCacheIsCurrentlyCalculatedException;
use Tuleap\Tracker\FormElement\BurndownFieldPresenter;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\ChartFieldUsage;
use Tuleap\Tracker\FormElement\ChartMessageFetcher;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\Semantic\Timeframe\IComputeTimeframes;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\UserWithReadAllPermissionBuilder;
use UserManager;

class BurndownField extends TrackerField implements Tracker_FormElement_Field_ReadOnly
{
    public const string LOG_IDENTIFIER = 'burndown_syslog';

    /**
     * Request parameter to display burndown image
     */
    public const string FUNC_SHOW_BURNDOWN = 'show_burndown';

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    #[Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Burndown Chart');
    }

    #[Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the burndown chart for the artifact');
    }

    #[Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burndown.png');
    }

    #[Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burndown--plus.png');
    }

    #[Override]
    public static function getFactoryUniqueField()
    {
        return true;
    }

    /**
     * Returns the previously injected factory (e.g. in tests), or a new
     * instance (e.g. in production).
     *
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory()
    {
        if ($this->hierarchy_factory == null) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }

    /**
     * Provides a way to inject the HierarchyFactory, since it cannot be done
     * in the constructor.
     *
     * @param Tracker_HierarchyFactory $hierarchy_factory
     */
    public function setHierarchyFactory($hierarchy_factory)
    {
        $this->hierarchy_factory = $hierarchy_factory;
    }

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
    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ) {
        $html  = $this->fetchBurndownReadOnly($artifact);
        $html .= $this->fetchBurndownCacheGenerationButton($artifact);

        return $html;
    }

    public function fetchBurndownReadOnly(Artifact $artifact)
    {
        $user               = $this->getCurrentUser();
        $burndown_presenter = $this->buildPresenter($artifact, $user);

        return $this->renderPresenter($burndown_presenter);
    }

    public function buildPresenter(Artifact $artifact, PFUser $user)
    {
        $warning                      = '';
        $burndown_rest_representation = null;

        try {
            $value_retriever = $this->getBurndownConfigurationValueRetriever();

            $burndown_data = $this->getBurndownData(
                $artifact,
                $user,
                $value_retriever->getDatePeriod($artifact, $user)
            );

            if ($burndown_data->isBeingCalculated()) {
                $warning = dgettext(
                    'tuleap-tracker',
                    'Burndown is under calculation. It will be available in a few minutes.'
                );
            }

            $burndown_rest_representation = $burndown_data->getRESTRepresentation();
        } catch (BurndownCacheIsCurrentlyCalculatedException $error) {
            $burndown_representation = null;
            $warning                 = $error->getMessage();
        } catch (Tracker_FormElement_Chart_Field_Exception $error) {
            $burndown_representation = null;
            $warning                 = $error->getMessage();
        }

        $assets = new IncludeAssets(
            __DIR__ . '/../../../../scripts/burndown-chart/frontend-assets',
            '/assets/trackers/burndown-chart'
        );

        assert($GLOBALS['HTML'] instanceof \Tuleap\Layout\BaseLayout);
        $GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($assets, 'burndown-chart.js'));
        $GLOBALS['HTML']->addCssAsset(new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($assets, 'burndown-chart-style'));

        return new BurndownFieldPresenter(
            $user,
            $warning,
            $burndown_rest_representation
        );
    }

    #[Override]
    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $html    .= '<img src="' . $this->getBurndownImageUrl($artifact) . '" alt="' .
                     $purifier->purify($this->getLabel()) . '" width="390" height="400" />';

        return $html;
    }

    private function fetchBurndownCacheGenerationButton(Artifact $artifact)
    {
        $user = $this->getCurrentUser();

        $html = '';
        if (
            $user->isAdmin($artifact->getTracker()->getGroupId())
            && $this->getBurndownCacheChecker()->isCacheBurndownAlreadyAsked($artifact) === false
            && $this->getBurndownConfigurationValueChecker()->areBurndownFieldsCorrectlySet($artifact, $user)
            && ! strpos($_SERVER['REQUEST_URI'], 'from_agiledashboard')
        ) {
            $html .= '<a class="btn chart-cache-button-generate" data-toggle="modal" href="#burndown-generate">' .
                     dgettext('tuleap-tracker', 'Force cache regeneration') . '</a>';

            $html .= $this->fetchBurndownGenerationModal($artifact);
        }

        return $html;
    }

    private function fetchBurndownGenerationModal(Artifact $artifact)
    {
        $header = dgettext('tuleap-tracker', 'Force cache regeneration');

        $body = dgettext('tuleap-tracker', 'Do you really want to force burndown cache generation? Cache generation will end up on a non availability of burndown for few minutes.');

        $cancel = dgettext('tuleap-tracker', 'Cancel');

        $generate = dgettext('tuleap-tracker', 'Force cache regeneration');

        return '<div id="burndown-generate" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-header">
                    <h3>' . $header . '</h3>
                </div>
                <div class="modal-body">
                   ' . $body . '
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">' . $cancel . '</button>
                    <a href="?aid=' . $artifact->getId() . '&func=burndown-cache-generate&field=' . $this->getId() . '"
                        class="btn btn-primary force-burndown-generation" name="add-keys">' . $generate . '</a>
                </div>
            </div>';
    }

    /**
     *
     * @param HTTPRequest $request
     * @param PFUser $current_user
     */
    #[Override]
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        switch ($request->get('func')) {
            case self::FUNC_SHOW_BURNDOWN:
                try {
                    $artifact_id = $request->getValidated('src_aid', 'uint', 0);
                    $artifact    = $this->getArtifactFactory()->getArtifactById($artifact_id);
                    if (! $artifact) {
                        return;
                    }
                    $this->fetchBurndownImage($artifact, $current_user);
                } catch (Tracker_FormElement_Chart_Field_Exception $e) {
                    $this->displayErrorImage($e->getMessage());
                } catch (BurndownCacheIsCurrentlyCalculatedException $e) {
                    $this->displayErrorImage(dgettext('tuleap-tracker', 'Burndown is under calculation. It will be available in few minutes.'));
                }
                break;
            default:
                parent::process($layout, $request, $current_user);
        }
    }

    /**
     * Render a burndown image based on $artifact artifact links
     *
     *
     * @throws Tracker_FormElement_Chart_Field_Exception
     * @throws BurndownCacheIsCurrentlyCalculatedException
     */
    public function fetchBurndownImage(Artifact $artifact, PFUser $user)
    {
        if ($this->userCanRead($user)) {
            $burndown_data = $this->buildBurndownDataForLegacy($user, $artifact);

            if ($burndown_data->isBeingCalculated() === true) {
                throw new BurndownCacheIsCurrentlyCalculatedException();
            } else {
                $this->getBurndown($burndown_data)->display();
            }
        } else {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'You are not allowed to access this field.')
            );
        }
    }

    #[Override]
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset): ArtifactFieldValueFullRepresentation
    {
        $artifact     = $changeset->getArtifact();
        $form_element = $this->getFormElementFactory()->getFormElementById($this->getId());

        $artifact_field_value_representation = new ArtifactFieldValueFullRepresentation();
        $artifact_field_value_representation->build(
            $this->getId(),
            $this->getFormElementFactory()->getType($form_element),
            $this->getLabel(),
            $this->getBurndownDataForREST(
                $artifact,
                $user,
                $this->getDatePeriodForRESTRepresentation($artifact, $user)
            )->getRESTRepresentation()
        );

        return $artifact_field_value_representation;
    }

    private function getDatePeriodForRESTRepresentation(Artifact $artifact, PFUser $user): DatePeriodWithOpenDays
    {
        $calculator = $this->getTimeframeCalculator();

        return $calculator->buildDatePeriodWithoutWeekendForChangesetForREST($artifact->getLastChangeset(), $user, $this->getLogger());
    }

    protected function getLogger(): \Psr\Log\LoggerInterface
    {
        return \BackendLogger::getDefaultLogger(self::LOG_IDENTIFIER);
    }

    /**
     * @return Tracker_Chart_Data_Burndown
     * @throws BurndownCacheIsCurrentlyCalculatedException
     */
    public function getBurndownData(Artifact $artifact, PFUser $user, DatePeriodWithOpenDays $date_period)
    {
        $builder = $this->getBurndownDataBuilderForREST();
        return $builder->build($artifact, $user, $date_period);
    }

    /**
     * @return Tracker_Chart_Data_Burndown
     * @throws BurndownCacheIsCurrentlyCalculatedException
     */
    public function getBurndownDataForREST(Artifact $artifact, PFUser $user, DatePeriodWithOpenDays $date_period)
    {
        return $this->getBurndownData($artifact, $user, $date_period);
    }

    private function getSystemEventManager()
    {
        return SystemEventManager::instance();
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
        return '';
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Artifact $artifact The artifact
     * @param PFUser $user The user who will receive the email
     * @param Tracker_Artifact_ChangesetValue $value The actual value of the field
     * @param string $format output format
     *
     */

    #[Override]
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        bool $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        string $format = 'text',
    ): string {
        $purifier = Codendi_HTMLPurifier::instance();
        $output   = '';
        if ($format == Codendi_Mail::FORMAT_HTML) {
            $output .= '<img src="' . \Tuleap\ServerHostname::HTTPSUrl() . $this->getBurndownImageUrl($artifact) . '" alt="' . $purifier->purify($this->getLabel()) . '" width="640" height="480" />';
            $output .= '<p><em>' . dgettext('tuleap-tracker', 'Please note that the image above is rendered in real time so it represents burndown as of today, not as when the email was sent.') . '</em></p>';
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    #[Override]
    public function fetchAdminFormElement()
    {
        $html  = '';
        $html .= $this->getBurndownMessageFetcher()->fetchWarnings($this, $this->getChartFieldUsage());
        $html .= '<img src="' . TRACKER_BASE_URL . '/images/fake-burndown-admin.png" />';
        $html .= '<a class="btn chart-cache-button-generate" disabled="disabled">' .
                 dgettext('tuleap-tracker', 'Force cache regeneration') .
                 '</a>';

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

    #[Override]
    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFromWhere::class);
    }

    #[Override]
    public function getQuerySelect(): string
    {
        return '';
    }

    #[Override]
    public function getQueryFrom()
    {
    }

    #[Override]
    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?array $redirection_parameters = null,
    ): string {
        return '';
    }

    #[Override]
    public function fetchCSVChangesetValue(int $artifact_id, int $changeset_id, mixed $value, ?Tracker_Report $report): string
    {
        return '';
    }

    #[Override]
    public function fetchCriteriaValue(Tracker_Report_Criteria $criteria): string
    {
        return '';
    }

    #[Override]
    public function fetchRawValue(mixed $value): string
    {
        return '';
    }

    #[Override]
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    #[Override]
    public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
    {
        return '';
    }

    #[Override]
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
    }

    #[Override]
    public function getRESTAvailableValues()
    {
    }

    #[Override]
    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        return false;
    }

    #[Override]
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
    }

    #[Override]
    protected function getCriteriaDao()
    {
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
    }

    /**
     * Display a png image with the given error message
     *
     * @param String $msg
     */
    protected function displayErrorImage($msg)
    {
        $error = new ErrorChart(dgettext('tuleap-tracker', 'Unable to render the chart'), $msg, 640, 480);
        $error->Stroke();
    }

    /**
     * Returns a Burndown rendering object for given data
     *
     *
     * @return \Tracker_Chart_BurndownView
     */
    protected function getBurndown(Tracker_Chart_Data_Burndown $burndown_data)
    {
        return new Tracker_Chart_BurndownView($burndown_data);
    }

    #[Override]
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
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
     * Return the relative url to the burndown chart image.
     *
     *
     * @return String
     */
    public function getBurndownImageUrl(Artifact $artifact)
    {
        $url_query = http_build_query(
            [
                'formElement' => $this->getId(),
                'func'        => self::FUNC_SHOW_BURNDOWN,
                'src_aid'     => $artifact->getId(),
            ]
        );

        return TRACKER_BASE_URL . '/?' . $url_query;
    }

    #[Override]
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitBurndown($this);
    }

    /**
     * Return the Field_Date_Dao
     *
     * @return ComputedFieldDao The dao
     */
    protected function getComputedDao()
    {
        return new ComputedFieldDao();
    }

    #[Override]
    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    /**
     * @see TrackerField::postSaveNewChangeset()
     */
    #[Override]
    public function postSaveNewChangeset(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
        try {
            if (
                $previous_changeset !== null &&
                $this->getBurndownCacheChecker()->isCacheBurndownAlreadyAsked($artifact) === false &&
                $this->getBurdownConfigurationFieldRetriever()->getBurndownRemainingEffortField($artifact, $submitter)
            ) {
                if ($this->getBurndownConfigurationValueChecker()->hasConfigurationChange($artifact, $submitter, $new_changeset) === true) {
                    $this->getBurndownCacheGenerator()->forceBurndownCacheGeneration($artifact->getId());
                }
            }
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
        }
    }

    /**
     * @return ChartConfigurationFieldRetriever
     */
    protected function getBurdownConfigurationFieldRetriever()
    {
        return new ChartConfigurationFieldRetriever(
            $this->getFormElementFactory(),
            SemanticTimeframeBuilder::build(),
            $this->getLogger()
        );
    }

    /**
     * @return ChartConfigurationValueRetriever
     */
    private function getBurndownConfigurationValueRetriever()
    {
        return new ChartConfigurationValueRetriever(
            $this->getBurdownConfigurationFieldRetriever(),
            $this->getTimeframeCalculator(),
            $this->getLogger()
        );
    }

    /**
     * @return ChartConfigurationValueChecker
     */
    private function getBurndownConfigurationValueChecker()
    {
        return new ChartConfigurationValueChecker(
            $this->getBurdownConfigurationFieldRetriever(),
            $this->getBurndownConfigurationValueRetriever()
        );
    }

    private function getBurndownMessageFetcher()
    {
        return new ChartMessageFetcher(
            $this->getHierarchyFactory(),
            $this->getBurdownConfigurationFieldRetriever(),
            EventManager::instance(),
            UserManager::instance()
        );
    }

    private function getChartFieldUsage()
    {
        $use_start_date       = true;
        $use_duration         = true;
        $use_capacity         = false;
        $use_hierarchy        = true;
        $use_remaining_effort = true;

        return new ChartFieldUsage(
            $use_start_date,
            $use_duration,
            $use_capacity,
            $use_hierarchy,
            $use_remaining_effort
        );
    }

    /**
     * @return ChartCachedDaysComparator
     */
    private function getCachedDaysComparator()
    {
        return new ChartCachedDaysComparator($this->getLogger());
    }

    /**
     * For testing purpose
     */
    protected function renderPresenter(BurndownFieldPresenter $burndown_presenter)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);

        return $renderer->renderToString('burndown-field', $burndown_presenter);
    }

    /**
     * @return BurndownDataBuilderForREST
     */
    private function getBurndownDataBuilderForREST()
    {
        return new BurndownDataBuilderForREST(
            $this->getLogger(),
            $this->getRemainingEffortAdder(),
            new BurndownCommonDataBuilder(
                $this->getLogger(),
                $this->getBurdownConfigurationFieldRetriever(),
                $this->getBurndownConfigurationValueRetriever(),
                $this->getBurndownCacheChecker()
            )
        );
    }

    /**
     * @return BurndownCacheGenerationChecker
     */
    private function getBurndownCacheChecker()
    {
        return new BurndownCacheGenerationChecker(
            $this->getLogger(),
            $this->getBurndownCacheGenerator(),
            $this->getSystemEventManager(),
            $this->getBurdownConfigurationFieldRetriever(),
            $this->getBurndownConfigurationValueChecker(),
            $this->getComputedDao(),
            $this->getCachedDaysComparator(),
            $this->getRemainingEffortAdder(),
            new BurndownCacheDateRetriever(),
        );
    }

    /**
     * @return BurndownCacheGenerator
     */
    private function getBurndownCacheGenerator()
    {
        return new BurndownCacheGenerator($this->getSystemEventManager());
    }

    /**
     * @return BurndownRemainingEffortAdderForREST
     */
    private function getRemainingEffortAdder()
    {
        return new BurndownRemainingEffortAdderForREST(
            $this->getBurdownConfigurationFieldRetriever(),
            $this->getComputedDao()
        );
    }

    /**
     * For testing purpose
     */
    protected function buildBurndownDataForLegacy(PFUser $user, Artifact $artifact)
    {
        $date_period = $this->getBurndownConfigurationValueRetriever()->getDatePeriod($artifact, $user);
        $builder     = $this->getBurndownDataBuilderForLegacy();

        return $builder->build($artifact, $user, $date_period);
    }

    private function getBurndownDataBuilderForLegacy()
    {
        return new BurndownDataBuilderForLegacy(
            $this->getLogger(),
            $this->getBurdownConfigurationFieldRetriever(),
            $this->getBurndownConfigurationValueRetriever(),
            $this->getBurndownCacheChecker(),
            $this->getBurndownAdderForLegacy()
        );
    }

    /**
     * @return BurndownRemainingEffortAdderForLegacy
     */
    private function getBurndownAdderForLegacy()
    {
        return new BurndownRemainingEffortAdderForLegacy(
            $this->getBurdownConfigurationFieldRetriever(),
            new UserWithReadAllPermissionBuilder()
        );
    }

    /**
     * protected for testing purpose
     */
    protected function getTimeframeCalculator(): IComputeTimeframes
    {
        return SemanticTimeframeBuilder::build()->getSemantic($this->getTracker())->getTimeframeCalculator();
    }

    /**
     * protected for testing purpose
     */
    protected function getArtifactFactory(): Tracker_ArtifactFactory
    {
        return Tracker_ArtifactFactory::instance();
    }

    #[Override]
    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
