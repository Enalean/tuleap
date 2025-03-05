<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate;

use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PdfTemplateDaoTest extends TestIntegrationTestCase
{
    public function testTemplatesAreRetrievedOrderedByLabel(): void
    {
        $alice = UserTestBuilder::aUser()->build();

        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory, RetrieveUserByIdStub::withUser($alice));

        self::assertCount(0, $dao->retrieveAll());

        $dao->create('the template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, new \DateTimeImmutable());
        $dao->create('a template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, new \DateTimeImmutable());

        $templates = $dao->retrieveAll();

        self::assertCount(2, $templates);
        self::assertEquals('a template', $templates[0]->label);
        self::assertEquals('the template', $templates[1]->label);
    }

    public function testTemplateDeletion(): void
    {
        $alice = UserTestBuilder::aUser()->build();

        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory, RetrieveUserByIdStub::withUser($alice));

        $the_template = $dao->create('the template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, new \DateTimeImmutable());
        $a_template   = $dao->create('a template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, new \DateTimeImmutable());

        $templates = $dao->retrieveAll();

        self::assertCount(2, $templates);

        $dao->delete($the_template->identifier);

        $templates = $dao->retrieveAll();
        self::assertCount(1, $templates);
        self::assertEquals($a_template->label, $templates[0]->label);
    }

    public function testRetrieveTemplate(): void
    {
        $alice = UserTestBuilder::aUser()->build();

        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory, RetrieveUserByIdStub::withUser($alice));

        $the_template = $dao->create('the template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, new \DateTimeImmutable());

        self::assertNull($dao->retrieveTemplate($identifier_factory->buildIdentifier()));
        self::assertEquals('the template', $dao->retrieveTemplate($the_template->identifier)?->label);
    }

    public function testUpdateTemplate(): void
    {
        $alice = UserTestBuilder::aUser()->withId(101)->build();
        $bob   = UserTestBuilder::aUser()->withId(102)->build();

        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory, RetrieveUserByIdStub::withUsers($alice, $bob));

        $last_updated_date = (new \DateTimeImmutable())->setTimestamp(123);
        $the_template      = $dao->create('the template', 'its description', 'its styles', 'its title page', 'its header', 'its footer', $alice, $last_updated_date);

        $last_updated_date  = (new \DateTimeImmutable())->setTimestamp(456);
        $submitted_template = PdfTemplateBuilder::build(
            $the_template->identifier,
            'updated label',
            'updated description',
            'updated style',
            'updated title page',
            'updated header',
            'updated footer',
            $bob,
            $last_updated_date,
        );
        $dao->update($submitted_template);

        $updated_template = $dao->retrieveTemplate($the_template->identifier);
        self::assertNotNull($updated_template);
        self::assertEquals('updated label', $updated_template->label);
        self::assertEquals('updated description', $updated_template->description);
        self::assertEquals('updated style', $updated_template->user_style);
        self::assertStringContainsString('updated style', $updated_template->style);
        self::assertEquals('updated title page', $updated_template->title_page_content);
        self::assertEquals('updated header', $updated_template->header_content);
        self::assertEquals('updated footer', $updated_template->footer_content);
        self::assertSame($bob, $updated_template->last_updated_by);
        self::assertEquals(456, $updated_template->last_updated_date->getTimestamp());
    }
}
