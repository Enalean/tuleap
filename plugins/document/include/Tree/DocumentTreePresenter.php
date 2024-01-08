<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use CSRFSynchronizerToken;
use DocmanPlugin;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Date\DateHelper;
use Tuleap\Date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\Docman\FilenamePattern\FilenamePattern;
use Tuleap\Document\Config\FileDownloadLimits;
use Tuleap\Document\Tree\Create\NewItemAlternativeSection;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;

class DocumentTreePresenter
{
    #[FeatureFlagConfigKey('Do not display logs in document')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG_HISTORY = 'do_not_display_logs_in_document';

    /**
     * @var int
     */
    public $project_id;
    /**
     * @var int
     */
    public $root_id;
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var bool
     */
    public $user_is_admin;
    /**
     * @var bool
     */
    public $user_can_create_wiki;
    /**
     * @var bool
     */
    public $user_can_delete_item;
    /**
     * @var int
     */
    public $max_size_upload;
    /**
     * @var int
     */
    public $max_files_dragndrop;
    /**
     * @var bool
     */
    public $embedded_are_allowed;
    /**
     * @var bool
     */
    public $is_item_status_metadata_used;
    /**
     * @var bool
     */
    public $is_obsolescence_date_metadata_used;
    /**
     * @var bool
     */
    public $forbid_writers_to_update;
    /**
     * @var bool
     */
    public $forbid_writers_to_delete;
    /**
     * @var string
     */
    public $csrf_token_name;
    /**
     * @var string
     */
    public $csrf_token;
    /**
     * @var int
     */
    public $max_archive_size;
    /**
     * @var int
     */
    public $warning_threshold;

    /**
     * @var string
     */
    public $relative_dates_display;
    /**
     * @var string
     * @psalm-readonly
     */
    public $project_url;
    /**
     * @var mixed
     * @psalm-readonly
     */
    public $project_public_name;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $criteria;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $columns;

    public string $project_icon;

    public string $filename_pattern;

    public bool $is_filename_pattern_enforced;
    public bool $can_user_switch_to_old_ui;
    public bool $should_display_history_in_document;
    public string $create_new_item_alternatives;

    /**
     * @param NewItemAlternativeSection[] $create_new_item_alternatives
     */
    public function __construct(
        \Project $project,
        int $root_id,
        \PFUser $user,
        bool $embedded_are_allowed,
        bool $is_item_status_metadata_used,
        bool $is_obsolescence_date_metadata_used,
        bool $only_siteadmin_can_delete_option,
        bool $forbid_writers_to_update,
        bool $forbid_writers_to_delete,
        CSRFSynchronizerToken $csrf,
        FileDownloadLimits $file_download_limits,
        public bool $is_changelog_displayed_after_dnd,
        array $project_flags,
        array $criteria,
        array $columns,
        FilenamePattern $filename_pattern,
        public bool $should_display_source_column,
        array $create_new_item_alternatives,
    ) {
        $this->project_id                         = $project->getID();
        $this->root_id                            = $root_id;
        $this->project_name                       = $project->getUnixNameLowerCase();
        $this->project_public_name                = $project->getPublicName();
        $this->project_url                        = $project->getUrl();
        $this->user_is_admin                      = $user->isAdmin($project->getID());
        $this->user_can_create_wiki               = $project->usesWiki();
        $this->user_can_delete_item               = ! $only_siteadmin_can_delete_option || $user->isSuperUser();
        $this->max_size_upload                    = \ForgeConfig::get(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        $this->max_files_dragndrop                = \ForgeConfig::get(
            DocmanPlugin::PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING
        );
        $this->embedded_are_allowed               = $embedded_are_allowed;
        $this->is_item_status_metadata_used       = $is_item_status_metadata_used;
        $this->is_obsolescence_date_metadata_used = $is_obsolescence_date_metadata_used;
        $this->forbid_writers_to_update           = $forbid_writers_to_update;
        $this->forbid_writers_to_delete           = $forbid_writers_to_delete;
        $this->csrf_token_name                    = $csrf->getTokenName();
        $this->csrf_token                         = $csrf->getToken();
        $this->max_archive_size                   = $file_download_limits->getMaxArchiveSize();
        $this->warning_threshold                  = $file_download_limits->getWarningThreshold();
        $this->relative_dates_display             = $user->getPreference(DateHelper::PREFERENCE_NAME) ?: DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue();

        $this->privacy       = json_encode(ProjectPrivacyPresenter::fromProject($project), JSON_THROW_ON_ERROR);
        $this->project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
        $this->project_icon  = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());
        $this->criteria      = json_encode($criteria, JSON_THROW_ON_ERROR);
        $this->columns       = json_encode($columns, JSON_THROW_ON_ERROR);

        $this->filename_pattern             = $filename_pattern->getPattern();
        $this->is_filename_pattern_enforced = $filename_pattern->isEnforced();

        $this->can_user_switch_to_old_ui = SwitchToOldUi::isAllowed($user, $project);

        $this->should_display_history_in_document = (int) \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_HISTORY) === 0;

        $this->create_new_item_alternatives = json_encode($create_new_item_alternatives, JSON_THROW_ON_ERROR);
    }
}
