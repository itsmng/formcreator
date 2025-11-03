<?php

class PluginFormcreatorUpgradeTo2_17
{
    protected Migration $migration;

    /**
     * @param Migration $migration
     */
    public function upgrade(Migration $migration)
    {
        global $DB;

        $query = <<<SQL
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
SQL;

        $DB->query($query) or plugin_formcreator_upgrade_error($migration);
    }
}