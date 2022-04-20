export {};

declare global {
    // Be consistent with Cypress declaration

    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            clearSessionCookie(): void;
            preserveSessionCookies(): void;
            projectAdministratorLogin(): void;
            secondProjectAdministratorLogin(): void;
            projectMemberLogin(): void;
            permissionDelegationLogin(): void;
            platformAdminLogin(): void;
            restrictedMemberLogin(): void;
            restrictedRegularUserLogin(): void;
            regularUserLogin(): void;
            heisenbergLogin(): void;
            userLogout(): void;
            switchProjectVisibility(visibility: string): void;
            updatePlatformVisibilityAndAllowRestricted(): void;
            updatePlatformVisibilityForAnonymous(): void;
            getProjectId(project_shortname: string): Chainable<number>;
            visitProjectService(project_unixname: string, service_label: string): void;
            visitProjectAdministration(project_unixname: string): void;
            visitProjectAdministrationInCurrentProject(): void;
            visitServiceInCurrentProject(service_label: string): void;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            getFromTuleapAPI(url: string): Chainable<Response<any>>;
            postFromTuleapApi(url: string, payload: Record<string, unknown>): void;
            putFromTuleapApi(url: string, payload: Record<string, unknown>): void;
        }
    }
}
