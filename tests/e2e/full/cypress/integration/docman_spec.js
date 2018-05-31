/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

describe("Docman", function() {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.login();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");

        cy.loadProjectConfig();
    });


    it("cannot create a document when a mandatory property is not filled", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=admin_metadata');
        cy.get('[data-test=metadata_name]').type('my custom property');
        cy.get('[data-test=empty_allowed]').uncheck();
        cy.get('[data-test=use_it]').check();
        cy.get('[data-test=admin_create_metadata]').submit();

        cy.visit('/plugins/docman/index.php?group_id=' + this.projects.permission_project_id + '&id=2&action=newDocument');
        cy.get('#title').type('my document title');
        cy.get('[type="radio"]').check('4');
        cy.get('.cke_wysiwyg_frame').type('my content');
        cy.get('#docman_new_form').submit();

        cy.get('[data-test=feedback]').contains('"my custom property" is required, please fill the field.');
    });

    it("cannot create a property with an empty name", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=admin_metadata');
        cy.get('[data-test=metadata_name]').type('  ');
        cy.get('[data-test=empty_allowed]').uncheck();
        cy.get('[data-test=use_it]').check();
        cy.get('[data-test=admin_create_metadata]').submit();

        cy.get('[data-test=feedback]').contains('Property name is required, please fill this field.');
    });

    it("create a folder with mandatory properties", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=newGlobalDocument&id=2');
        cy.get('[data-test=document_type]').select("1");
        cy.get('[data-test=create_document_next]').click();

        cy.get('[data-test=docman_new_item]').contains('my custom property');
    });

    it("remove a property", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=admin_metadata');
        cy.get('[href*="action=admin_delete_metadata"]').click();

        cy.get('[data-test=feedback]').contains('"my custom property" successfully deleted');
    });

    it("create an embed document", function() {
        cy.visit('/plugins/docman/index.php?group_id=' + this.projects.permission_project_id + '&id=2&action=newDocument');
        cy.get('#title').type('my document title');


        cy.get('[type="radio"]').check('4');
        cy.window().then(win => {
            win.CKEDITOR.instances.embedded_content.setData('<p>my content</p>')
        });
        cy.get('#docman_new_form').submit();

        cy.get('[data-test=feedback]').contains('Document successfully created.');
        cy.contains('my document title').click();
        cy.get('.docman_embedded_file_content').contains('my content');
    });

    it("create a new version of a document", function() {
        cy.visit('/plugins/docman/index.php?group_id=' + this.projects.permission_project_id + '&id=3&action=action_new_version');
        cy.get('[data-test=docman_changelog]').type('new version');

        cy.get('[data-test=docman_create_new_version]').click();

        cy.get('[data-test=feedback]').contains('New version successfully created.');
    });

    it("delete a given version of a document", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=details&id=3&section=history');
        cy.get('[href*="action=confirmDelete"]').first().click();
        cy.get('[name="confirm"]').click();

        cy.get('[data-test=feedback]').contains('successfully deleted');
    });

   it("throw an error when you try to delete the last version of a document", function() {
       cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id + '&action=details&id=3&section=history');
        cy.get('[href*="action=confirmDelete"]').first().click();
        cy.get('[name="confirm"]').click();

        cy.get('[data-test=feedback]').contains('Cannot delete last version of a file. If you want to continue, please delete the document itself.');
    });

    it("create a folder", function() {
        cy.visit('/plugins/docman/index.php?group_id=' + this.projects.permission_project_id + '&action=newFolder');

        cy.get('#title').type('my folder name');

        cy.get('[data-test=docman_create]').click();

        cy.get('[data-test=feedback]').contains('Document successfully created.');
        cy.contains('my folder name');
    });

    it("should search items by name", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id);
        cy.get('[data-test=docman_search]').type('folder');
        cy.get("[data-test=docman_search_button]").click();

        cy.get("[data-test=docman_report_table]").contains('my folder name');
    });

    it("should expand result", function() {
        cy.visit('/plugins/docman/?group_id=' + this.projects.permission_project_id);
        cy.get('[data-test=docman_report_search]').click();
        cy.get('[data-test=docman_search]').should('be.disabled');
        cy.get('[data-test=docman_form_table]').contains('Global text search');
    });
});
