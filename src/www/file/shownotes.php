<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDisplay;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\ReleasePermissionManager;
use Tuleap\FRS\UploadedLinkPresentersBuilder;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\FRS\UploadedLinksTablePresenter;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/file_utils.php';

$release_id = (int) $request->getValidated('release_id', 'uint', 0);
if ($release_id === 0) {
    exit_error($GLOBALS['Language']->getText('file_shownotes', 'not_found_err'), $GLOBALS['Language']->getText('file_shownotes', 'release_not_found'));
}

$frsrf   = new FRSReleaseFactory();
$user    = UserManager::instance()->getCurrentUser();
$release = $frsrf->getFRSReleaseFromDb($release_id);

$permission_manager         = FRSPermissionManager::build();
$release_permission_manager = new ReleasePermissionManager($frsrf);
if (
    $release === null ||
    $release_permission_manager->canUserSeeRelease($user, $release) === false
) {
    exit_error($Language->getText('file_shownotes', 'not_found_err'), $Language->getText('file_shownotes', 'release_not_found'));
}

$group_id = $release->getGroupID();
$GLOBALS['Response']->addJavascriptAsset(new JavascriptViteAsset(
    new IncludeViteAssets(
        __DIR__ . '/../../scripts/frs/frontend-assets',
        '/assets/core/frs',
    ),
    'src/frs.ts',
));
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$service = $project->getService(Service::FILE);
if (! ($service instanceof ServiceFile)) {
    exit_error(
        $GLOBALS['Language']->getText(
            'project_service',
            'service_not_used',
            $GLOBALS['Language']->getText('project_admin_editservice', 'service_file_lbl_key')
        )
    );
}
$breadcrumbs = new BreadCrumbCollection();
$breadcrumbs->addBreadCrumb(new BreadCrumb(
    new BreadCrumbLink($release->getPackage()->getName(), '/file/' . $release->getGroupID() . '/package/' . $release->getPackageID()),
));
$service->displayFRSHeader($project, $release->getName(), $breadcrumbs);

$hp = Codendi_HTMLPurifier::instance();

$url_package = '/file/' . urlencode((string) $release->getGroupID()) . '/package/' . urlencode((string) $release->getPackageID());

echo '<h1 class="project-administration-title">' . $hp->purify($release->getName()) . '</h1>';
echo '<div class="tlp-framed">';
echo '<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="fa-regular fa-file-lines tlp-pane-title-icon" aria-hidden="true"></i>
                ' . $hp->purify(_('Release details')) . '
            </h1>
        </div>
        <section class="tlp-pane-section">
            <div class="tlp-property">
                <label class="tlp-label">
                ' . $hp->purify(_('Package')) . '
                </label>
                <p>
                    <a href="' . $url_package . '">' . $hp->purify($release->getPackage()->getName()) . '</a>
                </p>
            </div>

                <div class="tlp-property">
                    <label class="tlp-label">
                        ' . $hp->purify($Language->getText('file_shownotes', 'notes')) . '
                    </label>';
if ($release->getNotes() === '') {
    echo '<p class="tlp-property-empty">' . _('Empty') . '</p>';
} elseif ($release->isPreformatted()) {
    echo '<pre>';
    echo $hp->purify($release->getNotes(), Codendi_HTMLPurifier::CONFIG_BASIC_NOBR, $group_id);
    echo '</pre>';
} else {
    echo '<blocquote>';
    echo $hp->purify($release->getNotes(), Codendi_HTMLPurifier::CONFIG_BASIC, $group_id);
    echo '</blocquote>';
}
echo '</div>';

echo '<div class="tlp-property">
    <label class="tlp-label">
        ' . $Language->getText('file_shownotes', 'changes') . '
    </label>';
if ($release->getChanges() === '') {
    echo '<p class="tlp-property-empty">' . _('Empty') . '</p>';
} elseif ($release->isPreformatted()) {
    echo '<pre>';
    echo $hp->purify($release->getChanges(), Codendi_HTMLPurifier::CONFIG_BASIC_NOBR, $group_id);
    echo '</pre>';
} else {
    echo '<blocquote>';
    echo $hp->purify($release->getChanges(), Codendi_HTMLPurifier::CONFIG_BASIC, $group_id);
    echo '</blocquote>';
}
echo '</div>';

$crossref_fact = new CrossReferenceFactory($release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
$crossref_fact->fetchDatas();
if ($crossref_fact->getNbReferences() > 0) {
    echo '<b> ' . $Language->getText('cross_ref_fact_include', 'references') . '</b>';
    $crossref_fact->DisplayCrossRefs();
}
echo '</section>';

$license_agreement_display = new LicenseAgreementDisplay(
    $hp,
    TemplateRendererFactory::build(),
    new LicenseAgreementFactory(
        new LicenseAgreementDao()
    ),
);

echo '<section class="tlp-pane-section">
    <div class="frs-release-files-container">
        <table class="tlp-table frs-release-files-table">
            <thead>
                <tr>
                    <th class="frs-release-file-name-column">
                        ' . $hp->purify($Language->getText('file_admin_editreleases', 'filename')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'size')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'd_l')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'arch')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'type')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'date')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'md5sum')) . '
                    </th>
                    <th>
                        ' . $hp->purify($Language->getText('file_showfiles', 'user')) . '
                    </th>
                </tr>
            </thead>';
$factory = new FRSFileFactory();
$files   = $factory->getFRSFileInfoListByReleaseFromDb($release->getReleaseID());
if (! $files) {
    echo '<tbody class="frs-release-files-table-tbody-empty">
        <tr>
            <td class="tlp-table-cell-empty" colspan="8">' . $hp->purify(_('No files are part of this release')) . '</td>
        </tr>
    </tbody>';
} else {
    echo '<tbody class="frs-release-files-table-tbody-files">';
    $filetypes = [];
    /** @psalm-suppress DeprecatedFunction */
    $res_filetype = db_query('select * from frs_filetype');
    foreach ($res_filetype as $resrow) {
        $filetypes[$resrow['type_id']] = $resrow['name'];
    }

    $processors = [];
    /** @psalm-suppress DeprecatedFunction */
    $res_processor = db_query('select * from frs_processor');
    foreach ($res_processor as $resrow) {
        $processors[$resrow['processor_id']] = $resrow['name'];
    }

    foreach ($files as $file) {
        $size_precision = 0;
        if ($file['file_size'] < 1024) {
            $size_precision = 2;
        }

        $owner = UserManager::instance()->getUserById($file['user_id']);

        echo '<tr>';
        echo '<td class="frs-release-file-name-column">';
        echo $license_agreement_display->getDownloadLink($release->getPackage(), (int) $file['file_id'], basename($file['filename']));
        if ($file['comment'] !== '') {
            echo '<p class="frs-release-file-comment">' . $hp->purify($file['comment'], CODENDI_PURIFIER_BASIC, $group_id) . '</p>';
        }
        echo '</td>';
        echo '<td>' . $hp->purify(FRSFile::convertBytesToKbytes($file['file_size'], $size_precision)) . '</td>';
        echo '<td>' . $hp->purify($file['downloads'] ?: '0') . '</td>';
        echo '<td>' . $hp->purify($processors[$file['processor']] ?? '') . '</td>';
        echo '<td>' . $hp->purify($filetypes[$file['type']] ?? '') . '</td>';
        echo '<td>' . $hp->purify(format_date('Y-m-d', $file['release_time'])) . '</td>';
        echo '<td>' . $hp->purify($file['computed_md5'] ?? '') . '</td>';
        echo '<td>' . $hp->purify($owner ? $owner->getRealName() : '') . '</td>';
        echo '</tr>';
    }
}
echo '
            </tbody>
        </table>
    </div>
</section>';
echo $license_agreement_display->getModals($release->getProject());

$uploaded_links_retriever         = new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
$uploaded_link_presenters_builder = new UploadedLinkPresentersBuilder();
$uploaded_links                   = $uploaded_links_retriever->getLinksForRelease($release);
if (count($uploaded_links) > 0) {
    $link_presenters = $uploaded_link_presenters_builder->build($uploaded_links);

    echo '<section class="tlp-pane-section">';
    TemplateRendererFactory::build()
        ->getRenderer(__DIR__ . '/../../templates/frs/')
        ->renderToPage(
            'uploaded-links',
            new UploadedLinksTablePresenter($link_presenters),
        );
    echo '</section>';
}

echo '</div>
</section>';

file_utils_footer([]);
