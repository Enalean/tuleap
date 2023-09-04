<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use CSRFSynchronizerToken;
use PFUser;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPrivacyPresenter;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\TestManagement\REST\v1\MilestoneRepresentation;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\User\REST\UserRepresentation;

#[ConfigKeyCategory('Test Management')]
class IndexPresenter
{
    #[FeatureFlagConfigKey('Order test executions by test definition ranks')]
    public const FEATURE_FLAG_ORDER_BY_TEST_DEF_RANK = 'ttm_test_exec_order_by_test_def_rank';

    /** @var int */
    public $project_id;

    /** @var int */
    public $campaign_tracker_id;

    /** @var int */
    public $test_definition_tracker_id;

    /** @var int */
    public $test_execution_tracker_id;

    /** @var int|null */
    public $issue_tracker_id;

    /** @var string */
    public $misconfigured_title;

    /** @var string */
    public $misconfigured_message;

    /** @var bool */
    public $is_properly_configured;

    /** @var string */
    public $current_user;

    /** @var string */
    public $lang;

    /** @var  string */
    public $tracker_ids;

    /** @var  array */
    public $tracker_permissions;

    /** @var string */
    public $current_milestone;
    /**
     * @var false|string
     */
    public $issue_tracker_config;
    /**
     * @var mixed
     */
    public $project_name;
    /**
     * @var string
     */
    public $project_url;
    /**
     * @var ProjectPrivacyPresenter
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var array
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var false|string
     * @psalm-readonly
     */
    public $json_encoded_project_flags;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_project_flags;
    /**
     * @var string
     * @psalm-readonly
     */
    public $ttm_admin_url = '';

    /**
     * @var string
     */
    public $csrf_token_campaign_status;

    /**
     * @psalm-readonly
     */
    public int $file_upload_max_size;
    /**
     * @psalm-readonly
     */
    public string $project_icon;
    /**
     * @psalm-readonly
     */
    public string $base_url;
    /**
     * @psalm-readonly
     */
    public string $platform_name;
    /**
     * @psalm-readonly
     */
    public string $platform_logo_url;

    public string $artifact_links_types;

    public bool $mercure_enabled;
    public readonly bool $order_by_definition_rank;

    /**
     * @param int|false                         $campaign_tracker_id
     * @param int|false                         $test_definition_tracker_id
     * @param int|false                         $test_execution_tracker_id
     * @param int|false|null                    $issue_tracker_id
     * @param MilestoneRepresentation|\stdClass $milestone_representation
     * @param TypePresenter[] $artifact_links_types
     */
    public function __construct(
        \Project $project,
        $campaign_tracker_id,
        $test_definition_tracker_id,
        $test_execution_tracker_id,
        $issue_tracker_id,
        array $issue_tracker_config,
        PFUser $current_user,
        object $milestone_representation,
        array $project_flags,
        CSRFSynchronizerToken $csrf_token,
        string $platform_name,
        string $base_url,
        string $platform_logo_url,
        array $artifact_links_types,
    ) {
        $this->lang = $this->getLanguageAbbreviation($current_user);

        $this->project_id   = $project->getID();
        $this->project_name = $project->getPublicName();
        $this->project_url  = $project->getUrl();
        $this->project_icon = EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint());

        $this->platform_name     = $platform_name;
        $this->base_url          = $base_url;
        $this->platform_logo_url = $platform_logo_url;

        $user_representation = UserRepresentation::build($current_user);
        $this->current_user  = json_encode($user_representation);
        if ($current_user->isAdmin($project->getID())) {
            $this->ttm_admin_url = TESTMANAGEMENT_BASE_URL . '/?' . http_build_query([
                'group_id' => $this->project_id,
                'action'   => 'admin',
            ]);
        }

        $this->test_definition_tracker_id = intval($test_definition_tracker_id);
        $this->test_execution_tracker_id  = intval($test_execution_tracker_id);
        $this->campaign_tracker_id        = intval($campaign_tracker_id);
        $this->issue_tracker_id           = $issue_tracker_id ? intval($issue_tracker_id) : null;
        $this->tracker_ids                = json_encode(
            [
                'definition_tracker_id' => $this->test_definition_tracker_id,
                'execution_tracker_id'  => $this->test_execution_tracker_id,
                'campaign_tracker_id'   => $this->campaign_tracker_id,
                'issue_tracker_id'      => $this->issue_tracker_id,
            ]
        );

        $this->issue_tracker_config = json_encode($issue_tracker_config);

        $this->current_milestone = json_encode($milestone_representation, JSON_THROW_ON_ERROR);

        $this->privacy                    = ProjectPrivacyPresenter::fromProject($project);
        $this->project_flags              = $project_flags;
        $this->json_encoded_project_flags = json_encode($project_flags, JSON_THROW_ON_ERROR);
        $this->has_project_flags          = count($project_flags) > 0;

        $this->csrf_token_campaign_status = $csrf_token->getToken();

        $this->file_upload_max_size = (int) \ForgeConfig::get('sys_max_size_upload');

        $this->artifact_links_types     = json_encode($artifact_links_types, JSON_THROW_ON_ERROR);
        $this->mercure_enabled          = \ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY) === "1";
        $this->order_by_definition_rank = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_ORDER_BY_TEST_DEF_RANK);
    }

    private function getLanguageAbbreviation(PFUser $current_user): string
    {
        [$lang, $country] = explode('_', $current_user->getLocale());

        return $lang;
    }
}
