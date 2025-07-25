<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashboard_Semantic_InitialEffortFactory;
use EventManager;
use PFUser;
use Psr\Log\LoggerInterface;
use SystemEventManager;
use TemplateRendererFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_ReadOnly;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tracker_Report_Criteria;
use Tuleap\AgileDashboard\FormElement\Burnup\Calculator\BurnupEffortCalculatorForArtifact;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\AgileDashboard\v1\Artifact\BurnupRepresentation;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\ChartFieldUsage;
use Tuleap\Tracker\FormElement\ChartMessageFetcher;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use UserManager;

class Burnup extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly, TrackerFormElementExternalField
{
    public const TYPE = 'burnup';

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitExternalField($this);
    }

    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new ViewAdminBurnupField($element, $used_element);
    }

    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    public function canBeUsedToSortReport()
    {
        return false;
    }

    /**
     * @return string html
     */
    public function fetchAdminFormElement()
    {
        $field_usage = $this->getChartFieldUsage();

        $html  = $this->getChartMessageFetcher()->fetchWarnings($this, $field_usage);
        $html .= '<img src="' . AGILEDASHBOARD_BASE_URL . '/images/fake-burnup-admin.png" />';

        return $html;
    }

    private function getChartMessageFetcher()
    {
        return new ChartMessageFetcher(
            Tracker_HierarchyFactory::instance(),
            $this->getConfigurationFieldRetriever(),
            EventManager::instance(),
            UserManager::instance()
        );
    }

    private function getConfigurationFieldRetriever()
    {
        return new ChartConfigurationFieldRetriever(
            $this->getFormElementFactory(),
            SemanticTimeframeBuilder::build(),
            $this->getLogger()
        );
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
    }

    public function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = [],
    ): string {
        return '';
    }

    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ) {
        $user                      = UserManager::instance()->getCurrentUser();
        $can_burnup_be_regenerated = $artifact->getTracker()->userIsAdmin($user);
        $burnup_presenter          = $this->buildPresenter($artifact, $can_burnup_be_regenerated, $user);

        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);

        return $renderer->renderToString('formelement/burnup-field', $burnup_presenter);
    }

    public function buildPresenter(Artifact $artifact, $can_burnup_be_regenerated, PFUser $user)
    {
        $warning     = '';
        $burnup_data = null;
        try {
            $burnup_data = $this->getBurnupDataBuilder()->buildBurnupData($artifact, $user);

            if ($burnup_data->isBeingCalculated()) {
                $warning = dgettext(
                    'tuleap-agiledashboard',
                    'Burnup is under calculation. It will be available in few minutes.'
                );
            }
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            $warning = $e->getMessage();
        }

        $include_assets = new IncludeViteAssets(
            __DIR__ . '/../../../scripts/burnup-chart/frontend-assets',
            '/assets/agiledashboard/burnup-chart'
        );
        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptViteAsset($include_assets, 'src/burnup-chart.js'));

        $capacity              = $this->getConfigurationValueRetriever()->getCapacity($artifact, $user);
        $burnup_representation = new BurnupRepresentation($capacity, $burnup_data);

        return new BurnupFieldPresenter(
            $this->getCountElementsModeChecker(),
            $burnup_representation,
            $artifact,
            $can_burnup_be_regenerated,
            $user->getLocale(),
            $warning
        );
    }

    public function fetchCSVChangesetValue(int $artifact_id, int $changeset_id, mixed $value, ?\Tracker_Report $report): string
    {
        return '';
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?\Tracker_Report $report = null,
        ?array $redirection_parameters = null,
    ): string {
        return '';
    }

    public function fetchCriteriaValue(Tracker_Report_Criteria $criteria): string
    {
        return '';
    }

    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        bool $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        string $format = 'text',
    ): string {
        return '';
    }

    public function fetchRawValue(mixed $value): string
    {
        return '';
    }

    public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
    {
        return '';
    }

    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

    public function fetchSubmitMasschange()
    {
        return '';
    }

    protected function fetchSubmitValue(array $submitted_values): string
    {
        return '';
    }

    protected function fetchSubmitValueMasschange(): string
    {
        return '';
    }

    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
    {
        return '';
    }

    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
    }

    protected function getCriteriaDao()
    {
    }

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFromWhere::class);
    }

    public function isCSVImportable(): bool
    {
        return false;
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the burnup chart for the artifact');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burnup--plus.png');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burnup.png');
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-agiledashboard', 'Burnup Chart');
    }

    public static function getFactoryUniqueField()
    {
        return true;
    }

    public function getQueryFrom()
    {
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact = $changeset->getArtifact();
        try {
            $burnup_data = $this->getBurnupDataBuilder()->buildBurnupData($artifact, $user);
        } catch (Tracker_FormElement_Chart_Field_Exception $ex) {
            $burnup_data = null;
        }
        $capacity = null;
        if ($this->getConfigurationFieldRetriever()->doesCapacityFieldExist($artifact->getTracker())) {
            $capacity = $this->getConfigurationValueRetriever()->getCapacity($artifact, $user);
        }

        $burnup_representation = new BurnupRepresentation($capacity, $burnup_data);
        $formelement_field     = $this->getFormElementFactory()->getFormElementById($this->getId());

        $field_representation = new ArtifactFieldValueFullRepresentation();
        $field_representation->build($this->getId(), $this->getFormElementFactory()->getType($formelement_field), $this->getLabel(), $burnup_representation);

        return $field_representation;
    }

    public function getRESTAvailableValues()
    {
    }

    protected function getValueDao()
    {
    }

    protected function keepValue(
        $artifact,
        $changeset_value_id,
        Tracker_Artifact_ChangesetValue $previous_changesetvalue,
    ) {
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
    ) {
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        return false;
    }

    public function testImport()
    {
        return true;
    }

    /**
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request.
     *
     * @return bool
     */
    protected function validate(Artifact $artifact, $value)
    {
        return true;
    }

    /**
     * @return ChartFieldUsage
     */
    private function getChartFieldUsage()
    {
        $use_start_date       = true;
        $use_duration         = true;
        $use_capacity         = true;
        $use_hierarchy        = false;
        $use_remaining_effort = false;

        return new ChartFieldUsage(
            $use_start_date,
            $use_duration,
            $use_capacity,
            $use_hierarchy,
            $use_remaining_effort
        );
    }

    private function getBurnupDataBuilder(): BurnupDataBuilder
    {
        $burnup_effort_cache_dao = new BurnupCacheDao();
        $burnup_count_cache_dao  = new CountElementsCacheDao();

        return new BurnupDataBuilder(
            $this->getLogger(),
            new BurnupCacheChecker(
                new BurnupCacheGenerator(
                    SystemEventManager::instance()
                ),
                new ChartConfigurationValueChecker(
                    $this->getConfigurationFieldRetriever(),
                    $this->getConfigurationValueRetriever()
                ),
                $burnup_effort_cache_dao,
                $burnup_count_cache_dao,
                new ChartCachedDaysComparator($this->getLogger()),
                $this->getCountElementsModeChecker(),
                $this->getLogger()
            ),
            $this->getConfigurationValueRetriever(),
            $burnup_effort_cache_dao,
            $this->getBurnupCalculator(),
            $burnup_count_cache_dao,
            new CountElementsCalculator(
                Tracker_Artifact_ChangesetFactoryBuilder::build(),
                Tracker_ArtifactFactory::instance(),
                Tracker_FormElementFactory::instance(),
                new BurnupDataDAO(),
                CachedSemanticStatusRetriever::instance(),
            ),
            $this->getCountElementsModeChecker(),
            new PlanningDao(),
            \PlanningFactory::build(),
            new BurnupCacheDateRetriever(),
        );
    }

    private function getBurnupCalculator(): BurnupCalculator
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new BurnupCalculator(
            Tracker_ArtifactFactory::instance(),
            new BurnupDataDAO(),
            new BurnupEffortCalculatorForArtifact(
                $changeset_factory,
                AgileDashboard_Semantic_InitialEffortFactory::instance(),
                new SemanticDoneFactory(
                    new SemanticDoneDao(),
                    new SemanticDoneValueChecker(),
                    CachedSemanticStatusRetriever::instance(),
                )
            )
        );
    }

    private function getLogger(): LoggerInterface
    {
        return \BackendLogger::getDefaultLogger('burnup_syslog');
    }

    /**
     * @return ChartConfigurationValueRetriever
     */
    private function getConfigurationValueRetriever()
    {
        $semantic_timeframe = SemanticTimeframeBuilder::build()->getSemantic($this->getTracker());

        return new ChartConfigurationValueRetriever(
            $this->getConfigurationFieldRetriever(),
            $semantic_timeframe->getTimeframeCalculator(),
            $this->getLogger()
        );
    }

    private function getCountElementsModeChecker(): CountElementsModeChecker
    {
        return new CountElementsModeChecker(new ProjectsCountModeDao());
    }

    public function isAlwaysInEditMode(): bool
    {
        return false;
    }
}
