<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Request;

use ArtifactTypeFactory;
use Codendi_HTMLPurifier;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\RS256;
use EventManager;
use FastRoute;
use ForgeConfig;
use FRSFileFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use MailManager;
use ProjectHistoryDao;
use ProjectManager;
use ReferenceManager;
use ServiceManager;
use SVN_TokenHandler;
use TemplateRendererFactory;
use ThemeVariant;
use TroveCatDao;
use TroveCatFactory;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\HelpDropdown\AdminReleaseNoteLinkController;
use Tuleap\admin\HelpDropdown\PostAdminReleaseNoteLinkController;
use Tuleap\Admin\ProjectCreation\ProjectCategoriesDisplayController;
use Tuleap\admin\ProjectCreation\ProjectFields\ProjectFieldsDao;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigDisplayController;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigUpdateController;
use Tuleap\Admin\ProjectCreation\ProjetFields\ProjectFieldsDisplayController;
use Tuleap\Admin\ProjectCreation\ProjetFields\ProjectFieldsUpdateController;
use Tuleap\Admin\ProjectCreation\ProjetFields\ProjectsFieldDescriptionUpdater;
use Tuleap\Admin\ProjectCreation\WebhooksDisplayController;
use Tuleap\Admin\ProjectCreation\WebhooksUpdateController;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Admin\ProjectWidgetsConfigurationDisplayController;
use Tuleap\Admin\ProjectWidgetsConfigurationPOSTDisableController;
use Tuleap\Admin\ProjectWidgetsConfigurationPOSTEnableController;
use Tuleap\admin\SiteContentCustomisationController;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Config\FeatureFlagController;
use Tuleap\ContentSecurityPolicy\CSPViolationReportToController;
use Tuleap\Core\RSS\News\LatestNewsController;
use Tuleap\Core\RSS\Project\LatestProjectController;
use Tuleap\Core\RSS\Project\LatestProjectDao;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Date\Admin\RelativeDatesDisplayController;
use Tuleap\Date\Admin\RelativeDatesDisplaySaveController;
use Tuleap\Date\SelectedDateDisplayPreferenceValidator;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Error\FrontendErrorCollectorController;
use Tuleap\Error\PermissionDeniedPrivateProjectMailSender;
use Tuleap\Error\PermissionDeniedRestrictedMemberMailSender;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\FRS\FRSFileDownloadController;
use Tuleap\FRS\FRSFileDownloadOldURLRedirectionController;
use Tuleap\FRS\LicenseAgreement\Admin\AddLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\EditLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\ListLicenseAgreementsController;
use Tuleap\FRS\LicenseAgreement\Admin\SaveLicenseAgreementController;
use Tuleap\FRS\LicenseAgreement\Admin\SetDefaultLicenseAgreementController;
use Tuleap\HelpDropdown\HelpMenuOpenedController;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\AccountCreationFeedback;
use Tuleap\InviteBuddy\AccountCreationFeedbackEmailNotifier;
use Tuleap\InviteBuddy\Admin\InviteBuddyAdminController;
use Tuleap\InviteBuddy\Admin\InviteBuddyAdminUpdateController;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationEmailNotifier;
use Tuleap\InviteBuddy\InvitationInstrumentation;
use Tuleap\InviteBuddy\InvitationLimitChecker;
use Tuleap\InviteBuddy\InvitationSender;
use Tuleap\InviteBuddy\InvitationSenderGateKeeper;
use Tuleap\InviteBuddy\InvitationToOneRecipientWithoutVerificationSender;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\PrefixTokenInvitation;
use Tuleap\InviteBuddy\ProjectMemberAccordingToInvitationAdder;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeCoreAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\Markdown\CommonMarkInterpreterController;
use Tuleap\Markdown\EnhancedCodeBlockExtension;
use Tuleap\News\NewsDao;
use Tuleap\News\PermissionsPerGroup;
use Tuleap\OAuth2ServerCore\OAuth2ServerRoutes;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Platform\Banner\PlatformBannerAdministrationController;
use Tuleap\Platform\RobotsTxtController;
use Tuleap\Project\Admin\Categories;
use Tuleap\Project\Admin\Export\ProjectExportController;
use Tuleap\Project\Admin\Export\ProjectXmlExportController;
use Tuleap\Project\Admin\Invitations\CSRFSynchronizerTokenProvider;
use Tuleap\Project\Admin\Invitations\ManageProjectInvitationsController;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersController;
use Tuleap\Project\Admin\ProjectMembers\UserCanManageProjectMembersChecker;
use Tuleap\Project\Admin\ProjectUGroup\MemberAdditionController;
use Tuleap\Project\Admin\ProjectUGroup\MemberRemovalController;
use Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership\ActivationController;
use Tuleap\Project\Admin\Reference\Browse\ReferenceAdministrationBrowsingRenderer;
use Tuleap\Project\Admin\Reference\Browse\ReferenceAdministrationBrowseController;
use Tuleap\Project\Admin\Reference\Browse\ReferencePatternPresenterBuilder;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware;
use Tuleap\Project\Admin\Routing\RejectNonProjectMembersAdministratorMiddleware;
use Tuleap\Project\Banner\BannerAdministrationController;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Home;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectAdministratorsIncludingDelegationDAO;
use Tuleap\Project\ProjectBackground\ProjectBackgroundAdministrationController;
use Tuleap\Project\Registration\ProjectRegistrationController;
use Tuleap\Project\Registration\ProjectRegistrationPresenterBuilder;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\CustomProjectArchive;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\Upload\EnqueueProjectCreationFromArchive;
use Tuleap\Project\Registration\Template\Upload\ProjectArchiveOngoingUploadDao;
use Tuleap\Project\Registration\Template\Upload\Tus\ProjectFileBeingUploadedInformationProvider;
use Tuleap\Project\Registration\Template\Upload\Tus\ProjectFileDataStore;
use Tuleap\Project\Registration\Template\Upload\Tus\ProjectFileUploadCanceler;
use Tuleap\Project\Registration\Template\Upload\Tus\ProjectFileUploadFinisher;
use Tuleap\Project\Registration\Template\Upload\UploadedArchiveForProjectController;
use Tuleap\Project\Registration\Template\Upload\UploadedArchiveForProjectDao;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Routing\ProjectRetrieverMiddleware;
use Tuleap\Project\Service\AddController;
use Tuleap\Project\Service\DeleteController;
use Tuleap\Project\Service\EditController;
use Tuleap\Project\Service\ServicesPresenterBuilder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Queue\EnqueueTask;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\REST\UserManager;
use Tuleap\ServerHostname;
use Tuleap\SVNCore\AccessControl\SVNProjectAccessRouteDefinition;
use Tuleap\Trove\TroveCatListController;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;
use Tuleap\Upload\FileUploadController;
use Tuleap\Upload\UploadPathAllocator;
use Tuleap\User\AccessKey\AccessKeyCreationController;
use Tuleap\User\AccessKey\AccessKeyRevocationController;
use Tuleap\User\Account\AccessKeyPresenterBuilder;
use Tuleap\User\Account\Appearance\AppearancePresenterBuilder;
use Tuleap\User\Account\Appearance\LanguagePresenterBuilder;
use Tuleap\User\Account\Appearance\ThemeColorPresenterBuilder;
use Tuleap\User\Account\ChangeAvatarController;
use Tuleap\User\Account\ConfirmNewEmailController;
use Tuleap\User\Account\DisplayAccountInformationController;
use Tuleap\User\Account\DisplayAppearanceController;
use Tuleap\User\Account\DisplayEditionController;
use Tuleap\User\Account\DisplayKeysTokensController;
use Tuleap\User\Account\DisplayNotificationsController;
use Tuleap\User\Account\DisplaySecurityController;
use Tuleap\User\Account\LogoutController;
use Tuleap\User\Account\LostPassword\DisplayLostPasswordController;
use Tuleap\User\Account\LostPassword\DisplayResetPasswordController;
use Tuleap\User\Account\LostPassword\LostPasswordController;
use Tuleap\User\Account\LostPassword\ResetPasswordController;
use Tuleap\User\Account\LostPassword\UserFromConfirmationHashRetriever;
use Tuleap\User\Account\Register\AccountRegister;
use Tuleap\User\Account\Register\AfterSuccessfulUserRegistration;
use Tuleap\User\Account\Register\ConfirmationHashEmailSender;
use Tuleap\User\Account\Register\ConfirmationPageDisplayer;
use Tuleap\User\Account\Register\DisplayAdminRegisterFormController;
use Tuleap\User\Account\Register\DisplayRegisterFormController;
use Tuleap\User\Account\Register\InvitationToEmailRequestExtractor;
use Tuleap\User\Account\Register\NewUserByAdminEmailSender;
use Tuleap\User\Account\Register\ProcessAdminRegisterFormController;
use Tuleap\User\Account\Register\ProcessRegisterFormController;
use Tuleap\User\Account\Register\RegisterFormDisplayer;
use Tuleap\User\Account\Register\RegisterFormPresenterBuilder;
use Tuleap\User\Account\Register\RegisterFormProcessor;
use Tuleap\User\Account\RemoveFromProjectController;
use Tuleap\User\Account\SVNTokensPresenterBuilder;
use Tuleap\User\Account\UpdateAccountInformationController;
use Tuleap\User\Account\UpdateAppearancePreferences;
use Tuleap\User\Account\UpdateEditionController;
use Tuleap\User\Account\UpdateNotificationsPreferences;
use Tuleap\User\Account\UpdatePasswordController;
use Tuleap\User\Account\UpdateSessionPreferencesController;
use Tuleap\User\Account\UserAvatarSaver;
use Tuleap\User\Account\UserWellKnownChangePasswordController;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\Password\Reset\LostPasswordDAO;
use Tuleap\User\Password\Reset\ResetTokenSerializer;
use Tuleap\User\Password\Reset\Verifier;
use Tuleap\User\Profile\AvatarController;
use Tuleap\User\Profile\AvatarGenerator;
use Tuleap\User\Profile\ProfileAsJSONForTooltipController;
use Tuleap\User\Profile\ProfileController;
use Tuleap\User\Profile\ProfilePresenterBuilder;
use Tuleap\User\SessionManager;
use Tuleap\User\Settings\UserSettingsController;
use Tuleap\User\Settings\UserSettingsUpdateController;
use Tuleap\User\SSHKey\SSHKeyCreateController;
use Tuleap\User\SSHKey\SSHKeyDeleteController;
use Tuleap\User\SVNToken\SVNTokenRevokeController;
use Tuleap\WebAuthn\Authentication\WebAuthnAuthentication;
use Tuleap\WebAuthn\Challenge\WebAuthnChallengeDao;
use Tuleap\WebAuthn\Controllers\DeleteSourceController;
use Tuleap\WebAuthn\Controllers\PostAuthenticationChallengeController;
use Tuleap\WebAuthn\Controllers\PostRegistrationChallengeController;
use Tuleap\WebAuthn\Controllers\PostRegistrationController;
use Tuleap\WebAuthn\Controllers\PostSwitchPasswordlessAuthenticationController;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSourceDao;
use Tuleap\WebAuthn\WebAuthnRegistration;
use Tuleap\Widget\WidgetFactory;
use UGroupManager;
use URLVerification;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManager;
use Webauthn\CeremonyStep\CheckExtensions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;

class RouteCollector
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public static function getSlash()
    {
        return new SiteHomepageController(
            new \Admin_Homepage_Dao(),
            \ProjectManager::instance(),
            \UserManager::instance(),
            EventManager::instance()
        );
    }

    public static function getOrPostProjectHome()
    {
        return new Home(\ProjectManager::instance());
    }

    public static function getAdminSiteContentCustomisation()
    {
        return new SiteContentCustomisationController(
            new AdminPageRenderer(),
            TemplateRendererFactory::build(),
            new \BaseLanguageFactory()
        );
    }

    public static function getAdminPasswordPolicy()
    {
        return new PasswordPolicyDisplayController(
            new AdminPageRenderer(),
            TemplateRendererFactory::build(),
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO())
        );
    }

    public static function postAdminPasswordPolicy()
    {
        return new PasswordPolicyUpdateController(
            new PasswordConfigurationSaver(new PasswordConfigurationDAO())
        );
    }

    public static function getUserSettings(): UserSettingsController
    {
        return new UserSettingsController(
            new AdminPageRenderer(),
            TemplateRendererFactory::build(),
        );
    }

    public static function postUserSettings(): UserSettingsUpdateController
    {
        return new UserSettingsUpdateController(new ConfigDao());
    }

    public static function getProjectCreationModeration()
    {
        return new ProjectCreationModerationDisplayController();
    }

    public static function postProjectCreationModeration()
    {
        return new ProjectCreationModerationUpdateController();
    }

    public static function getProjectCreationTemplates()
    {
        return new ProjectTemplatesController();
    }

    public static function getProjectCreationWebhooks()
    {
        return new WebhooksDisplayController();
    }

    public static function postProjectCreationWebhooks()
    {
        return new WebhooksUpdateController();
    }

    public static function getProjectCreationFields()
    {
        return new ProjectFieldsDisplayController();
    }

    public static function postProjectCreationFields(): ProjectFieldsUpdateController
    {
        return new ProjectFieldsUpdateController(
            new ProjectsFieldDescriptionUpdater(
                new \Project_CustomDescription_CustomDescriptionDao(),
                new ConfigDao()
            ),
            new ProjectFieldsDao()
        );
    }

    public static function getProjectCreationCategories()
    {
        return new ProjectCategoriesDisplayController();
    }

    public static function postProjectCreationCategories()
    {
        return new TroveCatListController();
    }

    public static function getProjectCreationVisibility()
    {
        return new ProjectVisibilityConfigDisplayController();
    }

    public static function getProjectConfigurationWidgets(): ProjectWidgetsConfigurationDisplayController
    {
        return new ProjectWidgetsConfigurationDisplayController(
            self::getWidgetFactory()
        );
    }

    public static function getProjectConfigurationPOSTWidgetsEnable(): ProjectWidgetsConfigurationPOSTEnableController
    {
        return new ProjectWidgetsConfigurationPOSTEnableController(
            self::getWidgetFactory(),
            new DisabledProjectWidgetsDao()
        );
    }

    public static function getProjectConfigurationPOSTWidgetsDisable(): ProjectWidgetsConfigurationPOSTDisableController
    {
        return new ProjectWidgetsConfigurationPOSTDisableController(
            self::getWidgetFactory(),
            new DisabledProjectWidgetsDao()
        );
    }

    private static function getWidgetFactory(): WidgetFactory
    {
        return new WidgetFactory(
            \UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );
    }

    public static function postProjectCreationVisibility()
    {
        return new ProjectVisibilityConfigUpdateController(
            new ProjectVisibilityConfigManager(
                new ConfigDao()
            )
        );
    }

    public static function getAccountToken(): DispatchableWithRequest
    {
        return new DisplayKeysTokensController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayKeysTokensController::getCSRFToken(),
            AccessKeyPresenterBuilder::build(),
            SVNTokensPresenterBuilder::build(),
        );
    }

    public static function getEditionController(): DispatchableWithRequest
    {
        return new DisplayEditionController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayEditionController::getCSRFToken()
        );
    }

    public static function postEditionController(): DispatchableWithRequest
    {
        return new UpdateEditionController(
            DisplayEditionController::getCSRFToken()
        );
    }

    public static function getAppearanceController(): DispatchableWithRequest
    {
        return new DisplayAppearanceController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayAppearanceController::getCSRFToken(),
            new AppearancePresenterBuilder(
                new LanguagePresenterBuilder(new \BaseLanguageFactory(), new LocaleSwitcher()),
                new ThemeColorPresenterBuilder(new \ThemeVariant())
            )
        );
    }

    public static function postAppearanceController(): DispatchableWithRequest
    {
        return new UpdateAppearancePreferences(
            DisplayAppearanceController::getCSRFToken(),
            \UserManager::instance(),
            $GLOBALS['Language'],
            new ThemeVariant(),
            new SelectedDateDisplayPreferenceValidator()
        );
    }

    public static function getAccountSecurity(): DispatchableWithRequest
    {
        return DisplaySecurityController::buildSelf();
    }

    public static function postAccountSecuritySession(): DispatchableWithRequest
    {
        return new UpdateSessionPreferencesController(
            DisplaySecurityController::getCSRFToken(),
            \UserManager::instance(),
        );
    }

    public static function postAccountSecurityPassword(): DispatchableWithRequest
    {
        return UpdatePasswordController::buildSelf();
    }

    public static function getWellKnownUrlChangePassword(): UserWellKnownChangePasswordController
    {
        return new UserWellKnownChangePasswordController(
            \UserManager::instance(),
            EventManager::instance(),
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    public static function postAccountSSHKeyCreate(): DispatchableWithRequest
    {
        $source_dao                    = new WebAuthnCredentialSourceDao();
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $logger            = \BackendLogger::getDefaultLogger();
        $credential_loader = new PublicKeyCredentialLoader(
            new AttestationObjectLoader($attestation_statement_manager)
        );
        $credential_loader->setLogger($logger);
        $assertion_validator = new AuthenticatorAssertionResponseValidator(
            $source_dao,
            null,
            new ExtensionOutputCheckerHandler(),
            Manager::create()
                ->add(
                    Ed25519::create(),
                    RS256::create(),
                    ES256::create()
                )
        );
        $assertion_validator->setLogger($logger);

        return new SSHKeyCreateController(
            DisplayKeysTokensController::getCSRFToken(),
            \UserManager::instance(),
            new WebAuthnAuthentication(
                $source_dao,
                new WebAuthnChallengeDao(),
                new PublicKeyCredentialRpEntity(
                    \ForgeConfig::get(ConfigurationVariables::NAME),
                    ServerHostname::rawHostname()
                ),
                $credential_loader,
                $assertion_validator,
            ),
        );
    }

    public static function postAccountSSHKeyDelete(): DispatchableWithRequest
    {
        return new SSHKeyDeleteController(DisplayKeysTokensController::getCSRFToken(), \UserManager::instance());
    }

    public static function postAccountAccessKeyCreate(): DispatchableWithRequest
    {
        $source_dao                    = new WebAuthnCredentialSourceDao();
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $logger            = \BackendLogger::getDefaultLogger();
        $credential_loader = new PublicKeyCredentialLoader(
            new AttestationObjectLoader($attestation_statement_manager)
        );
        $credential_loader->setLogger($logger);
        $assertion_validator = new AuthenticatorAssertionResponseValidator(
            $source_dao,
            null,
            new ExtensionOutputCheckerHandler(),
            Manager::create()
                ->add(
                    Ed25519::create(),
                    RS256::create(),
                    ES256::create()
                )
        );
        $assertion_validator->setLogger($logger);

        return new AccessKeyCreationController(
            DisplayKeysTokensController::getCSRFToken(),
            new WebAuthnAuthentication(
                $source_dao,
                new WebAuthnChallengeDao(),
                new PublicKeyCredentialRpEntity(
                    \ForgeConfig::get(ConfigurationVariables::NAME),
                    ServerHostname::rawHostname()
                ),
                $credential_loader,
                $assertion_validator,
            ),
        );
    }

    public static function postAccountAccessKeyRevoke(): DispatchableWithRequest
    {
        return new AccessKeyRevocationController(DisplayKeysTokensController::getCSRFToken());
    }

    public static function postAccountSVNTokenRevoke(): DispatchableWithRequest
    {
        return new SVNTokenRevokeController(DisplayKeysTokensController::getCSRFToken(), SVN_TokenHandler::build());
    }

    public static function getAccountPreferences(): DispatchableWithRequest
    {
        return DisplayAccountInformationController::buildSelf();
    }

    public static function postAccountInformation(): DispatchableWithRequest
    {
        return UpdateAccountInformationController::buildSelf();
    }

    public static function getEmailConfirm(): DispatchableWithRequest
    {
        return ConfirmNewEmailController::buildSelf();
    }

    public static function getAccountNotifications(): DispatchableWithRequest
    {
        return new DisplayNotificationsController(
            EventManager::instance(),
            TemplateRendererFactory::build(),
            DisplayNotificationsController::getCSRFToken(),
            new MailManager(),
        );
    }

    public static function postAccountNotifications(): DispatchableWithRequest
    {
        return new UpdateNotificationsPreferences(
            DisplayNotificationsController::getCSRFToken(),
            \UserManager::instance(),
            EventManager::instance(),
        );
    }

    public static function postAccountAvatar()
    {
        $user_manager = \UserManager::instance();

        $avatar_hash_storage = new AvatarHashDao();

        return new ChangeAvatarController(
            DisplayAccountInformationController::getCSRFToken(),
            new UserAvatarSaver($user_manager, $avatar_hash_storage, new ComputeAvatarHash()),
            $user_manager,
            $avatar_hash_storage,
        );
    }

    public static function postLogoutAccount(): LogoutController
    {
        return new LogoutController(\UserManager::instance());
    }

    public static function postAccountRemoveFromProject(): RemoveFromProjectController
    {
        $user_manager    = \UserManager::instance();
        $project_manager = ProjectManager::instance();
        return new RemoveFromProjectController(
            HTTPFactoryBuilder::responseFactory(),
            new \CSRFSynchronizerToken(RemoveFromProjectController::CSRF_TOKEN_NAME),
            $user_manager,
            $project_manager,
            new \Tuleap\Project\UserRemover(
                $project_manager,
                EventManager::instance(),
                new ArtifactTypeFactory(false),
                new \Tuleap\Project\UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                new UGroupManager(),
                new UserPermissionsDao(),
            ),
            new ProjectAdministratorsIncludingDelegationDAO(),
            new SapiEmitter()
        );
    }

    public static function getUsersName()
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();

        return new ProfileController(
            new ProfilePresenterBuilder(EventManager::instance(), Codendi_HTMLPurifier::instance()),
            new ProfileAsJSONForTooltipController(
                new JSONResponseBuilder(
                    $response_factory,
                    HTTPFactoryBuilder::streamFactory()
                ),
                new SapiEmitter(),
                $response_factory,
                TemplateRendererFactory::build()
            ),
        );
    }

    public static function getUsersNameAvatar()
    {
        $storage             = new AvatarHashDao();
        $compute_avatar_hash = new ComputeAvatarHash();

        return new AvatarController(
            new AvatarGenerator($storage, $compute_avatar_hash),
            $storage,
            $compute_avatar_hash,
        );
    }

    public static function getUsersNameAvatarHash()
    {
        $storage             = new AvatarHashDao();
        $compute_avatar_hash = new ComputeAvatarHash();

        return new AvatarController(
            new AvatarGenerator($storage, $compute_avatar_hash),
            $storage,
            $compute_avatar_hash,
            ['expires' => 'never'],
        );
    }

    public static function postJoinPrivateProjectMail()
    {
        return new PermissionDeniedPrivateProjectMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new \CSRFSynchronizerToken('/join-private-project-mail/')
        );
    }

    public static function postJoinRestrictedUserMail()
    {
        return new PermissionDeniedRestrictedMemberMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new \CSRFSynchronizerToken('/join-project-restricted-user-mail/')
        );
    }

    public static function getProjectAdminIndexCategories()
    {
        return Categories\IndexController::buildSelf();
    }

    public static function getProjectAdminUpdateCategories()
    {
        return Categories\UpdateController::buildSelf();
    }

    public static function getOldFileDownloadURLRedirection(): FRSFileDownloadOldURLRedirectionController
    {
        return new FRSFileDownloadOldURLRedirectionController(HTTPFactoryBuilder::responseFactory(), new SapiEmitter());
    }

    public static function getFileDownload(): FRSFileDownloadController
    {
        $current_user_provider = new RESTCurrentUserMiddleware(UserManager::build(), new BasicAuthentication());
        return new FRSFileDownloadController(
            new URLVerification(),
            new FRSFileFactory(),
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            new SapiStreamEmitter(),
            $current_user_provider,
            new SessionWriteCloseMiddleware(),
            $current_user_provider,
            new TuleapRESTCORSMiddleware()
        );
    }

    public static function getFileDownloadAgreementAdminList(): DispatchableWithRequest
    {
        return ListLicenseAgreementsController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminAdd(): DispatchableWithRequest
    {
        return AddLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminEdit(): DispatchableWithRequest
    {
        return EditLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminSave(): DispatchableWithRequest
    {
        return SaveLicenseAgreementController::buildSelf();
    }

    public static function getFileDownloadAgreementAdminSetDefault(): DispatchableWithRequest
    {
        return SetDefaultLicenseAgreementController::buildSelf();
    }

    public static function getRssLatestProjects()
    {
        return new LatestProjectController(new LatestProjectDao(), \ProjectManager::instance(), Codendi_HTMLPurifier::instance());
    }

    public static function getRssLatestNews()
    {
        return new LatestNewsController(new NewsDao(), Codendi_HTMLPurifier::instance());
    }

    public static function getNewsPermissionsPerGroup(): DispatchableWithRequest
    {
        return new PermissionsPerGroup();
    }

    public static function getProjectAdminMembersController(): DispatchableWithRequest
    {
        return ProjectMembersController::buildSelf();
    }

    public static function getManageProjectInvitationsController(): DispatchableWithRequest
    {
        $user_manager    = \UserManager::instance();
        $instrumentation = new InvitationInstrumentation(Prometheus::instance());
        $invitation_dao  = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            $instrumentation
        );

        $invite_buddy_configuration = new InviteBuddyConfiguration(\EventManager::instance());

        $delegation_dao = new MembershipDelegationDao();

        $members_manager_checker = new UserCanManageProjectMembersChecker($delegation_dao);

        return new ManageProjectInvitationsController(
            $user_manager,
            new CSRFSynchronizerTokenProvider(),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new FeedbackSerializer(new \FeedbackDao())),
            $invitation_dao,
            $invitation_dao,
            new InvitationSender(
                new InvitationSenderGateKeeper(
                    new \Valid_Email(),
                    $invite_buddy_configuration,
                    new InvitationLimitChecker($invitation_dao, $invite_buddy_configuration)
                ),
                $user_manager,
                \BackendLogger::getDefaultLogger(),
                $members_manager_checker,
                new InvitationToOneRecipientWithoutVerificationSender(
                    new InvitationEmailNotifier(new LocaleSwitcher()),
                    $invitation_dao,
                    $invitation_dao,
                    $instrumentation,
                    new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
                    new \ProjectHistoryDao(),
                ),
                ProjectMemberAdderWithStatusCheckAndNotifications::build(),
            ),
            new ProjectHistoryDao(),
            new SapiEmitter(),
            new ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new RejectNonProjectMembersAdministratorMiddleware($user_manager, $members_manager_checker),
        );
    }

    public static function getAdminHelpDropdownController(): AdminReleaseNoteLinkController
    {
        return AdminReleaseNoteLinkController::buildSelf();
    }

    public static function postAdminHelpDropdownController(): PostAdminReleaseNoteLinkController
    {
        return PostAdminReleaseNoteLinkController::buildSelf();
    }

    public static function getPostUserGroupIdAdd(): DispatchableWithRequest
    {
        return MemberAdditionController::buildSelf();
    }

    public static function getPostUserGroupIdRemove(): DispatchableWithRequest
    {
        return MemberRemovalController::buildSelf();
    }

    public static function getPostSynchronizedMembershipActivation(): DispatchableWithRequest
    {
        return ActivationController::buildSelf();
    }

    public static function getGetServices(): DispatchableWithRequest
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../scripts/project-services/frontend-assets',
            '/assets/core/project-services'
        );
        return new \Tuleap\Project\Service\IndexController(
            AdministrationLayoutHelper::buildSelf(),
            new ServicesPresenterBuilder(ServiceManager::instance(), EventManager::instance()),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/project/admin/services/'),
            new JavascriptAsset($assets, 'project-admin-services.js'),
            new JavascriptAsset($assets, 'site-admin-services.js'),
        );
    }

    public static function getPostServicesAdd(): DispatchableWithRequest
    {
        return AddController::buildSelf();
    }

    public static function getPostServicesEdit(): DispatchableWithRequest
    {
        return EditController::buildSelf();
    }

    public static function getPostServicesDelete(): DispatchableWithRequest
    {
        return DeleteController::buildSelf();
    }

    public static function getGetProjectBannerAdministration(): DispatchableWithRequest
    {
        return new BannerAdministrationController(
            AdministrationLayoutHelper::buildSelf(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/project/admin/banner/'),
            new JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'ckeditor.js'),
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../scripts/project-admin-banner/frontend-assets', '/assets/core/project-admin-banner'),
                'project-admin-banner.js'
            ),
            new \Tuleap\Project\Banner\BannerRetriever(new \Tuleap\Project\Banner\BannerDao())
        );
    }

    public static function getGetPlatformBannerAdministration(): DispatchableWithRequest
    {
        return new PlatformBannerAdministrationController(
            new AdminPageRenderer(),
            new JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'ckeditor.js'),
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../scripts/platform-admin-banner/frontend-assets', '/assets/core/platform-admin-banner'),
                'platform-admin-banner.js'
            ),
            new \Tuleap\Platform\Banner\BannerRetriever(new \Tuleap\Platform\Banner\BannerDao())
        );
    }

    public static function getGetProjectBackgroundAdministration(): DispatchableWithRequest
    {
        return ProjectBackgroundAdministrationController::buildSelf();
    }

    public static function getProjectRegistrationController(): ProjectRegistrationController
    {
        return new ProjectRegistrationController(
            TemplateRendererFactory::build(),
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/project-registration/frontend-assets',
                    '/assets/core/project-registration'
                ),
                'src/index.ts'
            ),
            new ProjectRegistrationUserPermissionChecker(
                new \ProjectDao()
            ),
            new ProjectRegistrationPresenterBuilder(
                TemplateFactory::build(),
                new DefaultProjectVisibilityRetriever(),
                new TroveCatFactory(new TroveCatDao()),
                new DescriptionFieldsFactory(new DescriptionFieldsDao()),
                new CustomProjectArchive(),
            )
        );
    }

    public static function getLegacyController(string $path)
    {
        return new LegacyRoutesController($path);
    }

    public static function getAdminDatesDisplay(): RelativeDatesDisplayController
    {
        return new RelativeDatesDisplayController(
            new AdminPageRenderer(),
            RelativeDatesDisplayController::buildCSRFToken()
        );
    }

    public static function postAdminDatesDisplay(): RelativeDatesDisplaySaveController
    {
        return new RelativeDatesDisplaySaveController(
            RelativeDatesDisplayController::buildCSRFToken(),
            new SelectedDateDisplayPreferenceValidator(),
            new ConfigDao(),
            new \UserPreferencesDao()
        );
    }

    public static function getAdminInvitationsController(): InviteBuddyAdminController
    {
        return InviteBuddyAdminController::buildSelf();
    }

    public static function getAdminInvitationsUpdateController(): InviteBuddyAdminUpdateController
    {
        return InviteBuddyAdminUpdateController::buildSelf();
    }

    public static function getCSPViolationReportToController(): CSPViolationReportToController
    {
        return new CSPViolationReportToController(
            new SapiEmitter(),
            HTTPFactoryBuilder::responseFactory(),
            \BackendLogger::getDefaultLogger('csp_violation'),
            new SessionWriteCloseMiddleware(),
        );
    }

    public static function getReferencesController(): ReferenceAdministrationBrowseController
    {
        $event_manager     = EventManager::instance();
        $reference_manager = ReferenceManager::instance();
        $builder           = new ReferencePatternPresenterBuilder($event_manager, $reference_manager->getAvailableNatures());

        return new ReferenceAdministrationBrowseController(
            \ProjectManager::instance(),
            new ReferenceAdministrationBrowsingRenderer(
                Codendi_HTMLPurifier::instance(),
                $event_manager,
                $reference_manager,
                TemplateRendererFactory::build(),
                $builder
            ),
            new HeaderNavigationDisplayer(),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                $event_manager
            ),
            new ProjectAdministratorChecker()
        );
    }

    public static function getInterpretedCommonmark(): CommonMarkInterpreterController
    {
        return new CommonMarkInterpreterController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            CommonMarkInterpreter::build(
                Codendi_HTMLPurifier::instance(),
                new EnhancedCodeBlockExtension(new CodeBlockFeatures())
            ),
            new SapiEmitter(),
            new ProjectRetrieverMiddleware(ProjectRetriever::buildSelf())
        );
    }

    public static function postHelpMenuOpened(): HelpMenuOpenedController
    {
        return new HelpMenuOpenedController(
            \UserManager::instance(),
            Prometheus::instance(),
            HTTPFactoryBuilder::responseFactory(),
            new SapiEmitter()
        );
    }

    private function getLegacyControllerHandler(string $path): array
    {
        return [
            'core' => true,
            'handler' => 'getLegacyController',
            'params' => [$path],
        ];
    }

    public static function getProjectExportController(): ProjectExportController
    {
        return new ProjectExportController(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            ),
        );
    }

    public static function getProjectXmlExportController(): ProjectXmlExportController
    {
        return new ProjectXmlExportController(
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            ),
            \UserManager::instance(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\SessionWriteCloseMiddleware(),
            new \Tuleap\Http\Server\ServiceInstrumentationMiddleware('xml-export')
        );
    }

    public static function postFrontendErrorCollectorController(): FrontendErrorCollectorController
    {
        return new FrontendErrorCollectorController(
            HTTPFactoryBuilder::responseFactory(),
            \BackendLogger::getDefaultLogger('frontend_error'),
            Prometheus::instance(),
            \UserManager::instance(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\SessionWriteCloseMiddleware(),
            new \Tuleap\Http\Server\DisableCacheMiddleware(),
        );
    }

    public static function getRobotsTxt(): RobotsTxtController
    {
        return new RobotsTxtController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter()
        );
    }

    public static function getDisplayLostPasswordController(): DisplayLostPasswordController
    {
        return new DisplayLostPasswordController(
            TemplateRendererFactory::build(),
            new \Tuleap\Layout\IncludeCoreAssets(),
            EventManager::instance(),
        );
    }

    public static function getDisplayResetPasswordController(): DisplayResetPasswordController
    {
        $renderer_factory = TemplateRendererFactory::build();
        $core_assets      = new \Tuleap\Layout\IncludeCoreAssets();

        return new DisplayResetPasswordController(
            $renderer_factory,
            $core_assets,
            new UserFromConfirmationHashRetriever(
                new ResetTokenSerializer(),
                new Verifier(
                    new LostPasswordDAO(),
                    new \Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher(),
                    \UserManager::instance(),
                )
            ),
            new DisplayLostPasswordController($renderer_factory, $core_assets, EventManager::instance()),
            new LocaleSwitcher(),
            new PasswordConfigurationRetriever(new PasswordConfigurationDAO()),
        );
    }

    public static function getResetPasswordController(): DispatchableWithRequest
    {
        $user_manager = \UserManager::instance();

        return new ResetPasswordController(
            new UserFromConfirmationHashRetriever(
                new ResetTokenSerializer(),
                new Verifier(
                    new LostPasswordDAO(),
                    new \Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher(),
                    $user_manager,
                )
            ),
            self::getDisplayResetPasswordController(),
            self::getDisplayLostPasswordController(),
            new LocaleSwitcher(),
            new PasswordChanger(
                $user_manager,
                new SessionManager($user_manager, new \SessionDao(), new \RandomNumberGenerator()),
                new \Tuleap\User\Password\Reset\Revoker(new \Tuleap\User\Password\Reset\LostPasswordDAO()),
                EventManager::instance(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            TemplateRendererFactory::build(),
            new \Tuleap\Layout\IncludeCoreAssets(),
        );
    }

    public static function getLostPasswordController(): DispatchableWithRequest
    {
        $renderer_factory = TemplateRendererFactory::build();
        $event_manager    = EventManager::instance();
        $core_assets      = new \Tuleap\Layout\IncludeCoreAssets();

        return new LostPasswordController(
            \UserManager::instance(),
            new \Tuleap\User\Password\Reset\Creator(
                new \Tuleap\User\Password\Reset\LostPasswordDAO(),
                new \Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher()
            ),
            new ResetTokenSerializer(),
            new LocaleSwitcher(),
            $renderer_factory,
            $event_manager,
            $core_assets,
            new DisplayLostPasswordController(
                $renderer_factory,
                $core_assets,
                $event_manager,
            ),
            \BackendLogger::getDefaultLogger(),
        );
    }

    public static function getDisplayRegisterFormController(): DispatchableWithRequest
    {
        $event_manager = EventManager::instance();

        return new DisplayRegisterFormController(
            new RegisterFormDisplayer(
                new RegisterFormPresenterBuilder(
                    $event_manager,
                    TemplateRendererFactory::build(),
                    new \Account_TimezonesCollection(),
                ),
                new \Tuleap\Layout\IncludeCoreAssets(),
            ),
            $event_manager,
            new InvitationToEmailRequestExtractor(
                new InvitationDao(
                    new SplitTokenVerificationStringHasher(),
                    new InvitationInstrumentation(Prometheus::instance())
                ),
                new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            ),
        );
    }

    public static function getDisplayAdminRegisterFormController(): DispatchableWithRequest
    {
        return new DisplayAdminRegisterFormController(
            new RegisterFormDisplayer(
                new RegisterFormPresenterBuilder(
                    EventManager::instance(),
                    TemplateRendererFactory::build(),
                    new \Account_TimezonesCollection(),
                ),
                new \Tuleap\Layout\IncludeCoreAssets(),
            ),
        );
    }

    public static function postRegister(): DispatchableWithRequest
    {
        return new ProcessRegisterFormController(
            self::getRegisterFormProcessor(),
            \EventManager::instance(),
            new InvitationToEmailRequestExtractor(
                new InvitationDao(
                    new SplitTokenVerificationStringHasher(),
                    new InvitationInstrumentation(Prometheus::instance())
                ),
                new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            ),
        );
    }

    public static function postAdminRegister(): DispatchableWithRequest
    {
        return new ProcessAdminRegisterFormController(
            self::getRegisterFormProcessor(),
        );
    }

    private static function getRegisterFormProcessor(): RegisterFormProcessor
    {
        $logger                     = \BackendLogger::getDefaultLogger();
        $event_manager              = EventManager::instance();
        $user_manager               = \UserManager::instance();
        $project_manager            = ProjectManager::instance();
        $locale_switcher            = new LocaleSwitcher();
        $mail_presenter_factory     = new \MailPresenterFactory();
        $renderer_factory           = TemplateRendererFactory::build();
        $include_core_assets        = new IncludeCoreAssets();
        $timezones_collection       = new \Account_TimezonesCollection();
        $invitation_instrumentation = new InvitationInstrumentation(Prometheus::instance());
        $invitation_dao             = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            $invitation_instrumentation
        );
        $mail_renderer              = $renderer_factory->getRenderer(
            __DIR__ . '/../../templates/mail/'
        );


        return new RegisterFormProcessor(
            new \Tuleap\User\Account\Register\RegisterFormHandler(
                new AccountRegister(
                    $user_manager,
                    new AccountCreationFeedback(
                        $invitation_dao,
                        $user_manager,
                        new AccountCreationFeedbackEmailNotifier(),
                        new ProjectMemberAccordingToInvitationAdder(
                            $user_manager,
                            $project_manager,
                            ProjectMemberAdderWithStatusCheckAndNotifications::build(),
                            $invitation_instrumentation,
                            $logger,
                            new InvitationEmailNotifier(new LocaleSwitcher()),
                            new ProjectHistoryDao(),
                        ),
                        $invitation_instrumentation,
                        $logger,
                    )
                ),
                $timezones_collection,
                $event_manager,
            ),
            new \Tuleap\User\MailConfirmationCodeGenerator(
                $user_manager,
                new \RandomNumberGenerator()
            ),
            new AfterSuccessfulUserRegistration(
                new ConfirmationPageDisplayer(
                    $renderer_factory,
                    $include_core_assets,
                ),
                new ConfirmationHashEmailSender(
                    new \TuleapRegisterMail($mail_presenter_factory, $mail_renderer, $user_manager, $locale_switcher, 'mail'),
                    \Tuleap\ServerHostname::HTTPSUrl(),
                ),
                new NewUserByAdminEmailSender(
                    new \TuleapRegisterMail($mail_presenter_factory, $mail_renderer, $user_manager, $locale_switcher, 'mail-admin'),
                    \Tuleap\ServerHostname::HTTPSUrl(),
                ),
                $event_manager,
                $user_manager,
                $project_manager,
            ),
            new RegisterFormDisplayer(
                new RegisterFormPresenterBuilder(
                    $event_manager,
                    $renderer_factory,
                    $timezones_collection,
                ),
                $include_core_assets,
            ),
        );
    }

    public static function postWebAuthnRegistrationChallenge(): DispatchablePSR15Compatible
    {
        $json_response_builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        return new PostRegistrationChallengeController(
            \UserManager::instance(),
            new WebAuthnChallengeDao(),
            new WebAuthnCredentialSourceDao(),
            new PublicKeyCredentialRpEntity(
                \ForgeConfig::get(ConfigurationVariables::NAME),
                ServerHostname::rawHostname()
            ),
            WebAuthnRegistration::getCredentialParameters(),
            $json_response_builder,
            new RestlerErrorResponseBuilder($json_response_builder),
            new SapiEmitter()
        );
    }

    public static function postWebAuthnRegistration(): DispatchablePSR15Compatible
    {
        $attestation_statement_manager = new AttestationStatementSupportManager();
        $attestation_statement_manager->add(new NoneAttestationStatementSupport());
        $source_dao            = new WebAuthnCredentialSourceDao();
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $json_response_builder = new JSONResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory());
        $logger                = \BackendLogger::getDefaultLogger();
        $credential_loader     = new PublicKeyCredentialLoader(
            new AttestationObjectLoader($attestation_statement_manager)
        );
        $credential_loader->setLogger($logger);
        $attestation_validator = new AuthenticatorAttestationResponseValidator(
            $attestation_statement_manager,
            $source_dao,
            null,
            null,
            null,
            new CeremonyStepManager(
                [new CheckExtensions(new ExtensionOutputCheckerHandler())]
            ),
        );
        $attestation_validator->setLogger($logger);

        return new PostRegistrationController(
            \UserManager::instance(),
            new WebAuthnChallengeDao(),
            $source_dao,
            new PublicKeyCredentialRpEntity(
                \ForgeConfig::get(ConfigurationVariables::NAME),
                ServerHostname::rawHostname()
            ),
            WebAuthnRegistration::getCredentialParameters(),
            $credential_loader,
            $attestation_validator,
            $response_factory,
            new RestlerErrorResponseBuilder($json_response_builder),
            new FeedbackSerializer(new \FeedbackDao()),
            new \CSRFSynchronizerToken(PostRegistrationController::URL),
            new SapiEmitter()
        );
    }

    public static function postWebAuthnAuthenticationChallenge(): DispatchablePSR15Compatible
    {
        $json_response_builder = new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory());

        return new PostAuthenticationChallengeController(
            \UserManager::instance(),
            new WebAuthnCredentialSourceDao(),
            new WebAuthnChallengeDao(),
            $json_response_builder,
            new RestlerErrorResponseBuilder($json_response_builder),
            new SapiEmitter()
        );
    }

    public static function deleteWebAuthnSource(): DispatchablePSR15Compatible
    {
        $response_factory      = HTTPFactoryBuilder::responseFactory();
        $json_response_builder = new JSONResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory());
        $source_dao            = new WebAuthnCredentialSourceDao();

        return new DeleteSourceController(
            \UserManager::instance(),
            \UserManager::instance(),
            $source_dao,
            $source_dao,
            $source_dao,
            new RestlerErrorResponseBuilder($json_response_builder),
            $response_factory,
            new FeedbackSerializer(new \FeedbackDao()),
            new \CSRFSynchronizerToken(DeleteSourceController::URL),
            new SapiEmitter()
        );
    }

    public static function postSwitchPasswordlessAuthentication(): DispatchableWithRequest
    {
        return new PostSwitchPasswordlessAuthenticationController(
            \UserManager::instance(),
            \UserManager::instance(),
            new \CSRFSynchronizerToken(PostSwitchPasswordlessAuthenticationController::URL),
        );
    }

    public static function getFeatureFlag(): FeatureFlagController
    {
        return new FeatureFlagController(
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new SapiEmitter()
        );
    }

    public static function routeProjectUpload(): FileUploadController
    {
        $path_allocator          = new UploadPathAllocator(ForgeConfig::get('tmp_dir') . '/project/ongoing-upload');
        $file_ongoing_upload_dao = new ProjectArchiveOngoingUploadDao();
        $current_user            = new RESTCurrentUserMiddleware(
            UserManager::build(),
            new BasicAuthentication()
        );

        return FileUploadController::build(
            new ProjectFileDataStore(
                new ProjectFileBeingUploadedInformationProvider(
                    $path_allocator,
                    $file_ongoing_upload_dao,
                    $current_user,
                    new ProjectRegistrationUserPermissionChecker(new \ProjectDao())
                ),
                new FileBeingUploadedWriter($path_allocator, DBFactory::getMainTuleapDBConnection()),
                new ProjectFileUploadFinisher(
                    $file_ongoing_upload_dao,
                    $file_ongoing_upload_dao,
                    $path_allocator,
                    new EnqueueProjectCreationFromArchive(new EnqueueTask()),
                    $current_user,
                ),
                new ProjectFileUploadCanceler(
                    $path_allocator,
                    $file_ongoing_upload_dao
                ),
                new FileBeingUploadedLocker($path_allocator),
            ),
            $current_user
        );
    }

    public static function getUploadedArchiveController(): DispatchableWithRequest
    {
        return new UploadedArchiveForProjectController(
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            new UploadedArchiveForProjectDao(),
            new SapiStreamEmitter(),
            new SessionWriteCloseMiddleware(),
            new ProjectRetrieverMiddleware(
                new ProjectRetriever(
                    ProjectManager::instance(),
                ),
            ),
            new RejectNonProjectAdministratorMiddleware(
                \UserManager::instance(),
                new ProjectAdministratorChecker(),
            ),
        );
    }

    public function collect(FastRoute\RouteCollector $r): void
    {
        $r->get('/', [self::class, 'getSlash']);

        $r->get('/contact.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/contact.php'));
        $r->addRoute(['GET', 'POST'], '/goto[.php]', $this->getLegacyControllerHandler(__DIR__ . '/../../core/goto.php'));
        $r->get('/info.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/info.php'));
        $r->get('/robots.txt', [self::class, 'getRobotsTxt']);
        $r->post('/make_links.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/make_links.php'));
        $r->get('/toggler.php', $this->getLegacyControllerHandler(__DIR__ . '/../../core/toggler.php'));
        $r->post('/help_menu_opened', [self::class, 'postHelpMenuOpened']);

        $r->get('/feature_flag', [self::class, 'getFeatureFlag']);

        $r->addGroup('/project/{project_id:\d+}/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/categories', [self::class, 'getProjectAdminIndexCategories']);
            $r->post('/categories', [self::class, 'getProjectAdminUpdateCategories']);

            $r->addRoute(['GET', 'POST'], '/members', [self::class, 'getProjectAdminMembersController']);

            $r->post('/invitations', [self::class, 'getManageProjectInvitationsController']);

            $r->post('/change-synchronized-project-membership', [self::class, 'getPostSynchronizedMembershipActivation']);
            $r->post('/user-group/{user-group-id:\d+}/add', [self::class, 'getPostUserGroupIdAdd']);
            $r->post('/user-group/{user-group-id:\d+}/remove', [self::class, 'getPostUserGroupIdRemove']);

            $r->get('/services', [self::class, 'getGetServices']);
            $r->post('/services/add', [self::class, 'getPostServicesAdd']);
            $r->post('/services/edit', [self::class, 'getPostServicesEdit']);
            $r->post('/services/delete', [self::class, 'getPostServicesDelete']);
            $r->get('/banner', [self::class, 'getGetProjectBannerAdministration']);
            $r->get('/background', [self::class, 'getGetProjectBackgroundAdministration']);

            $r->get('/references', [self::class, 'getReferencesController']);

            $r->get('/export/xml', [self::class, 'getProjectXmlExportController']);
            $r->get('/export', [self::class, 'getProjectExportController']);
            $r->get('/uploaded-archive', [self::class, 'getUploadedArchiveController']);
        });

        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', [self::class, 'getOrPostProjectHome']);

        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/invitations/', [self::class, 'getAdminInvitationsController']);
            $r->post('/invitations/', [self::class, 'getAdminInvitationsUpdateController']);

            $r->get('/release-note/', [self::class, 'getAdminHelpDropdownController']);
            $r->post('/release-note/', [self::class, 'postAdminHelpDropdownController']);

            $r->get('/password_policy/', [self::class, 'getAdminPasswordPolicy']);
            $r->post('/password_policy/', [self::class, 'postAdminPasswordPolicy']);

            $r->get('/user-settings/', [self::class, 'getUserSettings']);
            $r->post('/user-settings/', [self::class, 'postUserSettings']);

            $r->get('/project-creation/moderation', [self::class, 'getProjectCreationModeration']);
            $r->post('/project-creation/moderation', [self::class, 'postProjectCreationModeration']);

            $r->get('/project-creation/templates', [self::class, 'getProjectCreationTemplates']);

            $r->get('/project-creation/webhooks', [self::class, 'getProjectCreationWebhooks']);
            $r->post('/project-creation/webhooks', [self::class, 'postProjectCreationWebhooks']);

            $r->get('/project-creation/fields', [self::class, 'getProjectCreationFields']);
            $r->post('/project-creation/fields', [self::class, 'postProjectCreationFields']);

            $r->get('/project-creation/categories', [self::class, 'getProjectCreationCategories']);
            $r->post('/project-creation/categories', [self::class, 'postProjectCreationCategories']);

            $r->get('/project-creation/visibility', [self::class, 'getProjectCreationVisibility']);
            $r->post('/project-creation/visibility', [self::class, 'postProjectCreationVisibility']);

            $r->get('/project-creation/widgets', [self::class, 'getProjectConfigurationWidgets']);
            $r->post('/project-creation/widgets/{widget-id}/enable', [self::class, 'getProjectConfigurationPOSTWidgetsEnable']);
            $r->post('/project-creation/widgets/{widget-id}/disable', [self::class, 'getProjectConfigurationPOSTWidgetsDisable']);

            $r->get('/site-content-customisations', [self::class, 'getAdminSiteContentCustomisation']);

            $r->get('/dates-display', [self::class, 'getAdminDatesDisplay']);
            $r->post('/dates-display', [self::class, 'postAdminDatesDisplay']);

            $r->get('/banner', [self::class, 'getGetPlatformBannerAdministration']);

            $r->get('/register', [self::class, 'getDisplayAdminRegisterFormController']);
            $r->post('/register', [self::class, 'postAdminRegister']);
        });

        $r->addGroup('/account', static function (FastRoute\RouteCollector $r) {
            $r->get('/information', [self::class, 'getAccountPreferences']);
            $r->post('/information', [self::class, 'postAccountInformation']);

            $r->get('/confirm-new-email', [self::class, 'getEmailConfirm']);

            $r->get('/notifications', [self::class, 'getAccountNotifications']);
            $r->post('/notifications', [self::class, 'postAccountNotifications']);

            $r->get('/keys-tokens', [self::class, 'getAccountToken']);
            $r->post('/ssh_key/create', [self::class, 'postAccountSSHKeyCreate']);
            $r->post('/ssh_key/delete', [self::class, 'postAccountSSHKeyDelete']);
            $r->post('/access_key/create', [self::class, 'postAccountAccessKeyCreate']);
            $r->post('/access_key/revoke', [self::class, 'postAccountAccessKeyRevoke']);
            $r->post('/svn_token/revoke', [self::class, 'postAccountSVNTokenRevoke']);

            $r->get('/edition', [self::class, 'getEditionController']);
            $r->post('/edition', [self::class, 'postEditionController']);

            $r->get('/appearance', [self::class, 'getAppearanceController']);
            $r->post('/appearance', [self::class, 'postAppearanceController']);

            $r->get('/security', [self::class, 'getAccountSecurity']);
            $r->post('/security/session', [self::class, 'postAccountSecuritySession']);
            $r->post('/security/password', [self::class, 'postAccountSecurityPassword']);

            $r->post('/avatar', [self::class, 'postAccountAvatar']);
            $r->post('/logout', [self::class, 'postLogoutAccount']);

            $r->get('/lostpw', [self::class, 'getDisplayLostPasswordController']);
            $r->post('/lostpw', [self::class, 'getLostPasswordController']);

            $r->get('/lostlogin.php', [self::class, 'getDisplayResetPasswordController']);
            $r->post('/reset-lostpw', [self::class, 'getResetPasswordController']);

            $r->post('/remove_from_project/{project_id:\d+}', [self::class, 'postAccountRemoveFromProject']);

            $r->get('/register.php', [self::class, 'getDisplayRegisterFormController']);
            $r->post('/register.php', [self::class, 'postRegister']);
        });
        $r->get('/.well-known/change-password', [self::class, 'getWellKnownUrlChangePassword']);

        $r->addGroup('/users', function (FastRoute\RouteCollector $r) {
            $r->get('/{name}[/]', [self::class, 'getUsersName']);
            $r->get('/{name}/avatar.png', [self::class, 'getUsersNameAvatar']);
            $r->get('/{name}/avatar-{hash}.png', [self::class, 'getUsersNameAvatarHash']);
        });

        $r->post('/join-private-project-mail/', [self::class, 'postJoinPrivateProjectMail']);
        $r->post('/join-project-restricted-user-mail/', [self::class, 'postJoinRestrictedUserMail']);

        $r->addGroup('/file', function (FastRoute\RouteCollector $r) {
            $r->get('/download.php/{group_id:\d+}/{file_id:\d+}[/{filename:.*}]', [self::class, 'getOldFileDownloadURLRedirection']);
            $r->get('/download/{file_id:\d+}', [self::class, 'getFileDownload']);
            $r->get('/{project_id:\d+}/admin/license-agreements', [self::class, 'getFileDownloadAgreementAdminList']);
            $r->get('/{project_id:\d+}/admin/license-agreements/add', [self::class, 'getFileDownloadAgreementAdminAdd']);
            $r->get('/{project_id:\d+}/admin/license-agreements/{id:\d+}', [self::class, 'getFileDownloadAgreementAdminEdit']);
            $r->post('/{project_id:\d+}/admin/license-agreements/save', [self::class, 'getFileDownloadAgreementAdminSave']);
            $r->post('/{project_id:\d+}/admin/license-agreements/set-default', [self::class, 'getFileDownloadAgreementAdminSetDefault']);
        });

        $r->get('/export/rss_sfprojects.php', [self::class, 'getRssLatestProjects']);
        $r->get('/export/rss_sfnews.php', [self::class, 'getRssLatestNews']);

        $r->get('/news/permissions-per-group', [self::class, 'getNewsPermissionsPerGroup']);

        $r->post('/csp-violation', [self::class, 'getCSPViolationReportToController']);

        $r->addGroup('/project', function (FastRoute\RouteCollector $r) {
            $r->get('/new', [self::class, 'getProjectRegistrationController']);
            $r->get('/new-information', [self::class, 'getProjectRegistrationController']);
            $r->get('/from-archive-creation/{project_id:\d+}', [self::class, 'getProjectRegistrationController']);
            $r->get('/approval', [self::class, 'getProjectRegistrationController']);
            $r->post('/{project_id:\d+}/interpret-commonmark', [self::class, 'getInterpretedCommonmark']);
        });

        $r->addGroup(
            '/oauth2',
            function (FastRoute\RouteCollector $r): void {
                $r->addRoute(['GET', 'POST'], '/userinfo', [OAuth2ServerRoutes::class, 'routeOAuth2UserInfoEndpoint']);
                $r->get('/jwks', [OAuth2ServerRoutes::class, 'routeJWKSDocument']);
                $r->post('/token', [OAuth2ServerRoutes::class, 'routeAccessTokenCreation']);
                $r->post('/token/revoke', [OAuth2ServerRoutes::class, 'routeTokenRevocation']);
            }
        );

        $r->post('/collect-frontend-errors', [self::class, 'postFrontendErrorCollectorController']);

        $r->addGroup('/webauthn', function (FastRoute\RouteCollector $r) {
            $r->post('/registration-challenge', [self::class, 'postWebAuthnRegistrationChallenge']);
            $r->post('/registration', [self::class, 'postWebAuthnRegistration']);

            $r->post('/authentication-challenge', [self::class, 'postWebAuthnAuthenticationChallenge']);

            $r->post('/key/delete', [self::class, 'deleteWebAuthnSource']);

            $r->post('/switch-passwordless', [self::class, 'postSwitchPasswordlessAuthentication']);
        });

        $r->addRoute(
            ['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'],
            '/uploads/project/file/{id:\d+}',
            [self::class, 'routeProjectUpload']
        );

        SVNProjectAccessRouteDefinition::defineRoute($r, '/svnroot');

        $collect_routes = new CollectRoutesEvent($r);
        $this->event_manager->processEvent($collect_routes);
    }
}
