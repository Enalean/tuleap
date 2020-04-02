generate-templates:
	xsltproc $(CURDIR)/../../tools/utils/setup_templates/generate-templates/generate-scrum_dashboard.xml \
		-o resources/templates/scrum_dashboard_template.xml
	cp -f $(CURDIR)/../../tools/utils/setup_templates/generate-templates/trackers/task.xml \
		resources/templates/Tracker_Tasks.xml
	cp -f $(CURDIR)/../../tools/utils/setup_templates/generate-templates/trackers/story.xml \
		resources/templates/Tracker_UserStories.xml
	cp -f $(CURDIR)/../../tools/utils/setup_templates/generate-templates/trackers/activity.xml \
		resources/templates/Tracker_activity.xml
	cp -f $(CURDIR)/../../tools/utils/setup_templates/generate-templates/trackers/rel.xml \
		resources/templates/Tracker_release.xml
	cp -f $(CURDIR)/../../tools/utils/setup_templates/generate-templates/trackers/sprint.xml \
		resources/templates/Tracker_sprint.xml
