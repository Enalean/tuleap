CREATE TABLE plugin_docman_item_lock (
 item_id INT(11) UNSIGNED NOT NULL DEFAULT 0,
 user_id INT(11) NOT NULL DEFAULT 0,
 lock_date INT(11) UNSIGNED NOT NULL DEFAULT 0,
 PRIMARY KEY (item_id)
);





