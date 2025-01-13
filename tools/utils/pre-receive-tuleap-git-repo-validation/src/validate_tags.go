package main

import (
	"regexp"
	"strings"
)

var validTagFormats = regexp.MustCompile(
	`(?:^\d\d\.\d+$)` + // Tuleap
		`|(?:^\d\d\.\d+\-(?:(?:[2-9]\d*)|(?:1\d+))$)` + // Tuleap Enterprise
		`|(?:^@tuleap/[a-zA-Z0-9_\-]+_\d+\.\d+(?:\.\d+)?$)` + // JS packages
		`|(?:^tuleap_additional_tools_\d+\.\d+(?:\.\d+)?$)`, // Additional tools
)

func ValidateTagsFormat(hookData HookData) *string {
	var incorrectlyFormattedTags []string

	for reference := range hookData.UpdatedReferences {
		tagName := strings.TrimPrefix(reference, "refs/tags/")
		if tagName == reference {
			continue
		}

		if !validTagFormats.MatchString(tagName) {
			incorrectlyFormattedTags = append(incorrectlyFormattedTags, tagName)
		}
	}

	if len(incorrectlyFormattedTags) > 0 {
		rejectionMessage := "The following tags '" + strings.Join(incorrectlyFormattedTags, ", ") + "' do not respect the expected format, please see docs/release.md"
		return &rejectionMessage
	}

	return nil
}
