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

use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Tracker\Artifact\Workflow\GlobalRules\GlobalRulesHistoryEntry;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\Tracker;

require_once __DIR__ . '/../../../../../../src/www/include/html.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
class Tracker_Workflow_Action_Rules_EditRules extends Tracker_Workflow_Action
{
    public const string PARAMETER_ADD_RULE    = 'add_rule';
    public const string PARAMETER_REMOVE_RULE = 'remove_rule';

    public const string PARAMETER_SOURCE_FIELD = 'source_date_field';
    public const string PARAMETER_TARGET_FIELD = 'target_date_field';
    public const string PARAMETER_COMPARATOR   = 'comparator';

    private string $url_query;

    public function __construct(
        Tracker $tracker,
        private readonly Tracker_Rule_Date_Factory $rule_date_factory,
        private readonly CSRFSynchronizerTokenInterface $token,
        private readonly ProjectHistoryDao $project_history_dao,
        private readonly TemplateRendererFactory $template_renderer_factory,
    ) {
        parent::__construct($tracker);
        $this->url_query = TRACKER_BASE_URL . '/?' . http_build_query(
            [
                'tracker' => (int) $this->tracker->id,
                'func'    => Workflow::FUNC_ADMIN_RULES,
            ]
        );
    }

    private function shouldAddOrDeleteRules(\Tuleap\HTTPRequest $request): bool
    {
        $should_delete_rule = is_numeric($request->get(self::PARAMETER_REMOVE_RULE));

        return $should_delete_rule || $this->shouldAddRule($request);
    }

    private function shouldAddRule(\Tuleap\HTTPRequest $request): bool
    {
        $source_field_id = $this->getFieldIdFromAddRequest($request, self::PARAMETER_SOURCE_FIELD);
        $target_field_id = $this->getFieldIdFromAddRequest($request, self::PARAMETER_TARGET_FIELD);

        $fields_exist         = $source_field_id !== null && $target_field_id !== null;
        $fields_are_different = false;

        if ($fields_exist) {
            $fields_are_different = $this->checkFieldsAreDifferent($source_field_id, $target_field_id);
        }

        if ($fields_exist) {
            $fields_have_good_type = $this->fieldsAreDateOnes($source_field_id, $target_field_id);
        }

        $exist_comparator = (bool) $this->getComparatorFromAddRequest($request);

        return $fields_exist && $fields_are_different && $exist_comparator && $fields_have_good_type;
    }

    private function checkFieldsAreDifferent($source_field, $target_field): bool
    {
        $fields_are_different = $source_field !== $target_field;
        if (! $fields_are_different) {
            $error_msg = dgettext('tuleap-tracker', 'The two fields must be different');
            $GLOBALS['Response']->addFeedback('error', $error_msg);
        }
        return $fields_are_different;
    }

    private function getFieldIdFromAddRequest(\Tuleap\HTTPRequest $request, $source_or_target): ?int
    {
        $add = $request->get(self::PARAMETER_ADD_RULE);
        if (is_array($add) && isset($add[$source_or_target])) {
            return (int) $add[$source_or_target];
        }

        return null;
    }

    private function getComparatorFromAddRequest(\Tuleap\HTTPRequest $request)
    {
        $add = $request->get(self::PARAMETER_ADD_RULE);
        if (is_array($add)) {
            return $this->getComparatorFromRequestParameter($add);
        }
    }

    private function getComparatorFromRequestParameter(array $param)
    {
        $rule = new Rule_WhiteList(Tracker_Rule_Date::$allowed_comparators);
        if (isset($param[self::PARAMETER_COMPARATOR]) && $rule->isValid($param[self::PARAMETER_COMPARATOR])) {
            return $param[self::PARAMETER_COMPARATOR];
        }
    }

    private function fieldsAreDateOnes($source_field_id, $target_field_id): bool
    {
        $source_field_is_date = (bool) $this->rule_date_factory->getUsedDateFieldById($this->tracker, $source_field_id);
        $target_field_is_date = (bool) $this->rule_date_factory->getUsedDateFieldById($this->tracker, $target_field_id);

        return $source_field_is_date && $target_field_is_date;
    }

    #[\Override]
    public function process(Tracker_IDisplayTrackerLayout $layout, \Tuleap\HTTPRequest $request, PFUser $current_user): void
    {
        if ($this->shouldAddOrDeleteRules($request)) {
            // Verify CSRF Protection
            $this->token->check();
            $this->addOrDeleteRules($request, $current_user);
            $GLOBALS['Response']->redirect($this->url_query);
        } else {
            $this->displayPane($layout);
        }
    }

    private function addOrDeleteRules(\Tuleap\HTTPRequest $request, PFUser $user): void
    {
        $this->removeRules($request, $user);
        $this->addRule($request, $user);
    }

    /** @return array (source_field, target_field, comparator) */
    private function getFieldsAndComparatorFromRequestParameter(array $param): array
    {
        $source_field = null;
        $target_field = null;
        if (isset($param[self::PARAMETER_SOURCE_FIELD])) {
            $source_field = $this->rule_date_factory->getUsedDateFieldById($this->tracker, (int) $param[self::PARAMETER_SOURCE_FIELD]);
        }
        if (isset($param[self::PARAMETER_TARGET_FIELD])) {
            $target_field = $this->rule_date_factory->getUsedDateFieldById($this->tracker, (int) $param[self::PARAMETER_TARGET_FIELD]);
        }
        $comparator = $this->getComparatorFromRequestParameter($param);
        return [$source_field, $target_field, $comparator];
    }

    private function removeRules(\Tuleap\HTTPRequest $request, PFUser $user): void
    {
        $remove_rule_id = $request->get(self::PARAMETER_REMOVE_RULE);
        if (! is_numeric($remove_rule_id)) {
            return;
        }

        if ($this->rule_date_factory->deleteById($this->tracker->getId(), (int) $remove_rule_id)) {
            $this->project_history_dao->addHistory(
                $this->tracker->getProject(),
                $user,
                new \DateTimeImmutable(),
                GlobalRulesHistoryEntry::DeleteGlobalRules->value,
                '',
                [
                    $this->tracker->getId(),
                    (int) $remove_rule_id,
                ]
            );
            $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, dgettext('tuleap-tracker', 'Rule successfully deleted'));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker', 'An error occurred while deleting the rule'));
        }
    }

    private function addRule(\Tuleap\HTTPRequest $request, PFUser $user): void
    {
        if (! $this->shouldAddRule($request)) {
            return;
        }

        $add_values                                     = $request->get(self::PARAMETER_ADD_RULE);
        list($source_field, $target_field, $comparator) = $this->getFieldsAndComparatorFromRequestParameter($add_values);
        $rule                                           = $this->rule_date_factory->create(
            $source_field->getId(),
            $target_field->getId(),
            $this->tracker->getId(),
            $comparator
        );
        $this->project_history_dao->addHistory(
            $this->tracker->getProject(),
            $user,
            new \DateTimeImmutable(),
            GlobalRulesHistoryEntry::AddGlobalRules->value,
            '',
            [
                $this->tracker->getId(),
                $rule->getId(),
                $source_field->getId(),
                $comparator,
                $target_field->getId(),
            ]
        );

        $create_msg = dgettext('tuleap-tracker', 'Rule successfully created');
        $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $create_msg);
    }

    private function displayPane(Tracker_IDisplayTrackerLayout $layout): void
    {
        $title = dgettext('tuleap-tracker', 'Define global date rules');

        $assets = new IncludeAssets(__DIR__ . '/../../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin');
        $GLOBALS['Response']->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'global-rules-style'));
        $GLOBALS['Response']->addJavascriptAsset(new JavascriptAsset($assets, 'global-rules.js'));

        $this->displayHeaderBurningParrot($layout, $title);

        $date_fields = $this->rule_date_factory->getUsedDateFields($this->tracker);

        $this->template_renderer_factory
            ->getRenderer(__DIR__)
            ->renderToPage('global-rules', [
                'title' => $title,
                'url' => $this->url_query,
                'csrf_token' => \Tuleap\CSRFSynchronizerTokenPresenter::fromToken($this->token),
                'rules' => array_map(
                    static fn (Tracker_Rule_Date $rule) => [
                        'id'         => $rule->getId(),
                        'source'     => $rule->getSourceField()->getLabel(),
                        'comparator' => $rule->getComparator(),
                        'target'     => $rule->getTargetField()->getLabel(),
                    ],
                    $this->rule_date_factory->searchByTrackerId($this->tracker->getId()),
                ),
                'comparators' => array_map(
                    static fn (string $comparator) => ['value' => $comparator, 'label' => $comparator],
                    Tracker_Rule_Date::$allowed_comparators,
                ),
                'fields' => array_map(
                    static fn (DateField $field) => ['value' => $field->getId(), 'label' => $field->getLabel()],
                    $date_fields,
                ),
                'delete_name' => self::PARAMETER_REMOVE_RULE,
            ]);

        $this->displayFooterBurningParrot($layout);
    }
}
