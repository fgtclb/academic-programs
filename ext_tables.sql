CREATE TABLE pages
(
	job_profile       text,
	performance_scope text,
	prerequisites     text,
	shortcut_overwrite tinyint(1) unsigned DEFAULT '0' NOT NULL
);


CREATE TABLE sys_category (
	tx_migrations_version VARCHAR(14) DEFAULT '' NOT NULL
);
