<?php
/**
 * Classe pour gérer les liens externes personnels par utilisateur
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFormcreatorExternallink extends CommonDBTM
{
   public static $rightname = 'ticket';

   public static function getTypeName($nb = 0) {
      return _n('External link', 'External links', $nb, 'formcreator');
   }

   public static function getTable($classname = null) {
      return 'glpi_plugin_formcreator_externallinks';
   }

   /**
    * Installer la table
    */
   public static function install(Migration $migration) {
      global $DB;
      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `users_id` int(11) NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `url` varchar(1024) NOT NULL DEFAULT '',
            `icon` varchar(255) DEFAULT 'fa-external-link',
            `position` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `users_id` (`users_id`),
            KEY `position` (`position`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

         $DB->queryOrDie($query, $DB->error());
      }
      return true;
   }

   /**
    * Récupérer les liens de l'utilisateur connecté
    */
   public static function getMyLinks() {
      global $DB;

      $userId = Session::getLoginUserID();
      if (!$userId) {
         return [];
      }

      $iterator = $DB->request([
         'SELECT' => ['id', 'name', 'url', 'icon'],
         'FROM'   => self::getTable(),
         'WHERE'  => ['users_id' => $userId],
         'ORDER'  => 'position ASC, name ASC'
      ]);

      $links = [];
      foreach ($iterator as $data) {
         $links[] = $data;
      }
      return $links;
   }

   /**
    * Ajouter un lien pour l'utilisateur connecté
    */
   public static function addMyLink($name, $url, $icon = 'fa-external-link') {
      global $DB;

      $userId = Session::getLoginUserID();
      if (!$userId) {
         return false;
      }

      // Compter les liens existants pour définir la position
      $count = countElementsInTable(self::getTable(), ['users_id' => $userId]);

      $result = $DB->insert(self::getTable(), [
         'users_id' => $userId,
         'name'     => $name,
         'url'      => $url,
         'icon'     => $icon ?: 'fa-external-link',
         'position' => $count + 1
      ]);

      if ($result) {
         return $DB->insertId();
      }

      return false;
   }

   /**
    * Supprimer un lien (seulement si c'est le sien)
    */
   public static function deleteMyLink($id) {
      global $DB;

      $userId = Session::getLoginUserID();
      if (!$userId) {
         return false;
      }

      return $DB->delete(self::getTable(), [
         'id'       => $id,
         'users_id' => $userId
      ]);
   }
}
