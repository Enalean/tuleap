<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Templates;

use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\Registration\Template\CategorisedTemplate;
use Tuleap\Project\Registration\Template\TemplateCategory;
use Tuleap\Project\XML\ConsistencyChecker;

final class ProgramTemplate implements CategorisedTemplate
{
    public const NAME = 'program_management_program';

    private const PROGRAM_XML        = __DIR__ . '/../../resources/templates/program_template.xml';
    private const PROGRAM_CONFIG_XML = __DIR__ . '/../../resources/templates/program_management_config.xml';

    private string $title;
    private string $description;
    private ?string $xml_path = null;

    private ?bool $available = null;

    private GlyphFinder $glyph_finder;

    private ConsistencyChecker $consistency_checker;

    private TemplateCategorySAFe $template_category;

    public function __construct(GlyphFinder $glyph_finder, ConsistencyChecker $consistency_checker)
    {
        $this->title       = AsciiRegisteredToUnicodeConvertor::convertSafeRegisteredBecauseOurGettextExtractionIsClumsy(
            dgettext('tuleap-program_management', 'Essential SAFe(R) - Agile Release Train')
        );
        $this->description = AsciiRegisteredToUnicodeConvertor::convertSafeRegisteredBecauseOurGettextExtractionIsClumsy(
            dgettext(
                'tuleap-program_management',
                'Align several teams to a shared mission applying the principles of the Essential SAFe(R). Manage Agile Release Train, Program Increments and Roadmap. This template has to be used with the Tuleap template "Essential SAFe(R) - Scrum Team"'
            )
        );

        $this->glyph_finder        = $glyph_finder;
        $this->consistency_checker = $consistency_checker;
        $this->template_category   = TemplateCategorySAFe::build();
    }

    #[\Override]
    public function getId(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getTitle(): string
    {
        return $this->title;
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->description;
    }

    #[\Override]
    public function getGlyph(): Glyph
    {
        return $this->glyph_finder->get('tuleap-program-management-' . self::NAME);
    }

    #[\Override]
    public function isBuiltIn(): bool
    {
        return true;
    }

    #[\Override]
    public function getXMLPath(): string
    {
        if ($this->xml_path === null) {
            $base_dir = \ForgeConfig::getCacheDir() . '/program_management_template/program';
            if (! is_dir($base_dir) && ! mkdir($base_dir, 0755, true) && ! is_dir($base_dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $base_dir));
            }

            $this->xml_path = $base_dir . '/project.xml';
            if (! copy(self::PROGRAM_XML, $this->xml_path)) {
                throw new \RuntimeException('Can not copy Program file for tuleap template import');
            }

            $program_management_config_file = $base_dir . '/program-management-config.xml';
            if (! copy(self::PROGRAM_CONFIG_XML, $program_management_config_file)) {
                throw new \RuntimeException('Can not copy Program config file for tuleap template import');
            }
        }

        return $this->xml_path;
    }

    #[\Override]
    public function isAvailable(): bool
    {
        if ($this->available === null) {
            $this->available = $this->consistency_checker->areAllServicesAvailable($this->getXMLPath(), []);
        }

        return $this->available;
    }

    #[\Override]
    public function getTemplateCategory(): TemplateCategory
    {
        return $this->template_category;
    }
}
