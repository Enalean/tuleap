<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use ArrayIterator;
use Codendi_HTMLPurifier;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_MetadataHtmlList;
use Docman_MetadataListOfValuesElementFactory;
use Project;
use ProjectManager;

class MetadataComparator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public Project $srcGo;
    public Project $dstGo;

    public function __construct(int $srcGroupId, int $dstGroupId)
    {
        $pm          = ProjectManager::instance();
        $this->srcGo = $pm->getProject($srcGroupId);
        $this->dstGo = $pm->getProject($dstGroupId);
    }

    /**
     * For a five object iterator, return an array of object indexed by
     * $func applied on object.
     */
    public function getArrayFromIterator(ArrayIterator $iter, string $func): array
    {
        $a = [];
        while ($iter->valid()) {
            $e              = $iter->current();
            $a[$e->$func()] = $e;
            $iter->next();
        }
        return $a;
    }

    public function checkMdDifferences(Docman_Metadata $srcMd, Docman_Metadata $dstMd): array
    {
        $diffArray = [];
        if (! $dstMd->sameDescription($srcMd)) {
            $diffArray[] = dgettext('tuleap-docman', 'Description: <strong><em>new text</em></strong>');
        }
        if (! $dstMd->sameIsEmptyAllowed($srcMd)) {
            $diffArray[] = sprintf(dgettext('tuleap-docman', 'Allow empty value: <strong>%1$s</strong>'), $this->getEnabledDisabledText((bool) $srcMd->getIsEmptyAllowed()));
        }
        if (! $dstMd->sameIsMultipleValuesAllowed($srcMd)) {
            $diffArray[] = sprintf(dgettext('tuleap-docman', 'Allow multiple selection: <strong>%1$s</strong>'), $this->getEnabledDisabledText((bool) $srcMd->getIsMultipleValuesAllowed()));
        }
        if (! $dstMd->sameUseIt($srcMd)) {
            $diffArray[] = sprintf(dgettext('tuleap-docman', 'Usage: <strong>%1$s</strong>'), $this->getEnabledDisabledText((bool) $srcMd->getUseIt()));
        }
        return $diffArray;
    }

    private function getEnabledDisabledText(bool $is_enabled): string
    {
        if ($is_enabled) {
            return dgettext('tuleap-docman', 'Enabled');
        }

        return dgettext('tuleap-docman', 'Disabled');
    }

    /**
     *
     * Same algo used in Docman_View_ItemDetailsSectionPaste::_checkLoveToImport
     */
    private function getLoveCompareTable(Docman_Metadata $srcMd, Docman_Metadata $dstMd, array $mdMap, bool &$sthToImport): string
    {
        $html = '';

        if ($srcMd->getLabel() == 'status') {
            // No differences possible with status.
            return $html;
        }

        // Get list of ListOfValues elements from dst project
        $srcLoveFactory = new Docman_MetadataListOfValuesElementFactory($srcMd->getId());
        $srcLoveIter    = $srcLoveFactory->getIteratorByFieldId($srcMd->getId(), $srcMd->getLabel(), true);

        // Get list of ListOfValues elements from dst project
        $dstLoveFactory = new Docman_MetadataListOfValuesElementFactory($dstMd->getId());
        $dstLoveIter    = $dstLoveFactory->getIteratorByFieldId($dstMd->getId(), $dstMd->getLabel(), true);
        $dstLoveArray   = $this->getArrayFromIterator($dstLoveIter, 'getId');

        $purifier = Codendi_HTMLPurifier::instance();

        // Keep a trace of matching love
        $matchingLove = [];
        while ($srcLoveIter->valid()) {
            $srcLove  = $srcLoveIter->current();
            $rowStyle = 'missing';

            // Compute the differences
            $dstLove = false;
            if (isset($mdMap['love'][$srcLove->getId()])) {
                $dstLove                         = $dstLoveArray[$mdMap['love'][$srcLove->getId()]];
                $matchingLove[$dstLove->getId()] = true;
                $rowStyle                        = 'equals';
            } else {
                $sthToImport = true;
            }

            $html .= '<tr>';

            // Name
            $html .= '<td></td>';
            $html .= '<td>' . Docman_MetadataHtmlList::_getElementName($srcLove) . '</td>';

            // Presence in source project
            $html .= '<td><i class="fa-solid fa-check"></i></td>';

            // Presence in destination project
            $html .= '<td>';
            switch ($rowStyle) {
                case 'equals':
                    $html .= '<i class="fa-solid fa-check"></i>';
                    break;
            }
            $html .= '</td>';

            // Differences
            $html .= '<td>';
            switch ($rowStyle) {
                case 'missing':
                    $html .= dgettext('tuleap-docman', 'Doesn\'t exist');
            }
            $html .= '</td>';

            // Action
            $html .= '<td>';
            switch ($rowStyle) {
                case 'missing':
                    $html .= sprintf(dgettext('tuleap-docman', 'Will create value <strong>%1$s</strong>'), $purifier->purify($srcLove->getName()));
            }
            $html .= '</td>';

            $html .= '</tr>';

            $srcLoveIter->next();
        }

        // Append to the table the list of values elements in the dst project
        // that where not present in the src project.
        foreach ($dstLoveArray as $love) {
            if (! isset($matchingLove[$love->getId()])) {
                $html .= '<tr>';
                // Name
                $html .= '<td>&nbsp;</td>';
                $html .= '<td>' . $purifier->purify($love->getName()) . '</td>';
                // Presence in source project
                $html .= '<td></td>';
                // Presence in destination project
                $html .= '<td><i class="fa-solid fa-check"></i></td>';
                // Differences
                $html .= '<td></td>';
                // Action
                $html .= '<td></td>';
                $html .= '</tr>';
            }
        }

        return $html;
    }

    public function getMetadataCompareTable(bool &$sthToImport): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';

        // True if there is sth to import in dst project.
        $sthToImport = false;

        // For source project, only get the 'Used' metadata.
        $srcMdFactory = new Docman_MetadataFactory($this->srcGo->getGroupId());
        $srcMdIter    = $srcMdFactory->getMetadataForGroup(true);

        // For destination (current) project, get all metadata.
        $dstMdFactory = new Docman_MetadataFactory($this->dstGo->getGroupId());
        $dstMdIter    = $dstMdFactory->getMetadataForGroup();
        $dstMdArray   = $this->getArrayFromIterator($dstMdIter, 'getLabel');

        // Get mapping between the 2 definitions
        $mdMap = [];
        $srcMdFactory->getMetadataMapping($this->dstGo->getGroupId(), $mdMap);

        $html .= '<p>' .
                    sprintf(dgettext('tuleap-docman', 'The table below highlight the differences between %1$s and %2$s properties.'), $purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName()))
                    . '<br>'
                    . sprintf(dgettext('tuleap-docman', 'If there are differences, you can click on "Import" button at the bottom of the page. The properties of %1$s will be modified match what is defined in %2$s.'), $purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName()))
                    . '</p>';
        $html .= '<p>
                    <div class="tlp-alert-info">
                        <p class="tlp-alert-title">' . dgettext('tuleap-docman', 'Note') . '</p>' .
                        sprintf(dgettext('tuleap-docman', 'this operation delete neither properties nor values in %1$s and %2$s won\'t be modified.'), $purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName())) . '
                    </div>
                </p>';

        // Table
        $html .= "<table  class='tlp-table'>";
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th colspan="2">' . dgettext('tuleap-docman', 'Property') . '</th>';
        $html .= '<th>' . $purifier->purify($this->srcGo->getPublicName()) . '</th>';
        $html .= '<th>' . $purifier->purify($this->dstGo->getPublicName()) . '</th>';
        $html .= '<th>' . sprintf(dgettext('tuleap-docman', 'Differences<br />in %1$s vs. %2$s'), $purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName())) . '</th>';
        $html .= '<th>' . sprintf(dgettext('tuleap-docman', 'Import actions<br />in %1$s'), $purifier->purify($this->dstGo->getPublicName())) . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';

        $purifier = Codendi_HTMLPurifier::instance();

        // Keep a trace of metadata that matched in the dst metadata list.
        $matchingMd = [];
        $srcMdIter->rewind();
        while ($srcMdIter->valid()) {
            $srcMd = $srcMdIter->current();
            $dstMd = null;

            // Compute the differences between the 2 projects
            $dstMdStatus = 'missing';
            $dstMdLabel  = '';
            if (Docman_MetadataFactory::isRealMetadata($srcMd->getLabel())) {
                if (isset($mdMap['md'][$srcMd->getId()])) {
                    $dstMdLabel = $srcMdFactory->getLabelFromId($mdMap['md'][$srcMd->getId()]);
                }
            } else {
                $dstMdLabel = $srcMd->getLabel();
            }

            if (isset($dstMdArray[$dstMdLabel])) {
                $dstMd = $dstMdArray[$dstMdLabel];
                if ($dstMd instanceof Docman_Metadata) {
                    $matchingMd[$dstMdLabel] = true;
                    $dstMdStatus             = 'equivalent';
                    if ($dstMd->equals($srcMd)) {
                        $dstMdStatus = 'equals';
                    } else {
                        $sthToImport = true;
                    }
                } else {
                    $sthToImport = true;
                }
            } else {
                // The metadata is not in the metadata map list, check if it's
                // not a name conflict
                $dstMdi = $dstMdFactory->findByName($srcMd->getName());
                if ($dstMdi->count() == 1) {
                    $dstMdStatus = 'conflict';
                } else {
                    $sthToImport = true;
                }
            }

            $purified_property_name = $purifier->purify($srcMd->getName());

            // Display result
            $html .= '<tr>';

            // Property
            $html .= '<td colspan="2">';
            $html .= $purified_property_name;
            $html .= '</td>';

            // Presence in source project
            $html .= '<td>';
            $html .= '<i class="fa-solid fa-check"></i>';
            $html .= '</td>';

            // Presence in destination project
            $html .= '<td>';
            switch ($dstMdStatus) {
                case 'equals':
                case 'equivalent':
                    $html .= '<i class="fa-solid fa-check"></i>';
                    break;
            }
            $html .= '</td>';

            // Differences
            $html .= '<td>';
            switch ($dstMdStatus) {
                case 'equivalent':
                    $html .= dgettext('tuleap-docman', 'Settings differ');
                    break;
                case 'missing':
                    $html .= dgettext('tuleap-docman', 'Doesn\'t exist');
                    break;
                case 'conflict':
                    $html .= dgettext('tuleap-docman', 'Name conflict');
                    break;
            }
            $html .= '</td>';

            // Action
            $html .= '<td>';
            switch ($dstMdStatus) {
                case 'equals':
                    // Nothing to do
                    break;
                case 'equivalent':
                    if (! $dstMd instanceof Docman_Metadata) {
                        break;
                    }
                    $diffArray = $this->checkMdDifferences($srcMd, $dstMd);
                    $diffStr   = '<ul>';
                    foreach ($diffArray as $diff) {
                        $diff_purified = $purifier->purify($diff, CODENDI_PURIFIER_FULL);
                        $diffStr      .= "<li>$diff_purified</li>";
                    }
                    $diffStr .= '</ul>';

                    $html .= sprintf(dgettext('tuleap-docman', 'Will override <strong>%1$s</strong> settings in %2$s: %3$s'), $purified_property_name, $purifier->purify($this->dstGo->getPublicName()), $diffStr);
                    break;
                case 'missing':
                    $html .= sprintf(dgettext('tuleap-docman', 'Will create property <strong>%1$s</strong> with the same settings and values'), $purified_property_name);
                    break;
                case 'conflict':
                    $html .= dgettext('tuleap-docman', 'A property with the same name but a different type exists in destination project. Will be skiped.');
                    break;
            }
            $html .= '</td>';

            $html .= '</tr>';

            // List of values
            if ($srcMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                if ($dstMd !== null && $dstMd !== false) {
                    $html .= $this->getLoveCompareTable($srcMd, $dstMd, $mdMap, $sthToImport);
                }
            }

            unset($dstMd);
            $srcMdIter->next();
        }

        // Append to the table the metadata in the dst project that where not
        // present in the src project.
        foreach ($dstMdArray as $md) {
            if (! isset($matchingMd[$md->getLabel()])) {
                $html .= '<tr>';

                // Name
                $html         .= '<td colspan="2">';
                $purified_name = $purifier->purify($md->getName());
                $html         .= $purified_name;
                $html         .= '</td>';

                // Presence in source project
                $html .= '<td></td>';

                // Presence in destination project
                $html .= '<td>';
                $html .= '<i class="fa-solid fa-check"></i>';
                $html .= '</td>';

                // Differences
                $html .= '<td></td>';

                // Action
                $html .= '<td></td>';

                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';

        return $html;
    }
}
