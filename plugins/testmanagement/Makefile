SHELL=/usr/bin/env bash

.PHONY: generate-templates
generate-templates:
	install --mode=0644 -D $(CURDIR)/../../tools/setup_templates/generate-templates/trackers/ttm-campaign.xml \
		resources/templates/Tracker_campaign.xml
	install --mode=0644 -D $(CURDIR)/../../tools/setup_templates/generate-templates/trackers/ttm-def.xml \
		resources/templates/Tracker_test_def.xml
	install --mode=0644 -D $(CURDIR)/../../tools/setup_templates/generate-templates/trackers/ttm-exec.xml \
		resources/templates/Tracker_test_exec.xml
