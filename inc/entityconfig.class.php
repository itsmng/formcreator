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

/**
 *
 * @since 0.1.0
 */
class PluginFormcreatorEntityconfig extends CommonDBTM {

   const CONFIG_PARENT = -2;

   const CONFIG_GLPI_HELPDSK = 0;
   const CONFIG_SIMPLIFIED_SERVICE_CATALOG = 1;
   const CONFIG_EXTENDED_SERVICE_CATALOG = 2;

   const CONFIG_SORT_POPULARITY   = 0;
   const CONFIG_SORT_ALPHABETICAL = 1;

   const CONFIG_KB_MERGED = 0;
   const CONFIG_KB_DISTINCT = 1;

   const CONFIG_SEARCH_HIDDEN = 0;
   const CONFIG_SEARCH_VISIBLE = 1;

   const CONFIG_HEADER_HIDDEN = 0;
   const CONFIG_HEADER_VISIBLE = 1;

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory                   = true;

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $tabNames = [];
      if (!$withtemplate) {
         if (Session::haveRight(Entity::$rightname, UPDATE) && $item->getType() == Entity::getType()) {
            $tabNames[1] = _n('Form', 'Forms', 2, 'formcreator');
         }
      }
      return $tabNames;
   }

   public static function getEnumHelpdeskMode() : array {
      return [
         self::CONFIG_PARENT                     => __('Inheritance of the parent entity'),
         self::CONFIG_GLPI_HELPDSK               => __('GLPi\'s helpdesk', 'formcreator'),
         self::CONFIG_SIMPLIFIED_SERVICE_CATALOG => __('Service catalog simplified', 'formcreator'),
         self::CONFIG_EXTENDED_SERVICE_CATALOG   => __('Service catalog extended', 'formcreator'),
      ];
   }

   public static function getEnumSort() : array {
      return [
         self::CONFIG_PARENT            => __('Inheritance of the parent entity'),
         self::CONFIG_SORT_POPULARITY   => __('Popularity sort', 'formcreator'),
         self::CONFIG_SORT_ALPHABETICAL => __('Alphabetic sort', 'formcreator'),
      ];
   }

   public static function getEnumKbMode() : array {
      return [
         self::CONFIG_PARENT      => __('Inheritance of the parent entity'),
         self::CONFIG_KB_MERGED   => __('Merged with Forms', 'formcreator'),
         self::CONFIG_KB_DISTINCT => __('Distinct menu entry', 'formcreator'),
      ];
   }

   public static function getEnumSearchVisibility() : array {
      return [
         self::CONFIG_PARENT         => __('Inheritance of the parent entity'),
         self::CONFIG_SEARCH_VISIBLE => __('Visible', 'formcreator'),
         self::CONFIG_SEARCH_HIDDEN  => __('Hidden', 'formcreator'),
      ];
   }

   public static function getEnumheaderVisibility() : array {
      return [
         self::CONFIG_PARENT         => __('Inheritance of the parent entity'),
         self::CONFIG_HEADER_VISIBLE => __('Visible', 'formcreator'),
         self::CONFIG_HEADER_HIDDEN  => __('Hidden', 'formcreator'),
      ];
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Entity') {
         $config = new self();
         $config->showFormForEntity($item);
      }
   }

   public function prepareInputForAdd($input) {
      $input['header'] = $input['header'] ?? '';

      $config = Toolbox::getHtmLawedSafeConfig();
      $input['header'] = htmLawed($input['header'], $config);

      return $input;
   }

   public function prepareInputForUpdate($input) {
      $input['header'] = $input['header'] ?? '';

      $config = Toolbox::getHtmLawedSafeConfig();
      $input['header'] = htmLawed($input['header'], $config);

      return $input;
   }

   public function showFormForEntity(Entity $entity) {
      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      if (!$this->getFromDB($ID)) {
         $this->add([
            'id'                => $ID,
            'replace_helpdesk'  => self::CONFIG_PARENT,
            'is_kb_separated'   => self::CONFIG_PARENT,
            'is_search_visible' => self::CONFIG_PARENT,
            'is_header_visible' => self::CONFIG_PARENT,
            'sort_order'        => self::CONFIG_PARENT,
         ]);
      }

      $canedit = Entity::canUpdate() && $entity->canUpdateItem();
      
      $helpdeskElements = self::getEnumHelpdeskMode();
      if ($ID == 0) {
         unset($helpdeskElements[self::CONFIG_PARENT]);
      }
      $sortElements = self::getEnumSort();
      if ($ID == 0) {
         unset($sortElements[self::CONFIG_PARENT]);
      }
      $kbElements = self::getEnumKbMode();
      if ($ID == 0) {
         unset($kbElements[self::CONFIG_PARENT]);
      }
      $searchVisibilityElements = self::getEnumSearchVisibility();
      if ($ID == 0) {
         unset($searchVisibilityElements[self::CONFIG_PARENT]);
      }
      $headerVisibilityElements = self::getEnumheaderVisibility();
      if ($ID == 0) {
         unset($headerVisibilityElements[self::CONFIG_PARENT]);
      }

      $form = [
         'action' => $canedit ? Toolbox::getItemTypeFormURL(__CLASS__) : '',
         'buttons' => [
            $canedit ? [
               'name' => 'update',
               'value' => _sx('button', 'Save'),
               'class' => 'btn btn-secondary'
            ] : []
         ],
         'content' => [
            __('Helpdesk', 'formcreator') => [
               'visible' => true,
               'inputs' => [
                  [
                     'type' => 'hidden',
                     'name' => 'id',
                     'value' => $ID,
                  ],
                  __('Helpdesk mode', 'formcreator') => [
                     'type' => 'select',
                     'name' => 'replace_helpdesk',
                     'value' => $this->fields['replace_helpdesk'],
                     'values' => $helpdeskElements,
                  ],
                  __('Sort order', 'formcreator') => [
                     'type' => 'select',
                     'name' => 'sort_order',
                     'value' => $this->fields['sort_order'],
                     'values' => $sortElements,
                  ],
                  __('Knowledge base', 'formcreator') => [
                     'type' => 'select',
                     'name' => 'is_kb_separated',
                     'value' => $this->fields['is_kb_separated'],
                     'values' => $kbElements,
                  ],
                  __('Search', 'formcreator') => [
                     'type' => 'select',
                     'name' => 'is_search_visible',
                     'value' => $this->fields['is_search_visible'],
                     'values' => $searchVisibilityElements,
                  ],
                  __('Header message', 'formcreator') => [
                     'type' => 'select',
                     'name' => 'is_header_visible',
                     'value' => $this->fields['is_header_visible'],
                     'values' => $headerVisibilityElements,
                  ],
                  __('Header', 'formcreator') => [
                     'type' => 'richtextarea',
                     'name' => 'header',
                     'value' => $this->fields['header'],
                     'display' => false,
                     'col_lg' => 12,
                     'col_md' => 12,
                  ],
               ]
            ]
         ]
      ];
      renderTwigForm($form);
   }

   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'              => '3',
         'table'           => self::getTable(),
         'name'            => __('Helpdesk mode', 'formcreator'),
         'field'           => 'replace_helpdesk',
         'datatype'        => 'integer',
         'nosearch'        => true,
         'massiveaction'   => false,
      ];

      $tab[] = [
         'id'              => '4',
         'table'           => self::getTable(),
         'name'            => __('Sort order', 'formcreator'),
         'field'           => 'sort_order',
         'datatype'        => 'integer',
         'nosearch'        => true,
         'massiveaction'   => false,
      ];

      $tab[] = [
         'id'              => '5',
         'table'           => self::getTable(),
         'name'            => __('Knowledge base', 'formcreator'),
         'field'           => 'is_kb_separated',
         'datatype'        => 'integer',
         'nosearch'        => true,
         'massiveaction'   => false,
      ];

      $tab[] = [
         'id'              => '6',
         'table'           => self::getTable(),
         'name'            => __('Display search field', 'formcreator'),
         'field'           => 'is_search_visible',
         'datatype'        => 'integer',
         'nosearch'        => true,
         'massiveaction'   => false,
      ];

      $tab[] = [
         'id'              => '7',
         'table'           => self::getTable(),
         'name'            => __('Display header', 'formcreator'),
         'field'           => 'is_header_visible',
         'datatype'        => 'integer',
         'nosearch'        => true,
         'massiveaction'   => false,
      ];

      $tab[] = [
         'id'              => '8',
         'table'           => self::getTable(),
         'name'            => __('Header', 'formcreator'),
         'field'           => 'header',
         'datatype'        => 'text',
         'nosearch'        => true,
         'massiveaction'   => true,
      ];

      return $tab;
   }

   /**
    * Retrieve data of current entity or parent entity
    *
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $fieldref        string   name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval        string   name of the field that we want value (default '')
    * @param $default_value   integer  value to return (default -2)
    */
   static function getUsedConfig($fieldref, $entities_id, $fieldval = '', $default_value = -2) {

      // for calendar
      if (empty($fieldval)) {
         $fieldval = $fieldref;
      }

      $entity = new Entity();
      $entityConfig = new self();
      // Search in entity data of the current entity
      if ($entity->getFromDB($entities_id)) {
         // Value is defined : use it
         if ($entityConfig->getFromDB($entities_id)) {
            if (is_numeric($default_value)
                  && ($entityConfig->fields[$fieldref] != self::CONFIG_PARENT)) {
                     return $entityConfig->fields[$fieldval];
            }
            if (!is_numeric($default_value)) {
               return $entityConfig->fields[$fieldval];
            }

         }
      }

      // Entity data not found or not defined : search in parent one
      if ($entities_id > 0) {

         if ($entity->getFromDB($entities_id)) {
            $ret = self::getUsedConfig($fieldref, $entity->fields['entities_id'], $fieldval,
                  $default_value);
            return $ret;

         }
      }

      return $default_value;
   }
}
