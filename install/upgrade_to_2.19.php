<?php

class PluginFormcreatorUpgradeTo2_19
{
    protected Migration $migration;

    /**
     * @param Migration $migration
     */
    public function upgrade(Migration $migration)
    {
        global $DB;

        // Add is_questions_locked field to forms table
        $table = 'glpi_plugin_formcreator_forms';
        if (!$DB->fieldExists($table, 'is_questions_locked')) {
            $migration->addField($table, 'is_questions_locked', 'tinyint(1) NOT NULL DEFAULT 0');
            $migration->migrationOneTable($table);
        }
    }
}
