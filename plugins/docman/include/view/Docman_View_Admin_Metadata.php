<?php
/**
 * Copyright © Enalean, 2018-Present. All Rights Reserved.
 * Copyright © STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_Metadata extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string IDENTIFIER = 'admin_metadata';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Properties');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Define the properties of your documents.');
    }

    #[\Override]
    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset($include_assets, 'admin-properties.js')
        );
    }

    public static function getCSRFToken(int $project_id): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(
            DOCMAN_BASE_URL . '/?' . http_build_query([
                'group_id' => $project_id,
                'action'   => self::IDENTIFIER,
            ])
        );
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $renderer->renderToPage('admin/properties', [
            'available_properties' => $this->getAvailableProperties($params['mdIter'], $params['default_url']),
            'id_for_type_text'     => PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
            'id_for_type_string'   => PLUGIN_DOCMAN_METADATA_TYPE_STRING,
            'id_for_type_date'     => PLUGIN_DOCMAN_METADATA_TYPE_DATE,
            'id_for_type_list'     => PLUGIN_DOCMAN_METADATA_TYPE_LIST,
            'create_url'           => '?' . http_build_query(['group_id' => $params['group_id'], 'action' => 'admin_create_metadata']),
            'import_url'           => '?' . http_build_query(['group_id' => $params['group_id'], 'action' => \Docman_View_Admin_MetadataImport::IDENTIFIER]),
            'csrf'                 => self::getCSRFToken((int) $params['group_id']),
        ]);
    }

    private function getAvailableProperties(ArrayIterator $iterator, string $default_url): array
    {
        $properties = [];

        $custom_properties = [];

        foreach ($iterator as $property) {
            assert($property instanceof \Docman_Metadata);
            $delete_url = '';
            if (Docman_MetadataFactory::isRealMetadata($property->getLabel())) {
                $delete_url = DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    [
                        'action' => 'admin_delete_metadata',
                        'md' => $property->getLabel(),
                    ],
                    false,
                );
            }
            $property_presenter = [
                'id'          => $property->getId(),
                'name'        => $property->getName(),
                'url'         => DocmanViewURLBuilder::buildUrl(
                    $default_url,
                    [
                        'action' => \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                        'md'     => $property->getLabel(),
                    ],
                    false,
                ),
                'description' => $property->getDescription(),
                'is_required' => $property->isRequired(),
                'is_used'     => $property->isUsed(),
                'delete_url'  => $delete_url,
            ];

            if (Docman_MetadataFactory::isRealMetadata($property->getLabel())) {
                $custom_properties[] = $property_presenter;
            } else {
                $properties[] = $property_presenter;
            }
        }

        usort(
            $custom_properties,
            static fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']),
        );
        foreach ($custom_properties as $property) {
            $properties[] = $property;
        }

        return $properties;
    }
}
