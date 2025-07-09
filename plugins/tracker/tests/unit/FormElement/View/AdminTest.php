<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\View;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_View_Admin;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class AdminTest extends TestCase
{
    public function testForSharedFieldsItDisplaysOriginalTrackerAndProjectName(): void
    {
        $admin  = $this->givenAnAdminWithOriginalProjectAndTracker('Tuleap', 'Bugs');
        $result = $admin->fetchCustomHelpForShared();
        self::assertMatchesRegularExpression('%Bugs%', $result);
        self::assertMatchesRegularExpression('%Tuleap%', $result);
        self::assertMatchesRegularExpression('%<a href="' . TRACKER_BASE_URL . '/\?tracker=101&func=admin-formElement-update-view&formElement=666"%', $result);
    }

    public function givenAnAdminWithOriginalProjectAndTracker(string $project_name, string $tracker_name): Tracker_FormElement_View_Admin
    {
        $project = ProjectTestBuilder::aProject()->withPublicName($project_name)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->withName($tracker_name)->withProject($project)->build();

        $original = StringFieldBuilder::aStringField(666)->inTracker($tracker)->build();
        $element  = StringFieldBuilder::aStringField(667)->withOriginalField($original)->build();

        return new Tracker_FormElement_View_Admin($element, []);
    }

    public function testSharedUsageShouldDisplayAllTrackershatShareMe(): void
    {
        $element = $this->givenAnElementWithManyCopies();
        $admin   = new Tracker_FormElement_View_Admin($element, []);
        $content = $admin->fetchSharedUsage();
        self::assertMatchesRegularExpression('/Canard/', $content);
        self::assertMatchesRegularExpression('/Saucisse/', $content);
    }

    private function givenAnElementWithManyCopies(): StringField
    {
        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $project = ProjectTestBuilder::aProject()->withPublicName('Project')->build();

        $element = StringFieldBuilder::aStringField(1)->build();
        $element->setFormElementFactory($factory);

        $tracker1 = TrackerTestBuilder::aTracker()->withId(123)->withName('Canard')->withProject($project)->build();
        $copy1    = StringFieldBuilder::aStringField(10)->withOriginalField($element)->inTracker($tracker1)->build();
        $copy2    = StringFieldBuilder::aStringField(20)->withOriginalField($element)->inTracker($tracker1)->build();

        $tracker3 = TrackerTestBuilder::aTracker()->withId(124)->withName('Saucisse')->withProject($project)->build();
        $copy3    = StringFieldBuilder::aStringField(30)->withOriginalField($element)->inTracker($tracker3)->build();

        $factory->method('getSharedTargets')->with($element)->willReturn([$copy1, $copy2, $copy3]);
        return $element;
    }
}
