<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

final class PortfolioTemplate implements CategorisedTemplate
{
    public const NAME = 'program_management_portfolio';

    private const PORTFOLIO_XML = __DIR__ . '/../../resources/templates/portfolio_template.xml';

    private string $title;
    private string $description;
    private ?string $xml_path = null;
    private ?bool $available  = null;

    private TemplateCategorySAFe $template_category;

    public function __construct(private GlyphFinder $glyph_finder, private ConsistencyChecker $consistency_checker)
    {
        $this->title       = AsciiRegisteredToUnicodeConvertor::convertSafeRegisteredBecauseOurGettextExtractionIsClumsy(
            dgettext('tuleap-program_management', 'Portfolio SAFe(R)')
        );
        $this->description = AsciiRegisteredToUnicodeConvertor::convertSafeRegisteredBecauseOurGettextExtractionIsClumsy(
            dgettext(
                'tuleap-program_management',
                'Manage sets of applications and products into a portfolio to align strategy to execution. This template has to be used with the 2 Essential SAFe(R) templates.'
            )
        );

        $this->template_category = TemplateCategorySAFe::build();
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
        if ($this->xml_path !== null) {
            return $this->xml_path;
        }

        $base_dir = \ForgeConfig::getCacheDir() . '/program_management_template/portfolio';
        if (! is_dir($base_dir) && ! mkdir($base_dir, 0755, true) && ! is_dir($base_dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $base_dir));
        }
        $this->xml_path = $base_dir . '/project.xml';

        if (! copy(self::PORTFOLIO_XML, $this->xml_path)) {
            throw new \RuntimeException('Cannot copy Portfolio XML file for tuleap template import');
        }
        return $this->xml_path;
    }

    #[\Override]
    public function isAvailable(): bool
    {
        if ($this->available === null) {
            $this->available = $this->consistency_checker->areAllServicesAvailable(
                $this->getXMLPath(),
                ['graphontrackersv5']
            );
        }
        return $this->available;
    }

    #[\Override]
    public function getTemplateCategory(): TemplateCategory
    {
        return $this->template_category;
    }
}
