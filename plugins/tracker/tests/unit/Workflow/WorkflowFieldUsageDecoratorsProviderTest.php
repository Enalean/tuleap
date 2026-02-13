<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Admin\LabelDecorator;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Workflow\FieldDependencies\ProvideFieldDependenciesUsageByFieldStub;
use Tuleap\Tracker\Test\Stub\Workflow\ProvideGlobalRulesUsageByFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class WorkflowFieldUsageDecoratorsProviderTest extends TestCase
{
    private static function getExpectedGlobalRulesLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Global rules'),
            dgettext('tuleap-tracker', 'This field is used by global rules'),
            WorkflowUrlBuilder::buildGlobalRulesUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    private static function getExpectedFieldDependenciesLabelDecorator(): LabelDecorator
    {
        return LabelDecorator::buildWithUrl(
            dgettext('tuleap-tracker', 'Field dependencies'),
            dgettext('tuleap-tracker', 'This field is used by field dependencies'),
            WorkflowUrlBuilder::buildFieldDependenciesUrl(TrackerTestBuilder::aTracker()->build())
        );
    }

    /**
     * @param LabelDecorator[] $expected_label_decorators
     */
    #[DataProvider('getFields')]
    public function testWorkflowDecorators(
        bool $has_global_rules,
        bool $has_field_dependencies,
        array $expected_label_decorators,
    ): void {
        $tracker = TrackerTestBuilder::aTracker()->build();

        $field = $this->createMock(TrackerField::class);
        $field->method('getTracker')->willReturn($tracker);

        $global_rules_usage_provider = $has_global_rules
            ? ProvideGlobalRulesUsageByFieldStub::withGlobalRules()
            : ProvideGlobalRulesUsageByFieldStub::withoutGlobalRules();

        $field_dependencies_usage_provider = $has_field_dependencies
            ? ProvideFieldDependenciesUsageByFieldStub::withFieldDependencies()
            : ProvideFieldDependenciesUsageByFieldStub::withoutFieldDependencies();

        $decorators_provider = new WorkflowFieldUsageDecoratorsProvider(
            $global_rules_usage_provider,
            $field_dependencies_usage_provider
        );

        self::assertEquals($expected_label_decorators, $decorators_provider->getLabelDecorators($field));
    }

    public static function getFields(): iterable
    {
        yield 'no global rules, no field dependencies' => [false, false, []];

        yield 'global rules, no field dependencies' => [true, false, [self::getExpectedGlobalRulesLabelDecorator()]];

        yield 'no global rules, field dependencies' => [
            false,
            true,
            [self::getExpectedFieldDependenciesLabelDecorator()],
        ];

        yield 'global rules, field dependencies' => [
            true,
            true,
            [self::getExpectedGlobalRulesLabelDecorator(), self::getExpectedFieldDependenciesLabelDecorator()],
        ];
    }
}
