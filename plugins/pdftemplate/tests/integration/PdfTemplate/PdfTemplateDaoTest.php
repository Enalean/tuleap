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
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class PdfTemplateDaoTest extends TestIntegrationTestCase
{
    public function testTemplatesAreRetrievedOrderedByLabel(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory);

        self::assertCount(0, $dao->retrieveAll());

        $dao->create('the template', 'its description', 'its styles');
        $dao->create('a template', 'its description', 'its styles');

        $templates = $dao->retrieveAll();

        self::assertCount(2, $templates);
        self::assertEquals('a template', $templates[0]->label);
        self::assertEquals('the template', $templates[1]->label);
    }

    public function testTemplateDeletion(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory);

        $the_template = $dao->create('the template', 'its description', 'its styles');
        $a_template   = $dao->create('a template', 'its description', 'its styles');

        $templates = $dao->retrieveAll();

        self::assertCount(2, $templates);

        $dao->delete($the_template->identifier);

        $templates = $dao->retrieveAll();
        self::assertCount(1, $templates);
        self::assertEquals($a_template->label, $templates[0]->label);
    }

    public function testRetrieveTemplate(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory);

        $the_template = $dao->create('the template', 'its description', 'its styles');

        self::assertNull($dao->retrieveTemplate($identifier_factory->buildIdentifier()));
        self::assertEquals('the template', $dao->retrieveTemplate($the_template->identifier)?->label);
    }

    public function testUpdateTemplate(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory());
        $dao                = new PdfTemplateDao($identifier_factory);

        $the_template = $dao->create('the template', 'its description', 'its styles');

        $submitted_template = new PdfTemplate(
            $the_template->identifier,
            'updated label',
            'updated description',
            'updated style',
        );
        $dao->update($submitted_template);

        $updated_template = $dao->retrieveTemplate($the_template->identifier);
        self::assertNotNull($updated_template);
        self::assertEquals('updated label', $updated_template->label);
        self::assertEquals('updated description', $updated_template->description);
        self::assertEquals('updated style', $updated_template->style);
    }
}
