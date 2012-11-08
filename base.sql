CREATE  TABLE IF NOT EXISTS `default_schema`.`ip` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `ip` VARCHAR(15) NOT NULL ,
  `port` INT(4) NOT NULL DEFAULT '8080' ,
  `login` VARCHAR(32) NOT NULL ,
  `pass` VARCHAR(32) NOT NULL ,
  `flag` TINYINT(1) NOT NULL ,
  `time` INT(11) NOT NULL ,
  `count` INT(3) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `ip` (`ip` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`ip_d_ban` (
  `ip` VARCHAR(22) NOT NULL ,
  `time` INT(11) NOT NULL ,
  PRIMARY KEY (`ip`) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`ip_d_list` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `url` VARCHAR(255) NOT NULL ,
  `url_auth` VARCHAR(255) NOT NULL ,
  `login` VARCHAR(255) NOT NULL ,
  `passwd` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`log_errors` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `parse_id` INT(11) UNSIGNED NOT NULL ,
  `time` INT(11) NOT NULL ,
  `title` VARCHAR(128) NOT NULL ,
  `url` VARCHAR(512) NOT NULL ,
  `value` VARCHAR(128) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_tovar_log_parse1` (`parse_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`log_ip` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `parse_id` INT(11) UNSIGNED NOT NULL ,
  `time` INT(11) UNSIGNED NOT NULL ,
  `memory` INT(11) UNSIGNED NOT NULL ,
  `ip` VARCHAR(22) NOT NULL ,
  `url` VARCHAR(512) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_ip_log_parse1` (`parse_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`log_parse` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT(11) UNSIGNED NOT NULL ,
  `region_id` INT(4) UNSIGNED NOT NULL ,
  `shops_id` INT(11) UNSIGNED NOT NULL ,
  `time_start` INT(11) UNSIGNED NOT NULL ,
  `time_stop` INT(11) UNSIGNED NULL DEFAULT NULL ,
  `rows_plan` INT(11) UNSIGNED NOT NULL ,
  `rows_done` INT(11) UNSIGNED NOT NULL ,
  `b_title` TINYINT(1) NOT NULL ,
  `proxy` VARCHAR(24) NOT NULL ,
  `file` VARCHAR(256) NOT NULL ,
  `pid` INT(11) UNSIGNED NOT NULL ,
  `user_ip` VARCHAR(32) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_parse_users` (`user_id` ASC) ,
  INDEX `fk_log_parse_shops1` (`shops_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`log_pid` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `parse_id` INT(11) UNSIGNED NOT NULL ,
  `pid` INT(11) UNSIGNED NOT NULL ,
  `time_start` INT(11) UNSIGNED NOT NULL ,
  `time_stop` INT(11) UNSIGNED NULL DEFAULT NULL ,
  `rows_plan` INT(11) UNSIGNED NOT NULL ,
  `rows_done` INT(11) UNSIGNED NOT NULL ,
  `aborted` TINYINT(1) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_log_pid_log_parse1` (`parse_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`queryPrice` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `parse_id` INT(11) UNSIGNED NOT NULL ,
  `pid_id` INT(11) UNSIGNED NOT NULL ,
  `query_id` INT(11) UNSIGNED NOT NULL ,
  `title` VARCHAR(256) NOT NULL ,
  `mvic` FLOAT UNSIGNED NOT NULL ,
  `moic` FLOAT UNSIGNED NOT NULL ,
  `MPI` FLOAT UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`resultPrice` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` INT(11) UNSIGNED NOT NULL ,
  `region_id` INT(4) UNSIGNED NOT NULL ,
  `shops_id` INT(11) UNSIGNED NOT NULL ,
  `parse_id` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(255) NOT NULL DEFAULT '' ,
  `minPrice` FLOAT UNSIGNED NOT NULL DEFAULT '0' ,
  `maxPrice` FLOAT UNSIGNED NOT NULL DEFAULT '0' ,
  `midiPrice` FLOAT UNSIGNED NOT NULL DEFAULT '0' ,
  `query_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `link` VARCHAR(255) NOT NULL DEFAULT '' ,
  `pos` VARCHAR(255) NOT NULL DEFAULT '' ,
  `mvic` FLOAT NOT NULL DEFAULT '0' ,
  `moic` FLOAT NOT NULL DEFAULT '0' ,
  `MPI` FLOAT NOT NULL ,
  `dif` INT(11) NOT NULL DEFAULT '0' ,
  `five_shop` TEXT NOT NULL ,
  `ten_shop` TEXT NOT NULL ,
  `obsug` TINYINT(4) NOT NULL DEFAULT '0' ,
  `otziv` TINYINT(4) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_CITYID_price_users1` (`user_id` ASC) ,
  INDEX `fk_price_shops1` (`shops_id` ASC) ,
  INDEX `fk_price_log_parse1` (`parse_id` ASC) )
ENGINE = MyISAM
AUTO_INCREMENT = 1
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`regions` (
  `id` INT(11) UNSIGNED NOT NULL ,
  `sv_reg` INT(11) UNSIGNED NOT NULL ,
  `name` VARCHAR(32) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`settings` (
  `name` VARCHAR(32) NOT NULL ,
  `value` VARCHAR(128) NOT NULL ,
  PRIMARY KEY (`name`) )
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(32) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM
AUTO_INCREMENT = 100
DEFAULT CHARACTER SET = utf8;

CREATE  TABLE IF NOT EXISTS `default_schema`.`platform` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(36) NOT NULL ,
  `encoding` VARCHAR(12) NOT NULL ,
  `price_flag` TINYINT(1) UNSIGNED NOT NULL ,
  `rate_flag` TINYINT(1) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = MyISAM
AUTO_INCREMENT = 28
DEFAULT CHARACTER SET = utf8;

# Filling

INSERT INTO `default_schema`.`settings` (`name`,`value`) VALUES
('SESSION_BREAK_END','20000'),
('TIMEOUT_MIN','500'),
('TIMEOUT_MAX','2000'),
('WAITING_TIME','25'),
('PROXY_COMPOMENTED_AFTER_FALSE','5'),
('USE_TOR','false'),
('IP_MAX_QUERIES','600');

INSERT INTO `default_schema`.`users` (`id`, `name`) VALUES ('1', 'test');

INSERT INTO `default_schema`.`regions` (`id`, `sv_reg`, `name`) VALUES
('213', '1', 'Москва'),
('2', '2', 'СПБ');

INSERT INTO `default_schema`.`platform` (`id`, `title`, `encoding`, `price_flag`, `rate_flag`) VALUES
('1', 'yandex', 'utf-8', 1, 1);