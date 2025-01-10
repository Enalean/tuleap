package main

import (
	"strings"
	"testing"
)

var fakeUpdatedReference = UpdatedReference{
	OldValue: "000000000",
	NewValue: "111111111",
}

func TestValidateValidTagFormats(t *testing.T) {
	fakeHookData := HookData{
		UpdatedReferences: map[string]UpdatedReference{
			"refs/heads/main":                                 fakeUpdatedReference,
			"refs/tags/16.3":                                  fakeUpdatedReference,
			"refs/tags/16.3-10":                               fakeUpdatedReference,
			"refs/tags/@tuleap/package-name_0.1":              fakeUpdatedReference,
			"refs/tags/@tuleap/package-name_0.1.1":            fakeUpdatedReference,
			"refs/tags/@tuleap/tuleap_additional_tools_0.1.1": fakeUpdatedReference,
		},
	}

	rejectionMessage := ValidateTagsFormat(fakeHookData)
	if rejectionMessage != nil {
		t.Fatalf("Valid tags have been rejected: %s", *rejectionMessage)
	}
}

func TestRejectInvalidTagFormat(t *testing.T) {
	fakeHookData := HookData{
		UpdatedReferences: map[string]UpdatedReference{
			"refs/tags/wrong_tag": fakeUpdatedReference,
		},
	}

	rejectionMessage := ValidateTagsFormat(fakeHookData)
	if rejectionMessage == nil {
		t.Fatal("An invalid tag has been accepted")
	}

	if !strings.Contains(*rejectionMessage, "wrong_tag") {
		t.Fatalf("The rejection message does not mention the incorrect tag: %s", *rejectionMessage)
	}
}
