###############################################################################
# Phpwiki 1.3.12
ALTER TABLE wiki_page ADD cached_html MEDIUMBLOB;

###############################################################################
# Survey enhancement: new question type
INSERT INTO survey_question_types (id, type, rank) VALUES (7,'select_box', '23');
