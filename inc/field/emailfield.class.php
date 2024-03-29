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

use Html;
use Session;
use GlpiPlugin\Formcreator\Exception\ComparisonException;
use PluginFormcreatorAbstractField;

class EmailField extends TextField
{
   public function getDesignSpecializationField(): array {
      $label = '';
      $field = '';

      $additions = '<div class="plugin_formcreator_question_specific">';
      ob_start();
      renderTwigTemplate('macros/wrappedInput.twig', [
         'title' => __('Default value'),
         'input' => [
            'type'  => 'email',
            'id'    => 'default_values',
            'name'    => 'default_values',
            'value' => Html::entities_deep($this->question->fields['default_values']),
            'col_lg' => '12',
            'col_md' => '12',
         ],
      ]);
      $additions .= ob_get_clean();
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
         return $this->value;
      }
      $html = '';
      $id           = $this->question->getID();
      $rand         = mt_rand();
      $fieldName    = 'formcreator_field_' . $id;
      $domId        = $fieldName . '_' . $rand;
      $defaultValue = Html::cleanInputText($this->value);

      ob_start();
      renderTwigTemplate('macros/wrappedInput.twig', [
         'input' => [
            'type'  => 'email',
            'id'    => $domId,
            'name'  => $fieldName,
            'value' => $defaultValue,
            'col_lg' => '12',
            'col_md' => '12',
         ],
      ]);
      $html .= ob_get_clean();
      $html .= Html::scriptBlock("$(function() {
         pluginFormcreatorInitializeEmail('$fieldName', '$rand');
      });");

      return $html;
   }

   public function moveUploads() {
   }

   public function isValidValue($value): bool {
      if ($value === '') {
         return true;
      }

      if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
         Session::addMessageAfterRedirect(
            sprintf(__('This is not a valid e-mail: %s', 'formcreator'), $this->getLabel()),
            false,
            ERROR
         );
         return false;
      }

      return true;
   }

   public static function getName(): string {
      return _n('Email', 'Emails', 1);
   }

   public static function canRequire(): bool {
      return true;
   }

   public function prepareQuestionInputForSave($input) {
      $input['values'] = '';
      $this->value = $input['default_values'];
      return $input;
   }

   public function hasInput($input): bool {
      return isset($input['formcreator_field_' . $this->question->getID()]);
   }

   public function parseAnswerValues($input, $nonDestructive = false): bool {
      $key = 'formcreator_field_' . $this->question->getID();
      if (!isset($input[$key])) {
         return false;
      }
      if (!isset($input[$key])) {
         $input[$key] = '';
      }

      if (!is_string($input[$key])) {
         return false;
      }

      $this->value = $input[$key];
      return true;
   }

   public function getEmptyParameters(): array {
      return [];
   }

   public function equals($value): bool {
      return $this->value == $value;
   }

   public function notEquals($value): bool {
      return !$this->equals($value);
   }

   public function greaterThan($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function lessThan($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function regex($value): bool {
      return (preg_grep($value, $this->value)) ? true : false;
   }

   public function isAnonymousFormCompatible(): bool {
      return true;
   }

   public function getHtmlIcon(): string {
      return '<i class="fa fa-envelope" aria-hidden="true"></i>';
   }

   public function isVisibleField(): bool {
      return true;
   }

   public function isEditableField(): bool {
      return true;
   }

   public function getTranslatableStrings(array $options = []) : array {
      return PluginFormcreatorAbstractField::getTranslatableStrings($options);
   }
}
