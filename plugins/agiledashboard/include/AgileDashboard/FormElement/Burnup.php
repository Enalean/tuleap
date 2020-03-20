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
use Tracker_Artifact;
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
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\FormElement\Burnup\ProjectsCountModeDao;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDoneValueChecker;
use Tuleap\AgileDashboard\v1\Artifact\BurnupRepresentation;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\ChartFieldUsage;
use Tuleap\Tracker\FormElement\ChartMessageFetcher;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
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

        $html = $this->getChartMessageFetcher()->fetchWarnings($this, $field_usage);
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
            $this->getSemanticTimeframeBuilder(),
            $this->getLogger()
        );
    }

    public function fetchArtifactForOverlay(Tracker_Artifact $artifact, array $submitted_values)
    {
    }

    public function fetchArtifactValue(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = array()
    ) {
    }

    public function fetchArtifactValueReadOnly(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null
    ) {
        $user                      = UserManager::instance()->getCurrentUser();
        $can_burnup_be_regenerated = $artifact->getTracker()->userIsAdmin($user);
        $burnup_presenter = $this->buildPresenter($artifact, $can_burnup_be_regenerated, $user);

        $renderer = TemplateRendererFactory::build()->getRenderer(AGILEDASHBOARD_TEMPLATE_DIR);

        return $renderer->renderToString('formelement/burnup-field', $burnup_presenter);
    }

    public function buildPresenter(Tracker_Artifact $artifact, $can_burnup_be_regenerated, PFUser $user)
    {
        $warning     = "";
        $burnup_data = null;
        try {
            $burnup_data = $this->getBurnupDataBuilder()->buildBurnupData($artifact, $user);

            if ($burnup_data->isBeingCalculated()) {
                $warning = dgettext(
                    'tuleap-agiledashboard',
                    "Burnup is under calculation. It will be available in few minutes."
                );
            }
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            $warning = $e->getMessage();
        }

        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/agiledashboard',
            '/assets/agiledashboard'
        );
        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('burnup-chart.js'));

        $capacity                  = $this->getConfigurationValueRetriever()->getCapacity($artifact, $user);
        $burnup_representation     = new BurnupRepresentation($capacity, $burnup_data);
        $css_file_url              = $include_assets->getFileURL('burnup-chart.css');

        return new BurnupFieldPresenter(
            $this->getCountElementsModeChecker(),
            $burnup_representation,
            $artifact,
            $can_burnup_be_regenerated,
            $css_file_url,
            $user->getLocale(),
            $warning
        );
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
    }

    public function fetchCriteriaValue($criteria)
    {
    }

    public function fetchFollowUp($artifact, $from, $to)
    {
    }

    public function fetchMailArtifactValue(
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
    }

    public function fetchRawValue($value)
    {
    }

    public function fetchRawValueFromChangeset($changeset)
    {
    }

    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

    public function fetchSubmitMasschange()
    {
        return '';
    }

    protected function fetchSubmitValue(array $submitted_values)
    {
    }

    protected function fetchSubmitValueMasschange()
    {
    }

    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
    }

    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
    }

    protected function getCriteriaDao()
    {
    }

    public function getCriteriaFrom($criteria)
    {
    }

    public function getCriteriaWhere($criteria)
    {
    }

    protected function getDao()
    {
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Display the burnup chart for the artifact');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burndown--plus.png');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/burndown.png');
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

    public function getQuerySelect()
    {
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
        $formelement_field = $this->getFormElementFactory()->getFormElementById($this->getId());

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
        Tracker_Artifact_ChangesetValue $previous_changesetvalue
    ) {
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
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $id_mapping
    ) {
        return false;
    }

    public function testImport()
    {
        return true;
    }

    /**
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value data coming from the request.
     *
     * @return bool
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        return true;
    }

    /**
     * @return ChartFieldUsage
     */
    private function getChartFieldUsage()
    {
        $use_start_date        = true;
        $use_duration          = true;
        $use_capacity          = true;
        $use_hierarchy         = false;
        $use_remaining_effort  = false;

        return new ChartFieldUsage(
            $use_start_date,
            $use_duration,
            $use_capacity,
            $use_hierarchy,
            $use_remaining_effort
        );
    }

    /**
     * @return BurnupDataBuilder
     */
    private function getBurnupDataBuilder()
    {
        $burnup_cache_dao = new BurnupCacheDao;

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
                $burnup_cache_dao,
                new ChartCachedDaysComparator($this->getLogger())
            ),
            $this->getConfigurationValueRetriever(),
            $burnup_cache_dao,
            $this->getBurnupCalculator(),
            new CountElementsCacheDao(),
            new CountElementsCalculator(
                Tracker_Artifact_ChangesetFactoryBuilder::build(),
                Tracker_ArtifactFactory::instance(),
                Tracker_FormElementFactory::instance(),
                new BurnupDao()
            ),
            $this->getCountElementsModeChecker()
        );
    }

    /**
     * @return BurnupCalculator
     */
    private function getBurnupCalculator()
    {
        $changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();

        return new BurnupCalculator(
            $changeset_factory,
            Tracker_ArtifactFactory::instance(),
            new BurnupDao(),
            AgileDashboard_Semantic_InitialEffortFactory::instance(),
            new SemanticDoneFactory(new SemanticDoneDao(), new SemanticDoneValueChecker())
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
        $form_element_factory = Tracker_FormElementFactory::instance();

        return new ChartConfigurationValueRetriever(
            $this->getConfigurationFieldRetriever(),
            new TimeframeBuilder(
                new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $form_element_factory),
                $this->getLogger()
            ),
            $this->getLogger()
        );
    }

    private function getSemanticTimeframeBuilder()
    {
        return new SemanticTimeframeBuilder(
            new SemanticTimeframeDao(),
            Tracker_FormElementFactory::instance()
        );
    }

    private function getCountElementsModeChecker(): CountElementsModeChecker
    {
        return new CountElementsModeChecker(new ProjectsCountModeDao());
    }
}
