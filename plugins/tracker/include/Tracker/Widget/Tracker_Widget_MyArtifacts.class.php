<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Artifact\MyArtifactsCollection;

/**
 * Widget_MyArtifacts
 *
 * Artifact assigned to or submitted by this person
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Widget_MyArtifacts extends Widget
{
    public const ID        = 'plugin_tracker_myartifacts';
    public const PREF_SHOW = 'plugin_tracker_myartifacts_show';

    protected $artifact_show;

    public function __construct()
    {
        parent::__construct(self::ID);
        $this->artifact_show = user_get_preference(self::PREF_SHOW);
        if ($this->artifact_show === false) {
            $this->artifact_show = 'AS';
            user_set_preference(self::PREF_SHOW, $this->artifact_show);
        }
    }

    public function getTitle()
    {
        switch (strtolower($this->artifact_show)) {
            case 'a':
                $abbreviation = dgettext('tuleap-tracker', 'A');
                break;
            case 's':
                $abbreviation = dgettext('tuleap-tracker', 'S');
                break;
            case 'as':
            default:
                $abbreviation = dgettext('tuleap-tracker', 'AS');
                break;
        }

        return dgettext('tuleap-tracker', 'My Artifacts') . ' [' . $abbreviation . ']';
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        $vShow = new Valid_WhiteList('show', ['A', 'S', 'AS']);
        $vShow->required();
        if (! $request->exist('cancel')) {
            if ($request->valid($vShow)) {
                switch ($request->get('show')) {
                    case 'A':
                        $this->artifact_show = 'A';
                        break;
                    case 'S':
                        $this->artifact_show = 'S';
                        break;
                    default:
                        $this->artifact_show = 'AS';
                }
                user_set_preference(self::PREF_SHOW, $this->artifact_show);
            }
        }
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $selected_a  = $this->artifact_show === 'A'  ? 'selected="selected"' : '';
        $selected_s  = $this->artifact_show === 'S'  ? 'selected="selected"' : '';
        $selected_as = $this->artifact_show === 'AS' ? 'selected="selected"' : '';

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="show-' . $widget_id . '">
                    ' . $purifier->purify(dgettext('tuleap-tracker', 'Display artifacts:')) . '
                </label>
                <select type="text"
                    class="tlp-select"
                    id="show-' . $widget_id . '"
                    name="show"
                >
                    <option value="A" ' . $selected_a . '>
                        ' . $purifier->purify(dgettext('tuleap-tracker', 'assigned to me [A]')) . '
                    </option>
                    <option value="S" ' . $selected_s . '>
                        ' . $purifier->purify(dgettext('tuleap-tracker', 'submitted by me [S]')) . '
                    </option>
                    <option value="AS" ' . $selected_as . '>
                        ' . $purifier->purify(dgettext('tuleap-tracker', 'assigned to or submitted by me [AS]')) . '
                    </option>
                </select>
            </div>
            ';
    }

    public function isAjax()
    {
        return true;
    }

    public function getContent()
    {
        $html_my_artifacts = '';

        $taf  = Tracker_ArtifactFactory::instance();
        $um   = UserManager::instance();
        $user = $um->getCurrentUser();
        switch ($this->artifact_show) {
            case 'A':
                $my_artifacts = $taf->getUserOpenArtifactsAssignedTo($user);
                break;
            case 'S':
                $my_artifacts = $taf->getUserOpenArtifactsSubmittedBy($user);
                break;
            default:
                $my_artifacts = $taf->getUserOpenArtifactsSubmittedByOrAssignedTo($user);
                break;
        }

        if (count($my_artifacts) > 0) {
            $html_my_artifacts .= $this->_display_artifacts($my_artifacts);
        } else {
            $html_my_artifacts .= '<section class="empty-state-pane">
                <div class="empty-state-illustration">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="140" height="192" viewBox="0 0 140 192">
                        <g fill="none" fill-rule="evenodd">
                            <g transform="rotate(1 67.375 106.312)">
                                <rect fill="#FFF" width="122.5" height="168.875" x="6.125" y="21.875"/>
                                <rect class="empty-artifacts-sheet-border" width="121.5" height="167.875" x="6.625" y="22.375" stroke="#000"/>
                            </g>
                            <g>
                                <rect fill="#FFF" width="122.5" height="168.875" x="11.375" y="19.25"/>
                                <rect class="empty-artifacts-sheet-border" width="121.5" height="167.875" x="11.875" y="19.75" stroke="#000"/>
                            </g>
                            <g transform="rotate(-2 72.032 86.646)">
                                <rect fill="#FFF" width="129.5" height="168.875" x="7.282" y="2.208"/>
                                <rect class="empty-artifacts-sheet-border" width="128.5" height="167.875" x="7.782" y="2.708" stroke="#000"/>
                            </g>
                            <g transform="rotate(2 73.5 87.938)">
                                <rect fill="#FFF" width="122.5" height="168.875" x="12.25" y="3.5"/>
                                <rect class="empty-artifacts-sheet-border" width="121.5" height="167.875" x="12.75" y="4" stroke="#000"/>
                            </g>
                            <path class="empty-artifacts-cover" fill="#000" d="M130,30.6363636 L130,10 C130,8.8954305 129.104569,8 128,8 L2,8 L2,8 C0.8954305,8 -1.3527075e-16,8.8954305 0,10 L0,10 L0,183 C1.3527075e-16,184.104569 0.8954305,185 2,185 L128,185 C129.104569,185 130,184.104569 130,183 L130,67.3636364 L136,63 L136,35 L130,30.6363636 Z"/>
                            <rect class="empty-artifacts-sticker" width="64.75" height="28.875" x="34.125" y="35.875" fill="#FFF" rx="2"/>
                            <path class="empty-artifacts-icon" fill="#181818" d="M61.7543089,56.38425 C61.7543089,55.9145982 61.4548208,55.5606577 60.9987821,55.451753 L61.6454041,54.6689999 L61.6454041,54.0700237 L59.3788236,54.0700237 L59.3788236,55.104619 L60.1003177,55.104619 L60.1003177,54.743872 C60.3181272,54.743872 60.5427433,54.7302589 60.7605529,54.7302589 L60.7605529,54.7370654 C60.4678713,55.0025208 60.2432552,55.3496547 59.9982195,55.6627559 L60.1751897,56.0439226 C60.4542582,56.023503 60.8898773,56.0439226 60.8898773,56.4250893 C60.8898773,56.6973512 60.638035,56.8130626 60.3998058,56.8130626 C60.141157,56.8130626 59.8620885,56.6769316 59.6783117,56.5067679 L59.2903385,57.1057441 C59.5966332,57.4120388 60.0322522,57.5549763 60.4610648,57.5549763 C61.1689458,57.5549763 61.7543089,57.1329703 61.7543089,56.38425 Z M61.7916733,51.4152333 L61.0619026,51.4152333 L61.0619026,51.8322451 L60.1792276,51.8322451 C60.2000782,51.2901297 61.7082709,51.0607732 61.7082709,50.0321441 C61.7082709,49.3440746 61.1522552,48.9618138 60.5058869,48.9618138 C59.9776719,48.9618138 59.5050585,49.2328715 59.2757021,49.7124351 L59.8664688,50.1224967 C59.9846221,49.9278912 60.1861778,49.7193853 60.4294347,49.7193853 C60.6587912,49.7193853 60.8047453,49.8444888 60.8047453,50.0807955 C60.8047453,50.664612 59.2340009,50.8592175 59.2340009,52.1450039 C59.2340009,52.2701075 59.2548515,52.395211 59.2757021,52.5203146 L61.7916733,52.5203146 L61.7916733,51.4152333 Z M76,55.25 C76,55.109375 75.859375,55 75.7,55 L64.3,55 C64.13125,55 64,55.109375 64,55.25 L64,56.75 C64,56.8828125 64.13125,57 64.3,57 L75.7,57 C75.859375,57 76,56.8828125 76,56.75 L76,55.25 Z M61.8579844,46.8018488 L61.0863823,46.8018488 L61.0863823,43.9154856 L60.3290692,43.9154856 L59.3574222,44.8228325 L59.8646791,45.3658117 C60.0004239,45.2443558 60.1433131,45.1371889 60.2219022,44.9800107 L60.2361912,44.9800107 L60.2361912,45.0657442 C60.2361912,45.6444458 60.2290467,46.2231473 60.2290467,46.8018488 L59.4645891,46.8018488 L59.4645891,47.5091507 L61.8579844,47.5091507 L61.8579844,46.8018488 Z M76,50.25 C76,50.109375 75.859375,50 75.7,50 L64.3,50 C64.13125,50 64,50.109375 64,50.25 L64,51.75 C64,51.8828125 64.13125,52 64.3,52 L75.7,52 C75.859375,52 76,51.8828125 76,51.75 L76,50.25 Z M76,45.25 C76,45.1171875 75.859375,45 75.7,45 L64.3,45 C64.13125,45 64,45.1171875 64,45.25 L64,46.75 C64,46.8828125 64.13125,47 64.3,47 L75.7,47 C75.859375,47 76,46.8828125 76,46.75 L76,45.25 Z"/>
                        </g>
                    </svg>
                </div>
                <p class="empty-state-text">' .
                dgettext('tuleap-tracker', 'You don\'t have any artifacts yet')
                . '</p>
                </section>';
        }

        return $html_my_artifacts;
    }

    public function _display_artifacts(MyArtifactsCollection $my_artifacts) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $hp = Codendi_HTMLPurifier::instance();

        $html_my_artifacts = '';

        foreach ($my_artifacts->getTrackers() as $tracker) {
            $artifacts_in_tracker_count = $my_artifacts->getArtifactsInTrackerCount($tracker);
            if ($artifacts_in_tracker_count > 0) {
                $div_id              = 'plugin_tracker_my_artifacts_tracker_' . $tracker->getId();
                $classname           = Toggler::getClassname($div_id);
                $group_id            = $tracker->getGroupId();
                $project             = ProjectManager::instance()->getProject($group_id);
                $project_and_tracker = $project->getPublicName() . ' - ' . $tracker->getName();

                $html_my_artifacts .= '<div data-test="dashboard-my-artifacts-content">';
                $html_my_artifacts .= '<div class="' . $classname . ' tracker-widget-artifacts-toggler" id="' . $div_id . '">';
                $html_my_artifacts .= '<a href="/plugins/tracker/?tracker=' . $tracker->getId() . '" class="tracker-widget-artifacts">';
                $html_my_artifacts .= '<strong>' . $hp->purify($project_and_tracker, CODENDI_PURIFIER_CONVERT_HTML) . '</strong>';
                $html_my_artifacts .= '</a>';
                $html_my_artifacts .= ' [' . $artifacts_in_tracker_count . ']';
                $html_my_artifacts .= ' </div>';
                $html_my_artifacts .= '<ul class="plugin_tracker_my_artifacts_list tracker-widget-artifacts-list">';
                foreach ($my_artifacts->getArtifactsInTracker($tracker) as $artifact) {
                    $html_my_artifacts .=  '<li>';
                    $html_my_artifacts .=  $artifact->fetchWidget($tracker->getItemName());
                    $html_my_artifacts .=  '</li>';
                }
                $html_my_artifacts .= '</ul>';
                $html_my_artifacts .= '</div>';
            }
        }
        return $html_my_artifacts;
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request  = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type, $dashboard_id);
        if ($request->exist('hide_item_id') || $request->exist('hide_artifact')) {
            $ajax_url .= '&hide_item_id=' . $request->get('hide_item_id') . '&hide_artifact=' . $request->get('hide_artifact');
        }
        return $ajax_url;
    }

    public function getCategory()
    {
        return dgettext('tuleap-tracker', 'Trackers');
    }

    public function getDescription()
    {
        return dgettext('tuleap-tracker', 'List artifacts you have submitted or assigned to you, by project.');
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../frontend-assets',
            '/assets/trackers'
        );
        return new CssAssetCollection([new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($include_assets, 'tracker-bp')]);
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => RelativeDatesAssetsRetriever::retrieveAssetsUrl(), 'unique-name' => 'tlp-relative-dates'],
        ];
    }
}
