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
use Document;
use Session;
use PluginFormcreatorForm;
use GlpiPlugin\Formcreator\Exception\ComparisonException;
use ItsmngUploadHandler;

class FileField extends PluginFormcreatorAbstractField
{
   private $uploadData = [];
   private $uploads = [];

   public function isPrerequisites(): bool {
      return true;
   }

   public function setUploads($uploads) {
      $this->uploads = [];
      if (!is_array($uploads)) {
         return;
      }

      foreach ($uploads as $upload) {
         if (
            is_array($upload)
            && isset($upload['path'], $upload['name'])
         ) {
            $this->uploads[] = $upload;
         }
      }
   }

   public function getRenderedHtml($domain, $canEdit = true): string {
      global $CFG_GLPI;

      if (!$canEdit) {
         $html = '';
         $doc = new Document();
         $answer = $this->uploadData;
         if (!is_array($this->uploadData)) {
            $answer = [$this->uploadData];
         }
         foreach ($answer as $item) {
            if (is_numeric($item) && $doc->getFromDB($item)) {
               $html .= $doc->getDownloadLink();
            }
         }
         return $html;
      }
      ob_start();
      renderTwigTemplate('macros/wrappedInput.twig', [
         'input' => [
            'type'    => 'file',
            'name'    => 'formcreator_field_' . $this->question->getID(),
            'multiple' => 'multiple',
         ],
         'root_doc' => $CFG_GLPI['root_doc'],
      ]);
      return ob_get_clean();
   }

   public function serializeValue(): string {
      return json_encode($this->uploadData, true);
   }

   public function deserializeValue($value) {
      $this->uploadData = json_decode($value ?? '[]', true);
      if ($this->uploadData === null) {
         $this->uploadData = [];
      }
      $this->value = __('No attached document', 'formcreator');;
      if (count($this->uploadData) > 0) {
         $this->value = __('Attached document', 'formcreator');
      }
   }

   public function getValueForDesign(): string {
      return '';
   }

   public function getValueForTargetText($domain, $richText): ?string {
      return $this->value;
   }

   public function moveUploads() {
      if (count($this->uploads) < 1) {
         return;
      }

      $this->uploadData = [];
      foreach ($this->uploads as $upload) {
         $doc = ItsmngUploadHandler::addFileToDb($upload);
         if ($doc->getID() > 0) {
            $this->uploadData[] = $doc->getID();
         }
      }
   }

   public function getDocumentsForTarget(): array {
      return is_array($this->uploadData) ? $this->uploadData : [];
   }

   public function isValid(): bool {
      if (!$this->isRequired()) {
         return true;
      }

      // If the field is required it can't be empty
      if (count($this->uploads) < 1) {
         Session::addMessageAfterRedirect(
            sprintf(__('A required file is missing: %s', 'formcreator'), $this->getLabel()),
            false,
            ERROR
         );
         return false;
      }

      return $this->isValidValue($this->value);
   }

   public function isValidValue($value): bool {
      // If the field is required it can't be empty
      return count($this->uploads) > 0;
   }

   public static function getName(): string {
      return __('File');
   }

   public function prepareQuestionInputForSave($input) {
      return $input;
   }

   public static function canRequire(): bool {
      return true;
   }

   public function hasInput($input): bool {
      $key = 'formcreator_field_' . $this->question->getID();
      return isset($input[$key]);
   }

   public function parseAnswerValues($input, $nonDestructive = false): bool {
      $key = 'formcreator_field_' . $this->question->getID();
      if (!isset($input[$key])) {
         $this->setUploads([]);
         $this->uploadData = [];
         $this->value = '';
         return true;
      }

      if (!is_string($input[$key])) {
         return false;
      }

      $decodedValue = json_decode(stripslashes($input[$key]), true);
      if (!is_array($decodedValue)) {
         return false;
      }

      $hasUploads = false;
      foreach ($decodedValue as $upload) {
         if (is_array($upload) && isset($upload['path'], $upload['name'])) {
            $hasUploads = true;
            break;
         }
      }

      if ($hasUploads) {
         // v2 uploader payload: temporary file descriptors.
         $this->setUploads($decodedValue);
         $this->uploadData = [];
      } else {
         // Existing answer payload: document IDs.
         $this->setUploads([]);
         $this->uploadData = $decodedValue;
      }

      $this->value = count($decodedValue) > 0
         ? __('Attached document', 'formcreator')
         : __('No attached document', 'formcreator');
      return true;
   }

   public function equals($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function notEquals($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function greaterThan($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function lessThan($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function regex($value): bool {
      throw new ComparisonException('Meaningless comparison');
   }

   public function isAnonymousFormCompatible(): bool {
      return true;
   }

   public function getHtmlIcon() {
      return '<i class="fa fa-file" aria-hidden="true"></i>';
   }

   public function isVisibleField(): bool {
      return true;
   }

   public function isEditableField(): bool {
      return true;
   }
}
