--
-- Table structure for table 'plugin_docman_approval'
--
-- item_id     Id of the item (FK plugin_docman_item (item_id))
-- table_owner User who creates the table (FK user (user_id)) 
-- date        Table creation date 
-- description A text that describe why the approval is required.
-- status      Table activation state: 0 - Disabled / 1 - Enabled / 2 - Closed
-- notification Type of notification: 0 - Disabled / 1 - Once at all / 2 - Sequential
--
DROP TABLE IF EXISTS plugin_docman_approval;
CREATE TABLE plugin_docman_approval (
  item_id INT(11) UNSIGNED NOT NULL,
  table_owner INT(11) UNSIGNED NOT NULL,
  date INT(11) UNSIGNED NULL,
  description TEXT NULL,
  status TINYINT(4) DEFAULT 0 NOT NULL,
  notification TINYINT(4) DEFAULT 0 NOT NULL,
  INDEX item_id (item_id),
  UNIQUE(item_id)
);

--
-- Table structure for table 'plugin_docman_approval_user'
--
-- item_id     Id of the item (FK plugin_docman_item (item_id))
-- reviewer_id Id of user member of the table (FK user (user_id))
-- date        Date of the decision.
-- state       State of the review: 0 - Not Yet / 1 - Approved / 2 - Rejected
-- comment     A text to comment the state.
-- version     The version of the document on approval
--
DROP TABLE IF EXISTS plugin_docman_approval_user;
CREATE TABLE plugin_docman_approval_user (
  item_id INT(11) UNSIGNED NOT NULL,
  reviewer_id INT(11) UNSIGNED NOT NULL,
  rank INT(11) DEFAULT 0 NOT NULL,
  date INT(11) UNSIGNED NULL,
  state TINYINT(4) DEFAULT 0 NOT NULL,
  comment TEXT NULL,
  version INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id, reviewer_id),
  INDEX rank (rank)
);
