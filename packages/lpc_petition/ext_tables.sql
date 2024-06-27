#
# Table structure for table 'tx_lpcpetition_entry'
#
CREATE TABLE tx_lpcpetition_domain_model_entry (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	firstname tinytext,
	lastname tinytext,
	address tinytext,
	zip tinytext,
	place tinytext,
	canton char(2) DEFAULT '' NOT NULL,
	country tinytext,
	mail tinytext,
	title tinytext,
	birthday int(11) DEFAULT '0' NOT NULL,
	private tinyint(3) DEFAULT '0' NOT NULL,
	newsletter tinyint(3) DEFAULT '0' NOT NULL,
	allow_reuse tinyint(3) DEFAULT '0' NOT NULL,
	phone varchar(20) DEFAULT '' NOT NULL,
	comment text null,
	field_data text null,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
) ENGINE=InnoDB;

CREATE TABLE tx_lpcpetition_domain_model_field (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	name varchar(64) NOT NULL,
	type varchar(10) NOT NULL,
	options text NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
