<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\admin;

use BaseLanguage;
use BaseLanguageFactory;
use DirectoryIterator;
use HTTPRequest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use TemplateRendererFactory;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class SiteContentCustomisationController implements DispatchableWithRequest
{
    /**
     * @var BaseLanguageFactory
     */
    private $base_language_factory;
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var \TemplateRenderer
     */
    private $template_renderer;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        TemplateRendererFactory $template_renderer_factory,
        BaseLanguageFactory $base_language_factory
    ) {
        $this->base_language_factory = $base_language_factory;
        $this->admin_page_renderer = $admin_page_renderer;
        $this->template_renderer = $template_renderer_factory->getRenderer(__DIR__ . '/../../templates/admin/');
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (!$request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $title = _('Site content customisations');
        $this->admin_page_renderer->header($title);
        $customisations = array_filter(
            $this->getCustomisations(),
            static function ($custo): bool {
                return !empty($custo);
            }
        );
        $this->template_renderer->renderToPage(
            'customisations',
            [
                'title' => $title,
                'customisations' => array_values($customisations),
                'has_customisations' => !empty($customisations['en_US']) || !empty($customisations['fr_FR']),
                'json_encoded_customisations' => json_encode($customisations)
            ]
        );
        $this->admin_page_renderer->footer();
    }

    /**
     * @return array{en_US: array, fr_FR: array}
     */
    private function getCustomisations(): array
    {
        $customisations = [
            'en_US' => $this->getCustomisationsDoneForLocale('en_US'),
            'fr_FR' => $this->getCustomisationsDoneForLocale('fr_FR')
        ];

        return $customisations;
    }

    private function getCustomisationsDoneForLocale(string $locale): array
    {
        $customisations = [];
        $this->appendCustomisationsDoneForCore($locale, $customisations);
        $this->appendCustomisationsDoneForPlugins($locale, $customisations);
        if (empty($customisations)) {
            return [];
        }

        return [
            'locale' => $locale,
            'customisations' => $customisations
        ];
    }

    private function appendCustomisationsDoneForCore(string $locale, array &$customisations): void
    {
        $language = $this->base_language_factory->getBaseLanguage($locale);
        $sitecontent_path = \ForgeConfig::get('sys_custom_incdir') . '/' . $locale;
        $original_path = '/usr/share/tuleap/site-content/' . $locale;
        $this->parseSiteContentLocaleDir($original_path, $sitecontent_path, $language, $customisations);
    }

    private function appendCustomisationsDoneForPlugins(string $locale, array &$customisations): void
    {
        $language = $this->base_language_factory->getBaseLanguage($locale);
        foreach (new DirectoryIterator(\ForgeConfig::get('sys_custompluginsroot')) as $plugin_folder) {
            assert($plugin_folder instanceof DirectoryIterator);
            if ($plugin_folder->isDot() || !$plugin_folder->isDir()) {
                continue;
            }

            $sitecontent_path = $plugin_folder->getPathname() . '/site-content/' . $locale;
            if (!is_dir($sitecontent_path)) {
                continue;
            }

            $original_path = '/usr/share/tuleap/plugins/' . $plugin_folder->getFilename() . '/site-content/' . $locale;

            $this->parseSiteContentLocaleDir($original_path, $sitecontent_path, $language, $customisations);
        }
    }

    private function parseSiteContentLocaleDir(
        string $original_path,
        string $sitecontent_path,
        BaseLanguage $language,
        array &$customisations
    ): void {
        $iterator = new RecursiveDirectoryIterator(
            $sitecontent_path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );
        foreach (new RecursiveIteratorIterator($iterator) as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->getExtension() !== 'tab') {
                continue;
            }

            $this->compareFiles($language, $customisations, $file->getPathname(), str_replace($sitecontent_path, $original_path, $file->getPathname()));
        }
    }

    private function compareFiles(BaseLanguage $language, array &$customisations, string $customised_filename, string $original_filename): void
    {
        $customised_text_array = [];
        $original_text_array = [];
        $language->parseLanguageFile($customised_filename, $customised_text_array);
        $language->parseLanguageFile($original_filename, $original_text_array);

        $lines = [];
        foreach ($customised_text_array as $primary_key => $entries) {
            foreach ($entries as $secondary_key => $value) {
                $original = $original_text_array[$primary_key][$secondary_key];
                $customisation = $customised_text_array[$primary_key][$secondary_key];
                if ($original === $customisation) {
                    continue;
                }

                $lines[] = [
                    'primary' => $primary_key,
                    'secondary' => $secondary_key,
                    'customisation' => $customisation,
                    'original' => $original

                ];
            }
        }

        if (empty($lines)) {
            return;
        }

        $customisations[] = [
            'path' => $customised_filename,
            'lines' => $lines
        ];
    }
}
