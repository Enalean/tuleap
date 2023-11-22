<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Date\DateHelper;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;

require_once __DIR__ . '/../../../src/www/my/my_utils.php';

/**
 * Docman_Widget_MyDocman
 */
class Docman_Widget_MyDocman extends Widget
{
    public $pluginPath;

    public function __construct($pluginPath)
    {
        parent::__construct('plugin_docman_mydocman');
        $this->pluginPath = $pluginPath;
    }

    public function getTitle()
    {
        return dgettext('tuleap-docman', 'Documents under review');
    }

    public function getContent()
    {
        $html  = '';
        $html .= '<script type="text/javascript">';
        $html .= "
        function plugin_docman_approval_toggle(what, save) {
            if ($(what).visible()) {
                $(what+'_icon').src = '" . util_get_dir_image_theme() . "pointer_right.png';
                $(what).hide();
                if (save) {
                    new Ajax.Request('/plugins/docman/?action='+what+'&hide=1');
                }
            } else {
                $(what+'_icon').src = '" . util_get_dir_image_theme() . "pointer_down.png';
                $(what).show();
                if (save) {
                    new Ajax.Request('/plugins/docman/?action='+what+'&hide=0');
                }
            }
        }
        </script>";
        $html .= $this->_getReviews(true);
        $html .= $this->_getReviews(false);

        return $html;
    }

    private function _getReviews($reviewer = true)
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';

        $content_html_id = 'plugin_docman_approval_' . ($reviewer ? 'reviewer' : 'requester');
        $html           .= '<div style="font-weight:bold;" class="my-document-under-review">';

        if ($reviewer) {
            $html .= dgettext('tuleap-docman', 'Reviewer');
        } else {
            $html .= dgettext('tuleap-docman', 'Requester');
        }
        $html .= '</div>';
        $html .= '<div id="' . $content_html_id . '">';

        $um   = UserManager::instance();
        $user = $um->getCurrentUser();

        if ($reviewer) {
            $reviewsArray = Docman_ApprovalTableReviewerFactory::getAllPendingReviewsForUser($user->getId());
        } else {
            $reviewsArray = Docman_ApprovalTableReviewerFactory::getAllApprovalTableForUser($user->getId());
        }

        if (count($reviewsArray) > 0) {
            $request = HTTPRequest::instance();
            // Get hide arguments
            $hideItemId   = (int) $request->get('hide_item_id');
            $hideApproval = null;
            if ($request->exist('hide_plugin_docman_approval')) {
                $hideApproval = (int) $request->get('hide_plugin_docman_approval');
            }

            $prevGroupId = -1;
            $hideNow     = false;
            $i           = 0;

            $html .= '<table class="tlp-table">';
            foreach ($reviewsArray as $review) {
                if ($review['group_id'] != $prevGroupId) {
                    list($hideNow, $count_diff, $hideUrl) =
                        my_hide_url(
                            'plugin_docman_approval',
                            $review['group_id'],
                            $hideItemId,
                            1,
                            $hideApproval,
                            $request->get('dashboard_id')
                        );
                    $docmanUrl                            = $this->pluginPath . '/?group_id=' . $review['group_id'];
                    $docmanHref                           = '<a href="' . $docmanUrl . '">' . $hp->purify($review['group']) . '</a>';

                    if ($reviewer) {
                        $colspan = 2;
                    } else {
                        $colspan = 3;
                    }
                    $html .= '<tr class="boxitem"><td colspan="' . $colspan . '">';
                    $html .= '<strong>' . $hideUrl . $docmanHref . '</strong></td></tr>';
                    $i     = 0;
                }

                if (! $hideNow) {
                    $html .= '<tr class="' . util_get_alt_row_color($i++) . '">';
                    // Document
                    $html .= '<td align="left">';
                    $html .= '<a href="' . $review['url'] . '">' . $hp->purify($review['title'], CODENDI_PURIFIER_CONVERT_HTML) . '</a>';
                    $html .= '</td>';

                    // For requester, precise the status
                    if (! $reviewer) {
                        $html .= '<td align="right">';
                        $html .= $review['status'];
                        $html .= '</td>';
                    }

                    // Date
                    $html .= '<td align="right">';
                    $html .=  DateHelper::formatForLanguage($GLOBALS['Language'], $review['date'], true);
                    $html .= '</td>';
                }

                $html .= '</tr>';

                $prevGroupId = $review['group_id'];
            }
            $html .= '</table>';
        } else {
            if ($reviewer) {
                $html .= dgettext('tuleap-docman', 'No document under review.');
            } else {
                $html .= dgettext('tuleap-docman', 'No review requested.');
            }
        }
        $html .= '</div>';
        if (user_get_preference('hide_plugin_docman_approval_' . ($reviewer ? 'reviewer' : 'requester'))) {
            $html .= '<script type="text/javascript">';
            $html .= "document.observe('dom:loaded', function()
                {
                    plugin_docman_approval_toggle('$content_html_id', false);
                }
            );
            </script>";
        }
        return $html;
    }

    public function isAjax()
    {
        return true;
    }

    public function getCategory()
    {
        return dgettext('tuleap-docman', 'Document manager');
    }

    public function getDescription()
    {
        return dgettext('tuleap-docman', 'List the documents under review.');
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request  = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type, $dashboard_id);
        if ($request->exist('hide_plugin_docman_approval') || $request->exist('hide_item_id')) {
            $ajax_url .= '&hide_plugin_docman_approval=' . $request->get('hide_plugin_docman_approval') . '&hide_item_id=' . $request->get('hide_item_id');
        }

        return $ajax_url;
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        $theme_include_assets = new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/docman'
        );
        return new CssAssetCollection([new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($theme_include_assets, 'burningparrot-style')]);
    }
}
