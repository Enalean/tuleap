<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use ForgeConfig;
use HTTPRequest;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElementFactory;
use TrackerManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRF\CSRFSessionKeyStorageStub;
use Tuleap\Test\Stubs\CSRF\CSRFSigningKeyStorageStub;
use Tuleap\Tracker\FormElement\StaticField\Separator\SeparatorStaticField;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementTest extends TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testGetOriginalProjectAndOriginalTracker(): void
    {
        $project  = ProjectTestBuilder::aProject()->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(888)->withProject($project)->build();
        $original = SelectboxFieldBuilder::aSelectboxField(687)->inTracker($tracker)->build();

        $element = $this->givenAFormElementWithIdAndOriginalField(null, $original);

        self::assertEquals($tracker, $element->getOriginalTracker());
        self::assertEquals($project, $element->getOriginalProject());
    }

    public function testGetOriginalFieldIdShouldReturnTheFieldId(): void
    {
        $original = $this->givenAFormElementWithIdAndOriginalField(112, null);
        $element  = $this->givenAFormElementWithIdAndOriginalField(null, $original);
        self::assertEquals(112, $element->getOriginalFieldId());
    }

    public function testGetOriginalFieldIdShouldReturn0IfNoOriginalField(): void
    {
        $element = $this->givenAFormElementWithIdAndOriginalField(null, null);
        self::assertEquals(0, $element->getOriginalFieldId());
    }

    protected function givenAFormElementWithIdAndOriginalField(?int $id, ?TrackerFormElement $original_field): SeparatorStaticField
    {
        return new SeparatorStaticField(
            $id,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $original_field
        );
    }

    public function testDisplayUpdateFormShouldDisplayAForm(): void
    {
        $form_element = $this->givenAFormElementWithIdAndOriginalField(null, null);
        $factory      = $this->createMock(Tracker_FormElementFactory::class);
        $factory->method('getUsedFormElementForTracker')->willReturn([]);
        $factory->method('getSharedTargets');
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(888);
        $tracker->method('displayAdminFormElements')->willReturn('');
        $tracker->method('displayAdminFormElementsHeader')->willReturn('');
        $tracker->method('displayFooter')->willReturn('');

        $form_element->setTracker($tracker);
        $form_element->setFormElementFactory($factory);

        $content = $this->whenIDisplayAdminFormElement($form_element);

        self::assertMatchesRegularExpression('%Update%', $content);
        self::assertMatchesRegularExpression('%</form>%', $content);
    }

    private function whenIDisplayAdminFormElement(TrackerFormElement $form_element): string
    {
        $GLOBALS['HTML'] = new TestLayout(new LayoutInspector());

        ob_start();
        $form_element->displayAdminFormElement(
            $this->createMock(TrackerManager::class),
            new HTTPRequest(),
            new \CSRFSynchronizerToken('update_form', 'token', new CSRFSigningKeyStorageStub(), new CSRFSessionKeyStorageStub()),
        );
        $content = ob_get_contents();
        ob_end_clean();

        self::assertIsString($content);
        return $content;
    }
}
