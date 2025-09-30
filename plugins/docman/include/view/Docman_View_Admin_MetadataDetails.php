<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_MetadataDetails extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string IDENTIFIER = 'admin_md_details';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return sprintf(
            dgettext('tuleap-docman', '"%1$s" Property details'),
            $params['md']->getName()
        );
    }

    #[\Override]
    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset($include_assets, 'admin-properties.js')
        );
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $metadata = $params['md'];
        assert($metadata instanceof \Docman_Metadata);

        $can_change_name                       = $metadata->canChangeName();
        $can_change_description                = $metadata->canChangeDescription();
        $can_change_is_empty_allowed           = $metadata->canChangeIsEmptyAllowed();
        $can_change_is_multiple_values_allowed = $metadata->canChangeIsMultipleValuesAllowed();
        $can_change_use_it                     = ! $metadata->isRequired();

        $something_can_change = $can_change_name
            || $can_change_description
            || $can_change_is_empty_allowed
            || $can_change_is_multiple_values_allowed
            || $can_change_use_it;

        $is_list = (int) $metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_LIST;

        $renderer->renderToPage('admin/property-details', [
            'title'                             => $this->getTitle($params),
            'update_url'                        => DocmanViewURLBuilder::buildUrl($params['default_url'], ['action' => 'admin_md_details_update'], false),
            'back_url'                          => DocmanViewURLBuilder::buildUrl($params['default_url'], ['action' => \Docman_View_Admin_Metadata::IDENTIFIER], false),
            'create_value_url'                  => DocmanViewURLBuilder::buildUrl($params['default_url'], ['action' => 'admin_create_love'], false),
            'can_change_name'                   => $can_change_name,
            'can_change_description'            => $can_change_description,
            'can_change_empty_allowed'          => $can_change_is_empty_allowed,
            'can_change_multiplevalues_allowed' => $can_change_is_multiple_values_allowed,
            'can_change_use_it'                 => $can_change_use_it,
            'something_can_change'              => $something_can_change,
            'name'                              => $metadata->getName(),
            'label'                             => $metadata->getLabel(),
            'description'                       => $metadata->getDescription(),
            'empty_allowed'                     => $metadata->isEmptyAllowed(),
            'multiplevalues_allowed'            => $metadata->isMultipleValuesAllowed(),
            'use_it'                            => $metadata->isUsed(),
            'keep_history'                      => $metadata->getKeepHistory(),
            'is_list'                           => $is_list,
            'can_create_value'                  => $is_list && $metadata->getLabel() !== \Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL,
            'values'                            => $is_list ? $this->getValues($metadata, $params) : [],
            'ranks'                             => $this->getRanks($metadata),
            'csrf'                              => \Docman_View_Admin_Metadata::getCSRFToken((int) $params['group_id']),
            'type'                              => match ((int) $metadata->getType()) {
                PLUGIN_DOCMAN_METADATA_TYPE_TEXT => dgettext('tuleap-docman', 'Text'),
                PLUGIN_DOCMAN_METADATA_TYPE_STRING => dgettext('tuleap-docman', 'String'),
                PLUGIN_DOCMAN_METADATA_TYPE_DATE => dgettext('tuleap-docman', 'Date'),
                PLUGIN_DOCMAN_METADATA_TYPE_LIST => dgettext('tuleap-docman', 'List of values'),
                default => '',
            },
        ]);
    }

    private function getValues(\Docman_Metadata $metadata, array $params): array
    {
        assert($metadata instanceof \Docman_ListMetadata);

        $values = [];
        foreach ($metadata->getListOfValueIterator() as $value) {
            assert($value instanceof \Docman_MetadataListOfValuesElement);
            if ($value->getStatus() === 'D') {
                continue;
            }

            $url = DocmanViewURLBuilder::buildUrl(
                $params['default_url'],
                [
                    'action' => \Docman_View_Admin_MetadataDetailsUpdateLove::IDENTIFIER,
                    'md'     => $metadata->getLabel(),
                    'loveid' => $value->getId(),
                ],
                false,
            );

            $delete_url = DocmanViewURLBuilder::buildUrl(
                $params['default_url'],
                [
                    'action' => 'admin_delete_love',
                    'md'     => $metadata->getLabel(),
                    'loveid' => $value->getId(),
                ],
                false,
            );

            $values[] = [
                'id'          => $value->getId(),
                'is_none'     => (int) $value->getId() === 100,
                'name'        => $value->getName(),
                'description' => $value->getDescription(),
                'url'         => $url,
                'can_delete'  => $value->getStatus() === 'A',
                'delete_url'  => $delete_url,
                'status'      => match ($value->getStatus()) {
                    'A' => dgettext('tuleap-docman', 'Active'),
                    'P' => dgettext('tuleap-docman', 'Permanent'),
                    default => dgettext('tuleap-docman', 'Inactive'),
                },
            ];
        }

        return $values;
    }

    private function getRanks(\Docman_Metadata $metadata): array
    {
        if ($metadata instanceof \Docman_ListMetadata) {
            return (new \Tuleap\Docman\View\Admin\ValueRanksBuilder())->getRanks($metadata);
        }

        return [];
    }
}
