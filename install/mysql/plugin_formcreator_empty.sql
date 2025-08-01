-- Database schema
-- Do NOT drop anything here

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_formanswers_id` int(11) NOT NULL DEFAULT '0',
  `plugin_formcreator_questions_id`   int(11) NOT NULL DEFAULT '0',
  `answer`                            longtext,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_formanswers_id` (`plugin_formcreator_formanswers_id`),
  INDEX `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_categories` (
  `id`                               int(11) NOT NULL AUTO_INCREMENT,
  `name`                             varchar(255) NOT NULL DEFAULT '',
  `comment`                          text,
  `completename`                     varchar(255) DEFAULT NULL,
  `plugin_formcreator_categories_id` int(11) NOT NULL DEFAULT '0',
  `level`                            int(11) NOT NULL DEFAULT '1',
  `sons_cache`                       longtext,
  `ancestors_cache`                  longtext,
  `knowbaseitemcategories_id`        int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `name` (`name`),
  INDEX `knowbaseitemcategories_id` (`knowbaseitemcategories_id`),
  INDEX `plugin_formcreator_categories_id` (`plugin_formcreator_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_entityconfigs` (
  `id`                int(11) NOT NULL,
  `replace_helpdesk`  int(11) NOT NULL DEFAULT '-2',
  `sort_order`        int(11) NOT NULL DEFAULT '-2',
  `is_kb_separated`   int(11) NOT NULL DEFAULT '-2',
  `is_search_visible` int(11) NOT NULL DEFAULT '-2',
  `is_header_visible` int(11) NOT NULL DEFAULT '-2',
  `header`            text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_forms` (
  `id`                               int(11) NOT NULL AUTO_INCREMENT,
  `name`                             varchar(255) NOT NULL DEFAULT '',
  `entities_id`                      int(11) NOT NULL DEFAULT '0',
  `is_recursive`                     tinyint(1) NOT NULL DEFAULT '0',
  `icon_type`                        tinyint(1) NOT NULL DEFAULT '0',
  `icon`                             varchar(255) NOT NULL DEFAULT '',
  `icon_color`                       varchar(255) NOT NULL DEFAULT '',
  `background_color`                 varchar(255) NOT NULL DEFAULT '',
  `access_rights`                    tinyint(1) NOT NULL DEFAULT '1',
  `description`                      varchar(255) DEFAULT NULL,
  `content`                          longtext,
  `plugin_formcreator_categories_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_active`                        tinyint(1) NOT NULL DEFAULT '0',
  `language`                         varchar(255) NOT NULL DEFAULT '',
  `helpdesk_home`                    tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted`                       tinyint(1) NOT NULL DEFAULT '0',
  `validation_required`              tinyint(1) NOT NULL DEFAULT '0',
  `usage_count`                      int(11) NOT NULL DEFAULT '0',
  `is_default`                       tinyint(1) NOT NULL DEFAULT '0',
  `is_captcha_enabled`               tinyint(1) NOT NULL DEFAULT '0',
  `show_rule`                        int(11) NOT NULL DEFAULT '1' COMMENT 'Conditions setting to show the submit button',
  `uuid`                             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `entities_id` (`entities_id`),
  INDEX `plugin_formcreator_categories_id` (`plugin_formcreator_categories_id`),
  FULLTEXT KEY `Search` (`name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_formanswers` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `name`                        varchar(255) NOT NULL DEFAULT '',
  `entities_id`                 int(11) NOT NULL DEFAULT '0',
  `is_recursive`                tinyint(1) NOT NULL DEFAULT '0',
  `plugin_formcreator_forms_id` int(11) NOT NULL,
  `requester_id`                int(11) DEFAULT NULL,
  `users_id_validator`          int(11) NOT NULL DEFAULT '0' COMMENT 'User in charge of validation',
  `groups_id_validator`         int(11) NOT NULL DEFAULT '0' COMMENT 'Group in charge of validation',
  `request_date`                timestamp NULL,
  `status`                      int(11) NOT NULL DEFAULT '101',
  `comment`                     text,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`),
  INDEX `entities_id_is_recursive` (`entities_id`, `is_recursive`),
  INDEX `requester_id` (`requester_id`),
  INDEX `users_id_validator` (`users_id_validator`),
  INDEX `groups_id_validator` (`groups_id_validator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_forms_profiles` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int(11) NOT NULL,
  `profiles_id`                 int(11) NOT NULL,
  `uuid`                        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`profiles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_forms_validators` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id` int(11) NOT NULL,
  `itemtype`                    varchar(255) NOT NULL DEFAULT '',
  `items_id`                    int(11) NOT NULL,
  `uuid`                        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_questions` (
  `id`                             int(11) NOT NULL AUTO_INCREMENT,
  `name`                           varchar(255) NOT NULL DEFAULT '',
  `plugin_formcreator_sections_id` int(11) NOT NULL,
  `fieldtype`                      varchar(30) NOT NULL DEFAULT 'text',
  `required`                       tinyint(1) NOT NULL DEFAULT '0',
  `show_empty`                     tinyint(1) NOT NULL DEFAULT '0',
  `default_values`                 mediumtext,
  `values`                         mediumtext,
  `description`                    mediumtext,
  `row`                            int(11) NOT NULL DEFAULT '0',
  `col`                            int(11) NOT NULL DEFAULT '0',
  `width`                          int(11) NOT NULL DEFAULT '0',
  `show_rule`                      int(11) NOT NULL DEFAULT '1',
  `uuid`                           varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_sections_id` (`plugin_formcreator_sections_id`),
  FULLTEXT KEY `Search` (`name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_conditions` (
	`id`                              int(11) NOT NULL AUTO_INCREMENT,
	`itemtype`                        varchar(255) NOT NULL DEFAULT '' COMMENT 'itemtype of the item affected by the condition',
	`items_id`                        int(11) NOT NULL DEFAULT '0' COMMENT 'item ID of the item affected by the condition',
	`plugin_formcreator_questions_id` int(11) NOT NULL DEFAULT '0' COMMENT 'question to test for the condition',
	`show_condition`                  int(11) NOT NULL DEFAULT '0',
	`show_value`                      varchar(255) NULL DEFAULT NULL,
	`show_logic`                      int(11) NOT NULL DEFAULT '1',
	`order`                           int(11) NOT NULL DEFAULT '1',
	`uuid`                            varchar(255) NULL DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`),
  INDEX `item` (`itemtype`, `items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_sections` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `name`                        varchar(255) NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int(11) NOT NULL,
  `order`                       int(11) NOT NULL DEFAULT '0',
  `show_rule`                   int(11) NOT NULL DEFAULT '1',
  `uuid`                        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_targetchanges` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `name`                        varchar(255) NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
  `target_name`                 varchar(255) NOT NULL DEFAULT '',
  `changetemplates_id`          int(11) NOT NULL DEFAULT '0',
  `content`                     longtext,
  `impactcontent`               longtext,
  `controlistcontent`           longtext,
  `rolloutplancontent`          longtext,
  `backoutplancontent`          longtext,
  `checklistcontent`            longtext,
  `due_date_rule`               int(11) NOT NULL DEFAULT '1',
  `due_date_question`           int(11) DEFAULT NULL,
  `due_date_value`              tinyint(4) DEFAULT NULL,
  `due_date_period`             int(11) NOT NULL DEFAULT '0',
  `urgency_rule`                int(11) NOT NULL DEFAULT '1',
  `urgency_question`            int(11) NOT NULL DEFAULT '0',
  `validation_followup`         tinyint(1) NOT NULL DEFAULT '1',
  `destination_entity`         int(11) NOT NULL DEFAULT '1',
  `destination_entity_value`   int(11) DEFAULT NULL,
  `tag_type`                   int(11) NOT NULL DEFAULT '1',
  `tag_questions`              varchar(255) NOT NULL,
  `tag_specifics`              varchar(255) NOT NULL,
  `category_rule`              int(11) NOT NULL DEFAULT '1',
  `category_question`          int(11) NOT NULL DEFAULT '0',
  `show_rule`                  int(11) NOT NULL DEFAULT '1',
  `sla_rule`                   int(11) NOT NULL DEFAULT '1',
  `sla_question_tto`           int(11) NOT NULL DEFAULT '0',
  `sla_question_ttr`           int(11) NOT NULL DEFAULT '0',
  `ola_rule`                   int(11) NOT NULL DEFAULT '1',
  `ola_question_tto`           int(11) NOT NULL DEFAULT '0',
  `ola_question_ttr`           int(11) NOT NULL DEFAULT '0',
  `uuid`                       varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_targettickets` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `name`                        varchar(255) NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
  `target_name`                 varchar(255) NOT NULL DEFAULT '',
  `type_rule`                   int(11) NOT NULL DEFAULT '0',
  `type_question`               int(11) NOT NULL DEFAULT '0',
  `tickettemplates_id`          int(11) NOT NULL DEFAULT '0',
  `content`                     longtext,
  `due_date_rule`               int(11) NOT NULL DEFAULT '1',
  `due_date_question`           int(11) DEFAULT NULL,
  `due_date_value`              tinyint(4) DEFAULT NULL,
  `due_date_period`             int(11) NOT NULL DEFAULT '0',
  `urgency_rule`                int(11) NOT NULL DEFAULT '1',
  `urgency_question`            int(11) NOT NULL DEFAULT '0',
  `validation_followup`         tinyint(1) NOT NULL DEFAULT '1',
  `destination_entity`          int(11) NOT NULL DEFAULT '1',
  `destination_entity_value`    int(11) DEFAULT NULL,
  `tag_type`                    int(11) NOT NULL DEFAULT '1',
  `tag_questions`               varchar(255) NOT NULL,
  `tag_specifics`               varchar(255) NOT NULL,
  `category_rule`               int(11) NOT NULL DEFAULT '1',
  `category_question`           int(11) NOT NULL DEFAULT '0',
  `associate_rule`              int(11) NOT NULL DEFAULT '1',
  `associate_question`          int(11) NOT NULL DEFAULT '0',
  `location_rule`               int(11) NOT NULL DEFAULT '1',
  `location_question`           int(11) NOT NULL DEFAULT '0',
  `show_rule`                   int(11) NOT NULL DEFAULT '1',
  `sla_rule`                    int(11) NOT NULL DEFAULT '1',
  `sla_question_tto`            int(11) NOT NULL DEFAULT '0',
  `sla_question_ttr`            int(11) NOT NULL DEFAULT '0',
  `ola_rule`                    int(11) NOT NULL DEFAULT '1',
  `ola_question_tto`            int(11) NOT NULL DEFAULT '0',
  `ola_question_ttr`            int(11) NOT NULL DEFAULT '0',
  `uuid`                        varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `tickettemplates_id` (`tickettemplates_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_targetworkflows` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `name`                        varchar(255) NOT NULL DEFAULT '',
  `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
  `target_name`                 varchar(255) NOT NULL DEFAULT '',
  `type_rule`                   int(11) NOT NULL DEFAULT '0',
  `type_question`               int(11) NOT NULL DEFAULT '0',
  `content`                     longtext,
  `uuid`                        varchar(255) DEFAULT NULL,
  `workflows_id`                int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_targets_actors` (
  `id`               int(11) NOT NULL AUTO_INCREMENT,
  `itemtype`         varchar(255) DEFAULT NULL,
  `items_id`         int(11) NOT NULL,
  `actor_role`       int(11) NOT NULL DEFAULT '1',
  `actor_type`       int(11) NOT NULL DEFAULT '1',
  `actor_value`      int(11) DEFAULT NULL,
  `use_notification` tinyint(1) NOT NULL DEFAULT '1',
  `uuid`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `item` (`itemtype`, `items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_issues` (
  `id`                    int(11)       NOT NULL AUTO_INCREMENT,
  `name`                  varchar(255)  NOT NULL DEFAULT '',
  `display_id`            varchar(255)  NOT NULL,
  `items_id`              int(11)       NOT NULL DEFAULT '0',
  `itemtype`              varchar(255)  NOT NULL DEFAULT '',
  `status`                varchar(255)  NOT NULL DEFAULT '',
  `date_creation`         timestamp     NULL,
  `date_mod`              timestamp     NULL,
  `entities_id`           int(11)       NOT NULL DEFAULT '0',
  `is_recursive`          tinyint(1)    NOT NULL DEFAULT '0',
  `requester_id`          int(11)       NOT NULL DEFAULT '0',
  `users_id_validator`    int(11)       NOT NULL DEFAULT '0',
  `groups_id_validator`   int(11)       NOT NULL DEFAULT '0',
  `comment`               longtext,
  `users_id_recipient`    int(11)        NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `item` (`itemtype`, `items_id`),
  INDEX `entities_id` (`entities_id`),
  INDEX `requester_id` (`requester_id`),
  INDEX `users_id_validator` (`users_id_validator`),
  INDEX `groups_id_validator` (`groups_id_validator`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_items_targettickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_targettickets_id` int(11)      NOT NULL DEFAULT '0',
  `link`                                int(11)      NOT NULL DEFAULT '0',
  `itemtype`                            varchar(255) NOT NULL DEFAULT '',
  `items_id`                            int(11)      NOT NULL DEFAULT '0',
  `uuid`                                varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_targettickets_id` (`plugin_formcreator_targettickets_id`),
  INDEX `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_questiondependencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id`   int(11)       NOT NULL,
  `plugin_formcreator_questions_id_2` int(11)       NOT NULL,
  `fieldname`                         varchar(255)  DEFAULT NULL,
  `uuid`                              varchar(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`),
  INDEX `plugin_formcreator_questions_id_2` (`plugin_formcreator_questions_id_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_questionregexes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id`   int(11)       NOT NULL,
  `regex`                             text          DEFAULT NULL,
  `fieldname`                         varchar(255)  DEFAULT NULL,
  `uuid`                              varchar(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_questionranges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_questions_id`   int(11)       NOT NULL,
  `range_min`                         varchar(255)  DEFAULT NULL,
  `range_max`                         varchar(255)  DEFAULT NULL,
  `fieldname`                         varchar(255)  DEFAULT NULL,
  `uuid`                              varchar(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `plugin_formcreator_questions_id` (`plugin_formcreator_questions_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_forms_languages` (
  `id`                                int(11)       NOT NULL AUTO_INCREMENT,
  `plugin_formcreator_forms_id`       int(11)       NOT NULL,
  `name`                              varchar(255)  DEFAULT NULL,
  `comment`                           text,
  `uuid`                              varchar(255)  DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_configs` (
  `id`                int(11) NOT NULL AUTO_INCREMENT,
  `name`              varchar(255) NOT NULL DEFAULT '',
  `value`             text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT IGNORE INTO `glpi_plugin_formcreator_configs` (`name`, `value`) VALUES
  ('enable_profile_info', 1),
  ('collapse_menu', 0),
  ('default_categories_id', 0),
  ('enable_saved_search', 1),
  ('see_all', 1),
  ('enable_ticket_status_counter', 1);
