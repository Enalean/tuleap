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

namespace Tuleap\Tracker\FormElement\View\Admin;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Container\Column\ColumnContainer;
use Tuleap\Tracker\FormElement\Field\ArtifactId\ArtifactIdField;
use Tuleap\Tracker\FormElement\Field\CrossReferences\CrossReferencesField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\LastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\List\MultiSelectboxField;
use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\SubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\SubmittedOnField;
use Tuleap\Tracker\FormElement\StaticField\LineBreak\LineBreakStaticField;
use Tuleap\Tracker\FormElement\StaticField\RichText\RichTextStaticField;
use Tuleap\Tracker\FormElement\StaticField\Separator\SeparatorStaticField;
use Tuleap\Tracker\FormElement\View\Admin\Field\ArtifactIdFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\CrossReferencesFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\LastUpdateDateFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\ListFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\MultiSelectboxFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\PermissionsOnArtifactFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\SelectboxFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\SubmittedByFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\Field\SubmittedOnFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\StaticField\LineBreakStaticFieldAdminView;
use Tuleap\Tracker\FormElement\View\Admin\StaticField\SeparatorStaticFieldAdminView;

#[DisableReturnValueGenerationForTestDoubles]
final class AdminVisitorTest extends TestCase
{
    public function testVisitAStringShouldFallbackOnField(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new StringField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(FieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitAColumnShouldFallbackOnContainer(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new ColumnContainer(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(ContainerAdminView::class, $visitor->getAdmin());
    }

    public function testVisitAnOpenListShouldFallbackOnList(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new OpenListField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(ListFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitARichTextShouldFallbackStaticField(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new RichTextStaticField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(StaticFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitSelectbox(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new SelectboxField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(SelectboxFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitArtifactId(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new ArtifactIdField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(ArtifactIdFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitCrossReferences(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new CrossReferencesField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(CrossReferencesFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitLastUpdateDate(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new LastUpdateDateField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(LastUpdateDateFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitMultiSelectbox(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new MultiSelectboxField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(MultiSelectboxFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitPermissionsOnArtifact(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new PermissionsOnArtifactField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(PermissionsOnArtifactFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitSubmittedBy(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new SubmittedByField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(SubmittedByFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitSubmittedOn(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new SubmittedOnField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(SubmittedOnFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitLineBreak(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new LineBreakStaticField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(LineBreakStaticFieldAdminView::class, $visitor->getAdmin());
    }

    public function testVisitSeparator(): void
    {
        $visitor     = new VisitorAdminView([]);
        $formElement = new SeparatorStaticField(null, null, null, null, null, null, null, null, null, null, null, null);
        $formElement->accept($visitor);
        self::assertInstanceOf(SeparatorStaticFieldAdminView::class, $visitor->getAdmin());
    }
}
