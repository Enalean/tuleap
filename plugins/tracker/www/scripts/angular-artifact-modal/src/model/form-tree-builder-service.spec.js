describe("TuleapArtifactModalFormTreeBuilderService", function() {
    var TuleapArtifactModalFormTreeBuilderService;
    beforeEach(function() {
        module('tuleap-artifact-modal-model');

        inject(function(_TuleapArtifactModalFormTreeBuilderService_) {
            TuleapArtifactModalFormTreeBuilderService = _TuleapArtifactModalFormTreeBuilderService_;
        });
    });

    describe("buildFormTree() -", function() {
        var tracker;

        describe("Given a tracker object", function() {
            it("when I build the form tree, then the form tree given by the tracker structure will be filled with the complete fields, augmented with the correct template_url and will be returned", function() {
                tracker = {
                    fields: [
                        { field_id: 1, type: 'int' },
                        { field_id: 2, type: 'int' },
                        { field_id: 3, type: 'fieldset' },
                        { field_id: 4, type: 'int' },
                        { field_id: 5, type: 'column' },
                        { field_id: 6, type: 'int' }
                    ],
                    structure: [
                        { id: 1, content: null },
                        { id: 2, content: null },
                        { id: 3, content: [
                            { id: 4, content: null },
                            { id: 5, content: [
                                { id: 6, content: null }
                            ]}
                        ]}
                    ]
                };

                var output = TuleapArtifactModalFormTreeBuilderService.buildFormTree(tracker);

                expect(output).toEqual([
                    {
                        field_id: 1,
                        type: 'int',
                        template_url: 'field-int.tpl.html'
                    },
                    {
                        field_id: 2,
                        type: 'int',
                        template_url: 'field-int.tpl.html'
                    },
                    {
                        field_id: 3,
                        type: 'fieldset',
                        template_url: 'field-fieldset.tpl.html',
                        content: [
                            {
                                field_id: 4,
                                type: 'int',
                                template_url: 'field-int.tpl.html'
                            },
                            {
                                field_id: 5,
                                type: 'column',
                                template_url: 'field-column.tpl.html',
                                content: [
                                    {
                                        field_id: 6,
                                        type: 'int',
                                        template_url: 'field-int.tpl.html'
                                    }
                                ]
                            }
                        ]
                    }
                ]);
            });
        });
    });
});
