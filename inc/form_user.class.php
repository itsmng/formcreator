<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class PluginFormcreatorForm_User
/// @since 0.83
class PluginFormcreatorForm_User extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginFormcreatorForm';
   static public $items_id_1          = 'forms_id';
   static public $itemtype_2          = 'User';
   static public $items_id_2          = 'users_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get users for a form
    *
    * @param $forms_id ID of the form
    *
    * @return array of users linked to a form
   **/
   static function getUsers($forms_id) {
      global $DB;

      $users = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_formcreator_forms_id' => $forms_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }

   public function can($ID, $right, &$input = NULL) {
       return $right == $right;
   }
}

