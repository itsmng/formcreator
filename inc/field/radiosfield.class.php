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
 * @copyright Copyright © 2011 - 2020 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Formcreator\Field;

use PluginFormcreatorAbstractField;
use Html;
use Session;
use Toolbox;

class RadiosField extends PluginFormcreatorAbstractField
{
   public function isPrerequisites(): bool {
      return true;
   }

   public function getDesignSpecializationField(): array {
      $label = '';
      $field = '';

      $value = json_decode($this->question->fields['values'] ?? '[]');
      if ($value === null || !is_array($value)) {
         $value = [];
      }

      $additions = '<div class="plugin_formcreator_question_specific row">';
      $inputs = [
         __('Default values') . '<small>(' . __('One per line', 'formcreator') . ')</small>' => [
            'type' => 'textarea',
            'name' => 'default_values',
            'id' => 'default_values',
            'cols' => '50',
            'col_lg' => 6,
         ],
         __('Values') . '<small>(' . __('One per line', 'formcreator') . ')</small>' => [
            'type' => 'textarea',
            'name' => 'values',
            'id' => 'values',
            'value' => implode("\r\n", $value),
            'cols' => '50',
            'value' => Html::entities_deep($this->getValueForDesign()),
            'col_lg' => 6,
         ],
      ];
      ob_start();
      foreach ($inputs as $title => $input) {
         renderTwigTemplate('macros/wrappedInput.twig', [
            'title' => $title,
            'input' => $input,
         ]);
      }
      $additions .= ob_get_clean();
      $common = parent::getDesignSpecializationField();
      $additions .= $common['additions'];
      $additions .= '</div>';

      return [
         'label' => $label,
         'field' => $field,
         'additions' => $additions,
         'may_be_empty' => false,
         'may_be_required' => true,
      ];
   }

   public function getRenderedHtml($domain, $canEdit = true): string {
      if (!$canEdit) {
         return __($this->value, $domain);
      }
      $html         = '';
      $id           = $this->question->getID();
      $rand         = mt_rand();
      $fieldName    = 'formcreator_field_' . $id;
      $domId        = $fieldName . '_' . $rand;

      $values = $this->getAvailableValues();
      if (!empty($values)) {
         $html .= '<div class="radios">';
         $i = 0;
         foreach ($values as $value) {
            if ((trim($value) != '')) {
               $i++;
               ob_start();
               renderTwigTemplate('macros/wrappedInput.twig', [
                  'title' => __($value, $domain),
                  'input' => [
                     'type' => 'radio',
                     'name' => $fieldName,
                     'id' => $domId . '_' . $i,
                     'value' => $value,
                     'checked' => ($this->value == $value),
                     'col_lg' => 12,
                     'col_md' => 12,
                     'col_sm' => 12,
                  ]
               ]);
               $html .= ob_get_clean();
            }
         }
         $html .= '</div>';
      }
      $html .= Html::scriptBlock("$(function() {
         pluginFormcreatorInitializeRadios('$fieldName', '$rand');
      });");

      return $html;
   }

   public static function getName(): string {
      return __('Radios', 'formcreator');
   }

   public function prepareQuestionInputForSave($input) {
      if (!isset($input['values']) || empty($input['values'])) {
         Session::addMessageAfterRedirect(
            __('The field value is required:', 'formcreator') . ' ' . $input['name'],
            false,
            ERROR
         );
         return [];
      }

      // trim values
      $input['values'] = $this->trimValue($input['values']);
      $input['default_values'] = trim($input['default_values']);

      return $input;
   }

   public function hasInput($input): bool {
      return isset($input['formcreator_field_' . $this->question->getID()]);
   }

   public function parseAnswerValues($input, $nonDestructive = false): bool {
      $key = 'formcreator_field_' . $this->question->getID();
      if (isset($input[$key])) {
         if (!is_string($input[$key])) {
            return false;
         }
      } else {
         $this->value = '';
         return true;
      }

      $this->value = Toolbox::stripslashes_deep($input[$key]);
      return true;
   }

   public function parseDefaultValue($defaultValue) {
      $this->value = explode('\r\n', $defaultValue);
      $this->value = array_filter($this->value, function ($value) {
         return ($value !== '');
      });
      $this->value = array_shift($this->value);
   }

   public function serializeValue(): string {
      if ($this->value === null || $this->value === '') {
         return '';
      }

      return Toolbox::addslashes_deep($this->value);
   }

   public function deserializeValue($value) {
      $this->value = ($value !== null && $value !== '')
         ? $value
         : '';
   }

   public function getValueForDesign(): string {
      if ($this->value === null) {
         return '';
      }

      return $this->value;
   }

   public function getValueForTargetText($domain, $richText): ?string {
      return __($this->value, $domain);
   }

   public function moveUploads() {
   }

   public function getDocumentsForTarget(): array {
      return [];
   }

   public function isValid(): bool {
      // If the field is required it can't be empty
      if ($this->isRequired() && $this->value == '') {
         Session::addMessageAfterRedirect(
            sprintf(__('A required field is empty: %s', 'formcreator'), $this->getLabel()),
            false,
            ERROR
         );
         return false;
      }

      // All is OK
      return $this->isValidValue($this->value);
   }

   public function isValidValue($value): bool {
      if ($value == '') {
         return true;
      }
      $value = Toolbox::stripslashes_deep($value);
      $value = trim($value);
      return in_array($value, $this->getAvailableValues());
   }

   public static function canRequire(): bool {
      return true;
   }

   public function equals($value): bool {
      return $this->value == $value;
   }

   public function notEquals($value): bool {
      return !$this->equals($value);
   }

   public function greaterThan($value): bool {
      return $this->value > $value;
   }

   public function lessThan($value): bool {
      return !$this->greaterThan($value) && !$this->equals($value);
   }

   public function regex($value): bool {
      return preg_match($value, $this->value) ? true : false;
   }

   public function isAnonymousFormCompatible(): bool {
      return true;
   }

   public function getHtmlIcon(): string {
      return '<i class="fa fa-check-circle" aria-hidden="true"></i>';
   }

   public function isVisibleField(): bool {
      return true;
   }

   public function isEditableField(): bool {
      return true;
   }

   public function getTranslatableStrings(array $options = []) : array {
      $strings = parent::getTranslatableStrings($options);

      $params = [
         'searchText'      => '',
         'id'              => '',
         'is_translated'   => null,
         'language'        => '', // Mandatory if one of is_translated and is_untranslated is false
      ];
      $options = array_merge($params, $options);

      $searchString = Toolbox::stripslashes_deep(trim($options['searchText']));

      foreach ($this->getAvailableValues() as $value) {
         if ($searchString != '' && stripos($value, $searchString) === false) {
            continue;
         }
         $id = \PluginFormcreatorTranslation::getTranslatableStringId($value);
         if ($options['id'] != '' && $id != $options['id']) {
            continue;
         }
         $strings['string'][$id] = $value;
         $strings['id'][$id] = 'string';
      }

      return $strings;
   }
}
