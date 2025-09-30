<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class Docman_View_Admin_MetadataDetailsUpdateLove extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string IDENTIFIER = 'admin_display_love';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return sprintf(
            dgettext('tuleap-docman', 'Update value "%2$s" in "%1$s" property'),
            $params['md']->getName(),
            Docman_MetadataHtmlList::_getElementName($params['love'])
        );
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $metadata = $params['md'];
        assert($metadata instanceof \Docman_ListMetadata);
        $love = $params['love'];
        assert($love instanceof Docman_MetadataListOfValuesElement);

        $renderer->renderToPage('admin/property-value', [
            'property_label' => $metadata->getName(),
            'property_name'  => $metadata->getLabel(),
            'id'             => $love->getId(),
            'name'           => $love->getName(),
            'description'    => $love->getDescription(),
            'form_url'       => DocmanViewURLBuilder::buildUrl(
                $params['default_url'],
                ['action' => 'admin_update_love'],
                false
            ),
            'back_url'       => DocmanViewURLBuilder::buildUrl(
                $params['default_url'],
                [
                    'action' => \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                    'md' => $metadata->getLabel(),
                ],
                false
            ),
            'ranks' => (new \Tuleap\Docman\View\Admin\ValueRanksBuilder())->getRanks($metadata),
            'csrf'  => \Docman_View_Admin_Metadata::getCSRFToken((int) $params['group_id']),
        ]);
    }
}
