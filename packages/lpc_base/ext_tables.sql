
CREATE TABLE sys_file_reference (
	header_ypos tinyint null,
	header_xpos tinyint null
);

CREATE TABLE pages (
	social_links int NOT NULL DEFAULT 0
);

CREATE TABLE tx_lpcbase_domain_model_sociallink (
	link varchar(255) NOT NULL DEFAULT ''
);
