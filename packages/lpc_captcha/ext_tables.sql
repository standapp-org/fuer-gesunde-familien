
CREATE TABLE tx_captcha_ip (
	ip varbinary(16),
	tstamp int unsigned not null,
	bot tinyint(1) not null,
	spammer tinyint(1) not null,
	reported int(1) unsigned null,
	abuseipdb_data JSON NULL,
	country char(2) null,

	PRIMARY KEY (ip)
);
