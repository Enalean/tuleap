/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import Chainable = Cypress.Chainable;

declare global {
    // Be consistent with Cypress declaration
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            pushGitCommit(repository_name: string): void;
            pushGitCommitInBranch(repository_name: string, branch_name: string): void;
            pushAndRebaseGitCommitInBranch(repository_name: string, branch_name: string): void;
            pushGitCommitInBranchWillFail(repository_name: string, branch_name: string): void;
            deleteGitCommitInExistingBranch(repository_name: string, branch_name: string): void;
            deleteGitCommitInExistingBranchWillFail(
                repository_name: string,
                branch_name: string,
            ): void;
            createAndPushTag(repository_name: string, tag_name: string): void;
            createAndPushTagWillFail(repository_name: string, tag_name: string): void;
            cloneRepository(user: string, repository_path: string, repository_name: string): void;
            cloneRepositoryWillFail(
                user: string,
                repository_path: string,
                repository_name: string,
            ): Chainable<string>;
            deleteClone(repository_name: string): void;
            pullBranch(repository_name: string): void;
        }
    }
}

Cypress.Commands.add("pushGitCommit", (repository_name: string) => {
    const command = `cd /tmp/${repository_name}
                echo aa >> README &&
                git add README &&
                git commit -m 'test commit' &&
                git -c http.sslVerify=false push`;
    cy.exec(command);
});

Cypress.Commands.add(
    "pushAndRebaseGitCommitInBranch",
    (repository_name: string, branch_name: string) => {
        const push_command = pushGitCommitCommandWithRebase(repository_name, branch_name);
        cy.exec(push_command);
    },
);

Cypress.Commands.add("pushGitCommitInBranch", (repository_name: string, branch_name: string) => {
    const push_command = pushGitCommitCommandWithoutRebase(repository_name, branch_name);
    cy.exec(push_command);
});

Cypress.Commands.add(
    "pushGitCommitInBranchWillFail",
    (repository_name: string, branch_name: string) => {
        const push_command = pushGitCommitCommandWithRebase(repository_name, branch_name);
        cy.exec(push_command, { failOnNonZeroExit: false })
            .its("stderr")
            .should("contain", "remote: FATAL: W refs/heads/devel");
    },
);

Cypress.Commands.add(
    "deleteGitCommitInExistingBranch",
    (repository_name: string, branch_name: string) => {
        const push_command = pushDeleteCommitCommand(repository_name, branch_name);
        cy.exec(push_command);
    },
);

Cypress.Commands.add(
    "deleteGitCommitInExistingBranchWillFail",
    (repository_name: string, branch_name: string) => {
        const push_command = pushDeleteCommitCommand(repository_name, branch_name);
        cy.exec(push_command, { failOnNonZeroExit: false })
            .its("stderr")
            .should("contain", "fatal");
    },
);

Cypress.Commands.add("createAndPushTag", (repository_name: string, tag_name: string) => {
    const push_command = createTagCommand(repository_name, tag_name);
    cy.exec(push_command);
});

Cypress.Commands.add("createAndPushTagWillFail", (repository_name: string, tag_name: string) => {
    const push_command = createTagCommand(repository_name, tag_name);
    cy.exec(push_command, { failOnNonZeroExit: false }).its("stderr").should("contain", "error");
});

Cypress.Commands.add(
    "cloneRepository",
    (user: string, repository_path: string, repository_name: string) => {
        const uri = encodeURI(`https://${user}:Correct Horse Battery Staple@${repository_path}`);
        const branch_command = `cd /tmp &&
            git -c http.sslVerify=false clone ${uri} ${repository_name} &&
            cd /tmp/${repository_name} &&
            git config user.name "admin" &&
            git config user.email "admin@example.com"
             `;
        cy.exec(branch_command);
    },
);

Cypress.Commands.add(
    "cloneRepositoryWillFail",
    (user: string, repository_path: string, repository_name: string): Chainable<string> => {
        const uri = encodeURI(`https://${user}:Correct Horse Battery Staple@${repository_path}`);
        const branch_command = `cd /tmp &&
            git -c http.sslVerify=false clone ${uri} ${repository_name} &&
            cd /tmp/${repository_name} &&
            git config user.name "admin" &&
            git config user.email "admin@example.com"
             `;
        return cy.exec(branch_command, { failOnNonZeroExit: false }).then(function (result) {
            return result.stderr;
        });
    },
);

Cypress.Commands.add("deleteClone", (repository_name: string) => {
    const command = ` rm -rf "/tmp/${repository_name}" `;
    cy.exec(command);
});

function pushGitCommitCommandWithRebase(repository_name: string, branch_name: string): string {
    return `cd /tmp/${repository_name} &&
          git checkout -b ${branch_name} &&
          git -c http.sslVerify=false pull --rebase origin ${branch_name} &&
          echo aa >> README &&
          git add . &&
          git commit -m 'test commit' &&
          git -c http.sslVerify=false push --set-upstream origin ${branch_name}
       `;
}

function pushGitCommitCommandWithoutRebase(repository_name: string, branch_name: string): string {
    return `cd /tmp/${repository_name} &&
          git checkout -b ${branch_name} &&
          echo aa >> README &&
          git add . &&
          git commit -m 'test commit' &&
          git -c http.sslVerify=false push --set-upstream origin ${branch_name}
       `;
}

function pushDeleteCommitCommand(repository_name: string, branch_name: string): string {
    return ` cd /tmp/${repository_name} &&
            git -c http.sslVerify=false pull --ff-only origin ${branch_name} &&
            git reset --hard HEAD^
            git -c http.sslVerify=false push -f --set-upstream origin ${branch_name}
            `;
}

function createTagCommand(repository_name: string, tag_name: string): string {
    return ` cd /tmp/${repository_name} &&
            git tag ${tag_name}
            git -c http.sslVerify=false push --tags`;
}

export {};
