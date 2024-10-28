<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2011 - 2021 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFormcreatorCategory extends CommonTreeDropdown
{
   // Activate translation on GLPI 0.85
   var $can_be_translated = true;

   static function canView() {
      if (isAPI()) {
         return true;
      }

      return parent::canView();
   }

   public static function getTypeName($nb = 1) {
      return _n('Form category', 'Form categories', $nb, 'formcreator');
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $env       = new self;
      $found_env = $env->find([static::getForeignKeyField() => $item->getID()]);
      $nb        = count($found_env);
      return self::createTabEntry(self::getTypeName($nb), $nb);
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() != __CLASS__) {
         return;
      }
      $item->showChildren();
   }

   public function getAdditionalFields() {
      return [
         [
            'name'      => KnowbaseItemCategory::getForeignKeyField(),
            'type'      => 'dropdownValue',
            'label'     => __('Knowbase category', 'formcreator'),
            'list'      => false
         ],
         [
            'name'      => $this->getForeignKeyField(),
            'type'      => 'parent',
            'label'     => __('As child of'),
            'list'      => false
         ]
      ];
   }

   /**
    * @param int $rootId id of the subtree root
    * @param bool $helpdeskHome
    * @return array Tree of form categories as nested array
    */
   public static function getCategoryTree($rootId = 0, $helpdeskHome = false) : array {
      global $DB;

      $fcConfig = new PluginFormcreatorConfig();
      $config = $fcConfig->getConfig();

      $cat_table  = PluginFormcreatorCategory::getTable();
      $form_table = PluginFormcreatorForm::getTable();

      $query_faqs = KnowbaseItem::getListRequest([
         'faq'      => '1',
         'contains' => ''
      ]);
      // GLPI 9.5 returns an array
      $subQuery = new DBMysqlIterator($DB);
      $subQuery->buildQuery($query_faqs);
      $query_faqs = $subQuery->getSQL();

      $dbUtils = new DbUtils();
      $entityRestrict = $dbUtils->getEntitiesRestrictCriteria($form_table, "", "", true, false);
      if (count($entityRestrict)) {
         $entityRestrict = [$entityRestrict];
      }

      // Selects categories containing forms or sub-categories
      $categoryFk = self::getForeignKeyField();
      $authorizedForms = (new PluginFormcreatorForm())->showFormList();
      $formsId = implode(',', array_column($authorizedForms['forms'], 'id'));
      $query = <<<SQL
         SELECT DISTINCT
            {$cat_table}.name as name,
            {$cat_table}.id as id, 
            {$cat_table}.plugin_formcreator_categories_id as parent
         FROM {$form_table}
         LEFT JOIN {$cat_table} ON {$cat_table}.id = {$form_table}.plugin_formcreator_categories_id
         WHERE {$form_table}.id IN ({$formsId})
      SQL;
      $result = $DB->query($query);

      $categories = [];
      foreach ($result as $category) {
         $category['name'] = (new DbUtils)->getTreeLeafValueName($cat_table, $category['id'], false, true);
         // Keep the short name only
         // If a symbol > exists in a name, it is saved as an html entity, making the following reliable
         $split = explode(' > ', $category['name']);
         $category['name'] = array_pop($split);
         $categories[$category['id']] = $category;
      }

      // Remove categories that have no items and no children
      // Requires category list to be sorted by level DESC
      foreach ($categories as $index => $category) {
         $children = array_filter(
            $categories,
            function ($element) use ($category) {
               return $category['id'] == $element['parent'];
            }
         );
         $categories[$index]['active'] = $config['default_categories_id'] == $category['id'] ? 1 : 0;
         $categories[$index]['subcategories'] = [];
      }

      // Create root node
      $nodes = [
         'name'            => '',
         'id'              => 0,
         'parent'          => 0,
         'active'          => 0,
         'subcategories'   => [],
      ];
      $flat = [
         0 => &$nodes,
      ];

      // Build from root node to leaves
      $categories = array_reverse($categories);
      foreach ($categories as $item) {
         $flat[$item['id']] = $item;
         $flat[$item['parent']]['subcategories'][] = &$flat[$item['id']];
      }

      return $nodes;
   }

   /**
    * Get available categories
    *
    * @param int $helpdeskHome
    * @return DBmysqlIterator
    */
   public static function  getAvailableCategories($helpdeskHome = 1) : DBmysqlIterator {
      global $DB;

      $result = $DB->request(self::getAvailableCategoriesCriterias($helpdeskHome));

      return $result;
   }

   /**
    * Get available categories
    *
    * @param int $helpdeskHome
    * @return array
    */
   public static function getAvailableCategoriesCriterias($helpdeskHome = 1) : array {
      $cat_table       = PluginFormcreatorCategory::getTable();
      $categoryFk      = PluginFormcreatorCategory::getForeignKeyField();
      $formTable       = PluginFormcreatorForm::getTable();
      $formRestriction = PluginFormcreatorForm::getFormRestrictionCriterias($formTable);

      $formRestriction["$formTable.helpdesk_home"] = $helpdeskHome;

      return [
        'SELECT' => [
           $cat_table => [
              'name', 'id'
           ]
        ],
        'FROM' => $cat_table,
        'INNER JOIN' => [
           $formTable => [
              'FKEY' => [
                 $cat_table => 'id',
                 $formTable => $categoryFk
              ]
           ]
        ],
        'WHERE' => PluginFormcreatorForm::getFormRestrictionCriterias($formTable),
        'GROUPBY' => [
           "$cat_table.id"
        ]
      ];
   }
}
