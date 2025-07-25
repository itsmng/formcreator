"use strict";

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

var modalWindow;
var rootDoc          = CFG_GLPI['root_doc'];
var currentCategory  = "0";
var tiles = [];
var serviceCatalogEnabled = false;
var slinkyCategories;
var timers = [];
var formcreatorRootDoc = rootDoc + '/' + GLPI_PLUGINS_PATH.formcreator;

// === COMMON ===

function getTimer(object) {
   return function(timeout, action) {
      var timer;
      object.keyup(
         function(event) {
            if (typeof timer != 'undefined') {
               clearTimeout(timer);
            }
            if (event.which == 13) {
               action();
            } else {
               timer = setTimeout(function() {
                  action();
               }, timeout);
            }
         }
      );
   }
}

$(function() {
   modalWindow = $("<div></div>").dialog({
      width: 980,
      autoOpen: false,
      height: "auto",
      modal: true,
      position: {my: 'center'},
      open: function( event, ui ) {
         //remove existing tinymce when reopen modal (without this, tinymce don't load on 2nd opening of dialog)
         modalWindow.find('.mce-container').remove();
      }
   });

   // toggle menu in desktop mode
   $('#formcreator-toggle-nav-desktop').change(function() {
      $.ajax({
         url: formcreatorRootDoc + '/ajax/homepage_wizard.php',
         data: {wizard: 'toggle_menu'},
         type: "POST",
         dataType: "json"
      })
   });

   serviceCatalogEnabled = $("#plugin_formcreator_serviceCatalog").length;

   // Prevent jQuery UI dialog from blocking focusin
   $(document).on('focusin', function(e) {
       if ($(e.target).closest(".mce-window, .moxman-window").length) {
         e.stopImmediatePropagation();
      }
   });

   if (location.pathname.indexOf("helpdesk.public.php") != -1) {

      $('.ui-tabs-panel:visible').ready(function() {
         showHomepageFormList();
      });

      $('#tabspanel + div.ui-tabs').on("tabsload", function(event, ui) {
         showHomepageFormList();
      });

      showHomepageFormList();

   } else if ($('#plugin_formcreator_wizard_categories').length > 0) {
      updateCategoriesView().then((isActive) => {
         updateWizardFormsView(isActive);
         if (!isActive) {
            $("#wizard_seeall").parent().addClass('category_active');
         }
      });

      // Setup events
      $('.plugin_formcreator_sort [value=mostPopularSort]').click(function () {
         showTiles(tiles);
      });

      $('.plugin_formcreator_sort [value=alphabeticSort]').click(function () {
         showTiles(tiles);
      });

      $('#plugin_formcreator_wizard_categories #wizard_seeall').click(function () {
         slinkyCategories.home();
         updateWizardFormsView(0);
         $('#plugin_formcreator_wizard_categories .category_active').removeClass('category_active');
         $(this).addClass('category_active');
      });
   } else if ($('#plugin_formcreator_kb_categories').length > 0) {
      updateKbCategoriesView();
      updateKbitemsView(0);
      $("#kb_seeall").parent().addClass('category_active');

      // Setup events
      $('.plugin_formcreator_sort input[value=mostPopularSort]').click(function () {
         showTiles(tiles);
      });

      $('.plugin_formcreator_sort input[value=alphabeticSort]').click(function () {
         showTiles(tiles);
      });

      $('#plugin_formcreator_kb_categories #kb_seeall').click(function () {
         slinkyCategories.home();
         updateKbitemsView(0);
         $('#plugin_formcreator_kb_categories .category_active').removeClass('category_active');
         $(this).addClass('category_active');
      });
   }

   // load counters
   if ($('.status.status_incoming .status_number').length > 0) {
      plugin_formcreator.getCounters();
   }

   // Initialize search bar
   var searchInput = $('#plugin_formcreator_searchBar input:first');
   if (searchInput.length == 1) {
      // Dynamically update forms and faq items while the user types in the search bar
      var timer = getTimer(searchInput);
      if ($('#plugin_formcreator_kb_categories').length > 0) {
         var callback = function() {
            updateKbitemsView(currentCategory);
         }
      } else {
         var callback = function() {
            updateWizardFormsView(currentCategory);
         }
      }
      timer(300, callback);
      timers.push(timer);

      // Clear the search bar if it gains focus
      $('#plugin_formcreator_searchBar input').focus(function(event) {
         if (searchInput.val().length > 0) {
            searchInput.val('');
            if ($('#plugin_formcreator_kb_categories').length > 0) {
               updateKbitemsView(null);
               $.when(getFaqItems(0))
               .then(
                  function (response) {
                     tiles = response;
                     showTiles(tiles.forms);
                  }
               );
            } else {
               updateWizardFormsView(null);
               $.when(getFormAndFaqItems(0))
               .then(
                  function (response) {
                     tiles = response;
                     showTiles(tiles.forms);
                  }
               );
            }
         }
      });
   }
});

function showHomepageFormList() {
   if ($('#plugin_formcreatorHomepageForms').length) {
      return;
   }

   $.get({
      url: formcreatorRootDoc + '/ajax/homepage_forms.php',
   }).done(function(response){
      if (!$('#plugin_formcreatorHomepageForms').length) {
         $('.central > tbody:first').first().prepend(response);
      }
   });
}

function updateActiveCategory(tree) {
   if (tree.active) {
      $('#plugin_formcreator_wizard_categories .category_active').removeClass('category_active');
      $('a[data-parent-category-id="' + tree.parent + '"][data-category-id="' + tree.id + '"]').parent().addClass('category_active');
      return tree.id;
   }
   for (var i = 0; i < tree.subcategories.length; i++) {
      const ret = updateActiveCategory(tree.subcategories[i]);
      if (ret) {
         return ret;
      }
   }
   return 0;
}

async function updateCategoriesView() {
   return $.post({
      url: formcreatorRootDoc + '/ajax/homepage_wizard.php',
      data: {wizard: 'categories'},
      dataType: "json"
   }).then(function(response) {
      var html = '<div class="slinky-menu">';
      html = html + buildCategoryList(response);
      html = html + '</div>';

      //Display categories
      $('#plugin_formcreator_wizard_categories .slinky-menu').remove();
      $('#plugin_formcreator_wizard_categories').append(html);
      const isActive = updateActiveCategory(response);

      // Setup slinky
      slinkyCategories = $('#plugin_formcreator_wizard_categories div:nth(2)').slinky({
         label: true
      });

      // Show label of parent in the 'back' item
      document.querySelectorAll('#plugin_formcreator_wizard_categories .slinky-menu a.back').forEach(item => {
         var parentLabel = item.closest('ul').closest('li').querySelector('a').innerText;
         item.innerText = parentLabel;
     });

      $('#plugin_formcreator_wizard_categories a.back').click(
         function(event) {
            var parentItem = $(event.target).parentsUntil('#plugin_formcreator_wizard_categories .slinky-menu > ul', 'li')[1];
            var parentAnchor = $(parentItem).children('a')[0];
            updateWizardFormsView(parentAnchor.getAttribute('data-parent-category-id'));
         }
      );

      $('#plugin_formcreator_wizard_categories a[data-category-id]').click(
         function (event) {
            $('#plugin_formcreator_wizard_categories .category_active').removeClass('category_active');
            $(this).addClass('category_active');
         }
      );

      return isActive;
   });
}

function updateKbCategoriesView() {
   $.get({
      url: formcreatorRootDoc + '/ajax/kb_category.php',
      dataType: "json"
   }).done(function(response) {
      var html = '<div class="slinky-menu">';
      html = html + buildKbCategoryList(response);
      html = html + '</div>';

      //Display categories
      $('#plugin_formcreator_kb_categories .slinky-menu').remove();
      $('#plugin_formcreator_kb_categories').append(html);

      // Setup slinky
      slinkyCategories = $('.slinky-menu').slinky({
         label: true
      });
      $('#plugin_formcreator_kb_categories a.back').click(
         function(event) {
            parentItem = $(event.target).parentsUntil('#plugin_formcreator_kb_categories > div', 'li')[1];
            parentAnchor = $(parentItem).children('a')[0];
            updateKbitemsView(parentAnchor.getAttribute('data-parent-category-id'));
         }
      );

      $('#plugin_formcreator_kb_categories a[data-category-id]').click(
         function (event) {
            $('#plugin_formcreator_kb_categories .category_active').removeClass('category_active');
            $(this).addClass('category_active');
         }
      );
   });
}

function getFaqItems(categoryId) {
   var currentCategory = categoryId;
   var keywords = $('#plugin_formcreator_searchBar input:first').val();
   var deferred = jQuery.Deferred();
   $.post({
      url: formcreatorRootDoc + '/ajax/knowbaseitem.php',
      data: {
         categoriesId: categoryId,
         keywords: keywords,
         helpdeskHome: 0
      },
      dataType: "json"
   }).done(function (response) {
      deferred.resolve(response);
   }).fail(function (response) {
      deferred.reject();
   });
   return deferred.promise();
}

/**
 * get form and faq items from DB
 * Returns a promise
 */
function getFormAndFaqItems(categoryId) {
   var keywords = $('#plugin_formcreator_searchBar input:first').val();
   var deferred = jQuery.Deferred();
   $.post({
      url: formcreatorRootDoc + '/ajax/homepage_wizard.php',
      data: {
         wizard: 'forms',
         categoriesId: categoryId,
         keywords: keywords,
         helpdeskHome: 0
      },
      dataType: "json"
   }).done(function (response) {
      deferred.resolve(response);
   }).fail(function (response) {
      deferred.reject();
   });
   return deferred.promise();
}

function sortFormAndFaqItems(items, byName) {
   if (byName == true) {
      // sort by name
      items.sort(function (a, b) {
         if (a.name < b.name) {
            return -1;
         }
         if (a.name > b.name) {
            return 1
         }
         return 0;
      });
   } else {
      // sort by view or usage count
      items.sort(function (a, b) {
         if (a.usage_count > b.usage_count) {
            return -1;
         }
         if (a.usage_count < b.usage_count) {
            return 1
         }
         return 0;
      });
   }
   return items;
}

function showTiles(tiles, defaultForms) {
   var sortByName = $('#plugin_formcreator_alphabetic').prop('checked')
   var tiles = sortFormAndFaqItems(tiles, sortByName);
   var html = '';
   if (defaultForms) {
      html += '<p>' + i18n.textdomain('formcreator').__('No form found. Please choose a form below instead', 'formcreator') + '</p>'
   }
   html += buildTiles(tiles);

   //Display tiles
   $('#plugin_formcreator_wizard_forms').empty();
   $('#plugin_formcreator_wizard_forms').prepend(html);
   $('#plugin_formcreator_formlist').masonry({
      horizontalOrder: true
   });
   $('#plugin_formcreator_faqlist').masonry({
      horizontalOrder: true
   });
}

function updateWizardFormsView(categoryId) {
   $.when(getFormAndFaqItems(categoryId)).done(
      function (response) {
         tiles = response.forms;
         showTiles(tiles, response.default);
      }
   ).fail(
      function () {
         var html = '<p>' + i18n.textdomain('formcreator').__('An error occured while querying forms', 'formcreator') + '</p>'
         $('#plugin_formcreator_wizard_forms').empty();
         $('#plugin_formcreator_wizard_forms').prepend(html);
         $('#plugin_formcreator_formlist').masonry({
            horizontalOrder: true
         });
         $('#plugin_formcreator_faqlist').masonry({
            horizontalOrder: true
         });
      }
   );
}

function updateKbitemsView(categoryId) {
   $.when(getFaqItems(categoryId)).done(
      function (response) {
         tiles = response.forms;
         showTiles(tiles, false);
      }
   ).fail(
      function () {
         html = '<p>' + i18n.textdomain('formcreator').__('An error occured while querying forms', 'formcreator') + '</p>'
         $('#plugin_formcreator_wizard_forms').empty();
         $('#plugin_formcreator_wizard_forms').prepend(html);
         $('#plugin_formcreator_formlist').masonry({
            horizontalOrder: true
         });
         $('#plugin_formcreator_faqlist').masonry({
            horizontalOrder: true
         });
      }
   );
}

function buildKbCategoryList(tree) {
   var html = '';
   if (tree.id != 0) {
      html += '<a href="#" data-parent-category-id="' + tree.parent +'"'
         + ' data-category-id="' + tree.id + '"'
         + ' onclick="updateKbitemsView(' + tree.id + ')">'
         + tree.name
         + '</a>';
   }
   if (Object.keys(tree.subcategories).length == 0) {
      return html;
   }
   html = html + '<ul>';
   $.each(tree.subcategories, function (key, val) {
      html = html + '<li>' + buildKbCategoryList(val) + '</li>';
   });
   html = html + '</ul>';
   return html;
}


function buildCategoryList(tree) {
   var html = '';
   if (tree.id != 0) {
      html = '<a href="#" data-parent-category-id="' + tree.parent +'"'
         + ' data-category-id="' + tree.id + '"'
         + ' onclick="updateWizardFormsView(' + tree.id + ')">'
         + tree.name
         + '</a>';
   }
   if (Object.keys(tree.subcategories).length == 0) {
      return html;
   }
   html = html + '<ul>';
   $.each(tree.subcategories, function (key, val) {
      html = html + '<li>' + buildCategoryList(val) + '</li>';
   });
   html = html + '</ul>';
   return html;
}

function buildTiles(list) {
   $(document).on('click', '.plugin_formcreator_formTile', function(){
      document.location = $(this).find('a').attr('href');
   });

   var html = '';
   if (list.length == 0) {
      html = '<p id="plugin_formcreator_formlist">'
      + i18n.textdomain('formcreator').__('No item yet in this category', 'formcreator')
      + '</p>'
      +'<p id="plugin_formcreator_faqlist"></p>';
   } else {
      var forms = [];
      var faqs = [];
      $.each(list, function (key, item) {
         // Build a HTML tile
         var url = formcreatorRootDoc + '/front/formdisplay.php?id=' + item.id;
         if (item.type != 'form') {
            if (serviceCatalogEnabled) {
               url = formcreatorRootDoc + '/front/knowbaseitem.form.php?id=' + item.id;
            } else {
               url = rootDoc + '/front/knowbaseitem.form.php?id=' + item.id;
            }
         }

         var description = '';
         if (item.description) {
            description = '<div class="plugin_formcreator_formTile_description">'
                          +item.description
                          +'</div>';
         }

         var default_class = '';
         if (JSON.parse(item.is_default)) {
            default_class = 'default_form';
         }

         if (item.icon == '') {
            if (item.type == 'form') {
               item.icon = 'fa fa-question-circle';
            } else {
               item.icon = 'fa fa-clipboard-list';
            }
         }

         if (item.icon_color == '') {
            item.icon_color = '#999999';
         }

         if (item.background_color == '') {
            item.background_color = '#e7e7e7';
         }

         if (item.type == 'form') {
            forms.push(`
               <div data-itemtype="PluginFormcreatorForm"
                     data-id="${item.id}"
                     style="background-color: ${item.background_color};"
                     class="plugin_formcreator_formTile ${item.type} ${default_class}"
                     title="${item.description}">
                  <div>
                  ${
                     item.icon_type
                     ? `<img src="${item.icon}"
                              style="color: ${item.icon_color};">
                        </img>`
                     : `<i class="${item.icon}"
                              style="color: ${item.icon_color};">
                        </i>`
                  }
                  </div>
                  <div style=";width:90%;height:2.5rem">           
                     <a href="${url}"
                        class="plugin_formcreator_formTile_title">
                        ${item.name}
                     </a>
                     ${description}
                  </div>
               </div>
            `);
         } else {
            faqs.push(`
            <div  style="background-color: ${item.background_color};"
                  class="plugin_formcreator_formTile ${item.type} ${default_class}"
                  title="${item.description}">
               <div>
               ${
                  item.icon_type
                  ? `<img src="${item.icon}"
                           style="width:2rem; height: 2rem; color: ${item.icon_color};">
                     </img>`
                  : `<i class="${item.icon}"
                           style="color: ${item.icon_color};">
                     </i>`
               }
               </div>
               <div style="display:flex;align-items:center;width:90%;height:2.5rem">
                  <a href="${url}"
                     class="plugin_formcreator_formTile_title">
                     ${item.name}
                  </a>
                  ${description}
               </div>
            </div>
         `);
         }
      });

      // concatenate all HTML parts
      html = '<div id="plugin_formcreator_formlist">'
      + forms.join("")
      + '</div><div id="plugin_formcreator_faqlist">'
      + faqs.join("")
      + '</div>'
   }

   return html;
}

var plugin_formcreator = new function() {
   this.spinner = '<div"><img src="../../../pics/spinner.48.gif" style="margin-left: auto; margin-right: auto; display: block;" width="48px"></div>'

   this.modalSetings = {
      autoOpen: false,
      height: 'auto',
      width: '600px',
      minWidth: '600px',
      modal: true,
      position: {my: 'center'},
      close: function() {
         $(this).dialog('close');
         $(this).remove();
      }
   }

   // Properties of the item when the user begins to change it
   this.initialPosition = {};
   this.changingItemId = 0;
   this.questionsColumns = 4; // @see PluginFormcreatorSection::COLUMNS
   this.dirty = false;

   this.setupGridStack = function (group) {
      var that = this;
      group
      .on('resizestart', this.startChangeItem)
      .on('dragstart', this.startChangeItem)
      .on('change', function(event, item) {
         that.changeItems(event, item)
      })
      .on('dragstop', function(event, item) {
         setTimeout(function() {
            item.helper.find('a').off('click.prevent');
         },
         300);
         // Remove empty rows
         plugin_formcreator.moveUpItems(group);
      });
      group.on('dropped', function (event, previousWidget, newWidget) {
         var changes = {};
         var section = $(newWidget.el).closest('[data-itemtype="PluginFormcreatorSection"]');
         var itemId = $(newWidget.el).attr('data-id');
         changes[itemId] = {
            plugin_formcreator_sections_id: section.attr('data-id'),
            width: newWidget.width,
            height: newWidget.height,
            x: newWidget.x,
            y: newWidget.y
         };
         $.ajax({
            'url': formcreatorRootDoc + '/ajax/question_move.php',
            type: 'POST',
            data: {
               move: changes,
            }
         }).fail(function() {
            plugin_formcreator.cancelChangeItems(event, items);
            plugin_formcreator.dirty = false;
         }).done(function(response) {
            plugin_formcreator.dirty = false;
         });
      });
   };

   /**
   * Event handler : when an item is about to move or resize
   */
  this.startChangeItem = function (event, item) {
      item.helper.find('a').on('click.prevent', function(event) {
         return false;
      });
      var items = $(event.currentTarget).find('> .grid-stack-item');
      this.initialPosition = {};
      var that = this;
      $.each(items, function(index, item) {
         var id = $(item).attr('data-id');
         that.initialPosition[id] = {
            x:      Number($(item).attr('data-gs-x')),
            y:      Number($(item).attr('data-gs-y')),
            width:  Number($(item).attr('data-gs-width')),
            height: Number($(item).attr('data-gs-height')),
         }
      });
      this.changingItemId = Number($(event.target).attr('data-id'));
   };

   /**
    * Event handler : change an item (resize or move)
    */
   this.changeItems = function (event, items) {
      if (this.dirty === true) {
         return;
      }
      var that = this;
      var changes = {};
      $.each(items, function(index, item) {
         var id     = $(item.el).attr('data-id');
         if (typeof(id) !== 'undefined') {
            changes[id] = {
               width:  item.width,
               height: item.height,
               x:      item.x,
               y:      item.y
            };
         }
      });
      if (changes.length < 1) {
         return;
      }
      $.ajax({
         'url': formcreatorRootDoc + '/ajax/question_move.php',
         type: 'POST',
         data: {
            move: changes,
         }
      }).fail(function() {
         plugin_formcreator.cancelChangeItems(event, items);
         plugin_formcreator.dirty = false;
      }).done(function(response) {
         plugin_formcreator.dirty = false;
      });
   };

   this.cancelChangeItems = function (event, items) {
      var that = this;
      $.each(items, function(index, item) {
         var id = $(item.el).attr('data-id');
         if (typeof(that.initialPosition[id]) === 'undefined') {
            // this is the placeholder
            return;
         }
         if (id != that.changingItemId) {
            return;
         }
         $(event.target).data('gridstack').update(
            item.el,
            that.initialPosition[id]['x'],
            that.initialPosition[id]['y'],
            that.initialPosition[id]['width'],
            that.initialPosition[id]['height'],
         );
      });
   };

   // === QUESTIONS ===

   this.deleteQuestion = function (target) {
      var item = $(target).closest('.formcreator-question');
      var id = item.attr('data-id');
      if (typeof(id) === 'undefined') {
         return;
      }
      if (confirm(i18n.textdomain('formcreator').__('Are you sure you want to delete this question?', 'formcreator'))) {
         jQuery.ajax({
         url: formcreatorRootDoc + '/ajax/question_delete.php',
         type: "POST",
         data: {
               id: id,
            }
         }).fail(function(data) {
            alert(data.responseText);
         }).done(function() {
            item.remove();
         });
      }
   };

   this.toggleRequired = function (target) {
      var item = $(target).closest('.formcreator-question');
      var id = item.attr('data-id');
      if (typeof(id) === 'undefined') {
         return;
      }
      var required = $(target).hasClass('fa-check-circle');
      jQuery.ajax({
         url: formcreatorRootDoc + '/ajax/question_toggle_required.php',
         type: "POST",
         data: {
            id: id,
            required: required ? '0' : '1'
         }
      }).fail(function(data) {
         alert(data.responseText);
      }).done(function() {
         $(target)
            .removeClass('fa-circle fa-check-circle')
            .addClass(required ? 'fa-circle' : 'fa-check-circle');
      });
   };

   this.plugin_formcreator_scrollToModal = function (modalWindow) {
   $('html, body').animate({
        scrollTop: $(modalWindow).closest('.ui-dialog').offset().top
    }, 300);
}

   this.moveQuestion = function (target, action) {
      var item = $(target).closest('.formcreator-question');
      var id = item.attr('data-id');
      if (typeof(id) === 'undefined') {
         return;
      }
      switch (action) {
         case 'up':
            var otherItem = item.prev('.formcreator-question');
            break;
         case 'down':
            var otherItem = item.next('.formcreator-question');
            break;
      }
      if (otherItem.length < 1) {
         return;
      }
      const otherId = otherItem.attr('data-id');
      const data = {move: {}};
      data.move[otherId] = {
         y: item.attr('data-order'),
      };
      data.move[id] = {
         y: otherItem.attr('data-order'),
      };
      $.ajax({
         url: formcreatorRootDoc + '/ajax/question_move.php',
         type: "POST",
         data,
         success: function() {
            if (action == 'up') {
               otherItem.before(item);
            }
            if (action == 'down') {
               otherItem.after(item);
            }
            $.each([item, otherItem], function(index, item) {
               if (item.prev('.formcreator-question').length < 1) {
                  item.find('.moveUp').hide();
               } else {
                  item.find('.moveUp').show();
               }
               if (item.next('.formcreator-question').length < 1) {
                  item.find('.moveDown').hide();
               } else {
                  item.find('.moveDown').show();
               }
            });
         }
      });
   };

   this.addQuestion = function () {
      var form = $('form[data-itemtype="PluginFormcreatorQuestion"]');
      var that = this;
      $.ajax({
         url: formcreatorRootDoc + '/ajax/question_add.php',
         type: "POST",
         data: form.serializeArray(),
         dataType: 'json'
      }).fail(function(data) {
         displayAjaxMessageAfterRedirect();
      }).done(function(data) {
         var sectionId = form.find('select[name="plugin_formcreator_sections_id"]').val();
         var container = $('[data-itemtype="PluginFormcreatorSection"][data-id="' + sectionId + '"] .plugin_formcreator_sectionContent');
         container.append(data.html);
         modalWindow.dialog('close');
         that.resetTabs();
      });
   }

   this.editQuestion = function () {
      var form = $('form[data-itemtype="PluginFormcreatorQuestion"]');
      var questionId = form.find('[name="id"]').val();
      var that = this;
   
      const editorContent = document.querySelector('.ck-editor__editable[contenteditable="true"]');
      const textarea = document.getElementById('description');
      
      if (editorContent && textarea) {
         textarea.value = editorContent.innerHTML;
      }
   
      $.ajax({
         url: formcreatorRootDoc + '/ajax/question_update.php',
         type: "POST",
         data: form.serializeArray(),
         dataType: 'html'
      }).fail(function(data) {
         displayAjaxMessageAfterRedirect();
      }).done(function(data) {
         var question = $('.plugin_formcreator_form_design[data-itemtype="PluginFormcreatorForm"] [data-itemtype="PluginFormcreatorQuestion"][data-id="' + questionId + '"]');
         question.find('[data-field="name"]').text(data)
         modalWindow.dialog('close');
         that.resetTabs();
      });
   }

   this.duplicateQuestion = function (target) {
      var item = $(target).closest('.formcreator-question');
      var id = item.attr('data-id');
      if (typeof(id) === 'undefined') {
         return;
      }

      $.ajax({
         url: formcreatorRootDoc + '/ajax/question_duplicate.php',
         type: "POST",
         dataType: 'json',
         data: {
            id: id
         }
      }).fail(function(data) {
         alert(data.responseText);
      }).done(function(question) {
         var sectionContent = item.closest('[data-itemtype="PluginFormcreatorSection"] .plugin_formcreator_sectionContent');
         sectionContent.append(question.html);
      });
   };

   this.showFields = function (form) {
      console.log(form);
      $.ajax({
         url: formcreatorRootDoc + '/ajax/showfields.php',
         type: "POST",
         data: form.serializeArray()
      }).done(function(response){
         try {
            var itemToShow = JSON.parse(response);
            var questionToShow = itemToShow['PluginFormcreatorQuestion'];
            var sectionToShow = itemToShow['PluginFormcreatorSection'];
            var submitButtonToShow = itemToShow['PluginFormcreatorForm'];
         } catch (e) {
            // Do nothing for now
         }
         for (var sectionKey in sectionToShow) {
            var sectionId = parseInt(sectionKey);
            if (!isNaN(sectionId)) {
               if (sectionToShow[sectionId]) {
                  $('#plugin_formcreator_form.plugin_formcreator_form [data-itemtype = "PluginFormcreatorSection"][data-id="' + sectionId+ '"]').removeAttr('hidden', '');
               } else {
                  $('#plugin_formcreator_form.plugin_formcreator_form [data-itemtype = "PluginFormcreatorSection"][data-id="' + sectionId+ '"]').attr('hidden', '');
               }
            }
         }
         var i = 0;
         for (var questionKey in questionToShow) {
            var questionId = questionKey;
            questionId = parseInt(questionKey.replace('formcreator_field_', ''));
            if (!isNaN(questionId)) {
               if (tinymce.majorVersion < 5) {
                  // Workaround issue with tinymce input which may be 0px height
                  $('#form-group-field-' + questionKey + ' iframe')
                     .attr('data-mce-style', 'width: 100%; height: 148px; display: block;')
                     .attr('style', 'width: 100%; height: 148px; display: block;');
               }
               if (questionToShow[questionKey]) {
                  $('#plugin_formcreator_form.plugin_formcreator_form [data-itemtype = "PluginFormcreatorQuestion"][data-id="' + questionKey + '"]').removeAttr('hidden', '');
               } else {
                  $('#plugin_formcreator_form.plugin_formcreator_form [data-itemtype = "PluginFormcreatorQuestion"][data-id="' + questionKey + '"]').attr('hidden', '');
               }
            }
         }

         $('[name="submit_formcreator"]').toggle(submitButtonToShow == true);
      });
   };

   // === SECTIONS ===

   this.deleteSection = function (item) {
      if(confirm(i18n.textdomain('formcreator').__('Are you sure you want to delete this section?', 'formcreator'))) {
         var section = $(item).closest('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]');
         var sectionId = section.attr('data-id');
         var that = this;
         $.ajax({
         url: formcreatorRootDoc + '/ajax/section_delete.php',
         type: "POST",
         data: {
               id: sectionId
            }
         }).done(function() {
            section.remove();
            that.resetTabs();
         }).fail(function(data) {
            alert(data.responseText);
         });
      }
   };

   this.moveSection = function (item, action) {
      var section = $(item).closest('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]');
      var sectionId = section.attr('data-id');
      $.ajax({
         url: formcreatorRootDoc + '/ajax/section_move.php',
         type: "POST",
         data: {
            id: sectionId,
            way: action
         },
         success: function() {
            if (action == 'up') {
               var otherSection = section.prev('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]').detach();
               section.after(otherSection);
            }
            if (action == 'down') {
               var otherSection = section.next('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]').detach();
               section.before(otherSection);
            }
            $.each([section, otherSection], function(index, section) {
               if (section.prev('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]').length < 1) {
                  section.children('.moveUpSection').hide();
               } else {
                  section.children('.moveUpSection').show();
               }
               if (section.next('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]').length < 1) {
                  section.children('.moveDownSection').hide();
               } else {
                  section.children('.moveDownSection').show();
               }
            });
         }
      });
   };

   this.showQuestionForm = function (sectionId, questionId = 0) {
      var that = this;
      $.post({
         url: formcreatorRootDoc + '/ajax/question.php',
         data: {
            question_id: questionId,
            plugin_formcreator_sections_id: sectionId
         }
      }).done(function (data) {
         modalWindow.html(data).dialog('open');
         that.plugin_formcreator_scrollToModal($(modalWindow));
      });
   };

   this.duplicateSection = function (item) {
      var section = $(item).closest('#plugin_formcreator_form.plugin_formcreator_form_design [data-itemtype="PluginFormcreatorSection"]');
      var sectionId = section.attr('data-id');
      var that = this;
      $.ajax({
         url: formcreatorRootDoc + '/ajax/section_duplicate.php',
      type: "POST",
      data: {
         id: sectionId
      },
      dataType: 'html'
      }).done(function(data) {
         var lastSection = $('.plugin_formcreator_form_design[data-itemtype="PluginFormcreatorForm"] [data-itemtype="PluginFormcreatorSection"]').last();
         lastSection.after(data);
         sectionId = $('.plugin_formcreator_form_design[data-itemtype="PluginFormcreatorForm"] [data-itemtype="PluginFormcreatorSection"]').last().attr('data-id');
         that.resetTabs();
      }).fail(function(data) {
         alert(data.responseText);
      });
   };

   this.showSectionForm = function (formId, sectionId = 0) {
      var that = this;
      $.post({
         url: formcreatorRootDoc + '/ajax/section.php',
         data: {
            section_id: sectionId,
            plugin_formcreator_forms_id: formId
         }
      }).done(function(data) {
         modalWindow.html(data).dialog('open');
         that.plugin_formcreator_scrollToModal($(modalWindow));
      });
   }

   this.addSection = function () {
      var form = $('form[data-itemtype="PluginFormcreatorSection"]');
      var that = this;
      $.ajax({
         url: formcreatorRootDoc + '/ajax/section_add.php',
         type: "POST",
         data: form.serializeArray(),
         dataType: 'html'
      }).fail(function(data) {
         alert(data.responseText);
      }).done(function(data) {
         var addSectionRow = $('[data-itemtype="PluginFormcreatorForm"] li').last();
         addSectionRow.before(data);
         modalWindow.dialog('close');
         that.resetTabs();
      });
   }

   this.editSection = function () {
      var form = $('form[data-itemtype="PluginFormcreatorSection"]');
      var sectionId = form.find('[name="id"]').val();
      var that = this;
      $.ajax({
         url: formcreatorRootDoc + '/ajax/section_update.php',
         type: "POST",
         data: form.serializeArray(),
         dataType: 'html'
      }).fail(function(data) {
         alert(data.responseText);
      }).done(function(data) {
         var section = $('.plugin_formcreator_form_design[data-itemtype="PluginFormcreatorForm"] [data-itemtype="PluginFormcreatorSection"][data-id="' + sectionId + '"]');
         section.find('> [data-field="name"]').text(data);
         modalWindow.dialog('close');
         that.resetTabs();
      });
   }

   this.createLanguage = function (formId, id = -1) {
      var placeholder = $('#plugin_formcreator_formLanguage');
      this.showSpinner(placeholder);
      $.post({
         url: rootDoc + '/ajax/viewsubitem.php',
         data: {
            type: "PluginFormcreatorForm_Language",
            parenttype: "PluginFormcreatorForm",
            plugin_formcreator_forms_id: formId,
            id: id
         }
      }).done(function (data) {
         $(placeholder).html(data);
      });
   }

   /**
    * Put a spinner inside the given selector
    */
   this.showSpinner = function (selector) {
      return $(selector).html('<img class="plugin_formcreator_spinner" src="../../../pics/spinner.48.gif">');
   }

   /**
    * destroy hidden tabs. Useful when their content is obsoleted
    */
   this.resetTabs = function () {
      $('.glpi_tabs [role="tabpanel"][aria-hidden="true"] ').empty();
   }

   this.showTranslationEditor = function (object) {
      var formlanguageId = $(object).closest('[data-itemtype="PluginFormcreatorForm_Language"][data-id]').attr('data-id');
      var plugin_formcreator_translations_id = $(object).find('input[name="id"]').val();
      $('#plugin_formcreator_editTranslation').load(formcreatorRootDoc + '/ajax/edit_translation.php', {
         plugin_formcreator_form_languages_id: formlanguageId,
         plugin_formcreator_translations_id: ''
      });
   }

   this.newTranslation = function (formLanguageId) {
      var modal = $(this.spinner)
         .dialog(this.modalSetings)
         .on('dialogclose', function (e, ui) {
            reloadTab();
         });
      $.post({
         url: '../ajax/form_language.php',
         data: {
            action: 'newTranslation',
            id: formLanguageId,
         }
      }).done(function (data) {
         modal.html(data).dialog('open');
      }).fail(function () {
         displayAjaxMessageAfterRedirect();
         modal.html('');
      });
   }

   this.saveNewTranslation = function (element) {
      var that = this;
      var form = document.querySelector('form[name="plugin_formcreator_translation"]');
      tinyMCE.triggerSave();
      $.post({
         url: '../ajax/translation.php',
         data: $(element).closest('form').serializeArray()
      }).fail(function () {
         displayAjaxMessageAfterRedirect();
      }).done(function () {
         that.showTranslationEditor(form);
      });
   }

   this.showUpdateTranslationForm = function (element) {
      var formLanguageId = $(element).closest('[data-itemtype="PluginFormcreatorForm_Language"][data-id]').attr('data-id');
      var translationId = $(element.closest('[data-itemtype="PluginFormcreatorTranslation"]')).attr('data-id');
      var modal = $(this.spinner);
      modal.dialog(this.modalSetings)
         .on('dialogclose', function (e, ui) {
            reloadTab();
         });
      $.post({
         url: '../ajax/form_language.php',
         data: {
            action: 'translation',
            id: formLanguageId,
            plugin_formcreator_translations_id: translationId
         }
      }).done(function(data) {
         modal.html(data).dialog('open');
         // we edit a translation, then close the dialog when saving the values
         modal.find('form').on('submit', function () {
            modal.dialog('close');
         })
      });
   }

   // make a new selector equivalent to :contains(...) but case insensitive
   jQuery.expr[":"].icontains = jQuery.expr.createPseudo(function (arg) {
      return function (elem) {
         return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
      };
   });

   // filter override results
   var debounce;
   $(document).on('change paste keyup', '.plugin_formcreator_filter_translations > input', function() {
      var text = $(this).val();

      // delay event by a little time to avoid trigger on each key press
      window.clearTimeout(debounce);
      debounce = window.setTimeout(function() {
         // reshow all tr
         $(".translation_list tbody tr").show();

         // find tr with searched text inside
         var tr_with_text = $(".translation_list tbody tr:has(td:icontains("+text+"))");

         // hide other tr
         var tr_inverse = $(".translation_list tbody tr").not(tr_with_text);
         tr_inverse.hide();
      }, 200);
   });

   this.toggleForm = function (id) {
      $.ajax({
         url: formcreatorRootDoc + '/ajax/form_toggle.php',
         type: 'POST',
         data: {
            toggle: 'toggle',
            id: id
         }
      }).success(function () {
         location.reload();
      });
   }

   this.getCounters = function () {
      this.getIncomingCounter().done(function (data) {
         $('.status.status_incoming .status_number').empty().append(data[1]);
      }).fail(function () {
         $('.status.status_incoming .status_number').empty().append('N/A');
      });

      this.getWaitingCounter().done(function (data) {
         $('.status.status_waiting .status_number').empty().append(data[4]);
      }).fail(function () {
         $('.status.status_waiting .status_number').empty().append('N/A');
      });

      this.getToValidateCounter().done(function (data) {
         $('.status.status_validate .status_number').empty().append(data['to_validate']);
      }).fail(function () {
         $('.status.status_validate .status_number').empty().append('N/A');
      });

      this.getSolvedCounter().done(function (data) {
         $('.status.status_solved .status_number').empty().append(data[5]);
      }).fail(function () {
         $('.status.status_solved .status_number').empty().append('N/A');
      });
   }

   this.getIncomingCounter = function () {
      return $.get({
         url: formcreatorRootDoc + '/ajax/counter.php',
         dataType: 'json',
         data: {
            counter: 'incoming'
         }
      });
   }

   this.getWaitingCounter = function () {
      return $.get({
         url: formcreatorRootDoc + '/ajax/counter.php',
         dataType: 'json',
         data: {
            counter: 'waiting'
         }
      });
   }

   this.getToValidateCounter = function () {
      return $.get({
         url: formcreatorRootDoc + '/ajax/counter.php',
         dataType: 'json',
         data: {
            counter: 'to_validate'
         }
      });
   }

   this.getSolvedCounter = function () {
      return $.get({
         url: formcreatorRootDoc + '/ajax/counter.php',
         dataType: 'json',
         data: {
            counter: 'solved'
         }
      });
   }
}

// === TARGETS ===

function plugin_formcreator_addTarget(items_id) {
   $.post({
      url: formcreatorRootDoc + '/ajax/target.php',
      data: {
         plugin_formcreator_forms_id: items_id
      }
   }).done(function (data) {
      modalWindow.html(data).dialog('open');
   });
}

function plugin_formcreator_editTarget(itemtype, items_id) {
   $.post({
      url: formcreatorRootDoc + '/ajax/target_edit.php',
      data: {
         itemtype: itemtype,
         id: items_id
      }
   }).done(function (data) {
      modalWindow.html(data).dialog('open');
   });
}

function plugin_formcreator_deleteTarget(itemtype, target_id, token) {
   if(confirm(i18n.textdomain('formcreator').__('Are you sure you want to delete this destination:', 'formcreator'))) {
      jQuery.ajax({
        url: formcreatorRootDoc + '/front/form.form.php',
        type: "POST",
        data: {
            delete_target: 1,
            itemtype: itemtype,
            items_id: target_id,
            _glpi_csrf_token: token
         }
      }).done(function () {
         location.reload();
      });

   }
}

// DESTINATION
function plugin_formcreator_formcreatorChangeDueDate(value) {
   $('#due_date_questions').hide();
   $('#due_date_time').hide();
   switch (value) {
      case '2' :
         $('#due_date_questions').show();
         break;
      case '3' :
         $('#due_date_time').show();
         break;
      case '4' :
         $('#due_date_questions').show();
         $('#due_date_time').show();
         break;
   }
}

function plugin_formcreator_formcreatorChangeSla(value) {
   switch (value) {
      default:
      case '1' :
         $('#sla_specific_title').hide();
         $('#sla_specific_value').hide();
         $('#sla_question_title').hide();
         $('#sla_questions').hide();
         break;
      case '2' :
         $('#sla_question_title').hide();
         $('#sla_questions').hide();
         $('#sla_specific_title').show();
         $('#sla_specific_value').show();
         break;
      case '3' :
         $('#sla_specific_title').hide();
         $('#sla_specific_value').hide();
         $('#sla_question_title').show();
         $('#sla_questions').show();
         break;
   }
}

function plugin_formcreator_formcreatorChangeOla(value) {
   switch (value) {
      default:
      case '1' :
         $('#ola_specific_title').hide();
         $('#ola_specific_value').hide();
         $('#ola_question_title').hide();
         $('#ola_questions').hide();
         break;
      case '2' :
         $('#ola_question_title').hide();
         $('#ola_questions').hide();
         $('#ola_specific_title').show();
         $('#ola_specific_value').show();
         break;
      case '3' :
         $('#ola_specific_title').hide();
         $('#ola_specific_value').hide();
         $('#ola_question_title').show();
         $('#ola_questions').show();
         break;
   }
}

function plugin_formcreator_displayRequesterForm() {
   $('#form_add_requester').show();
   $('#btn_add_requester').hide();
   $('#btn_cancel_requester').show();
}

function plugin_formcreator_hideRequesterForm() {
   $('#form_add_requester').hide();
   $('#btn_add_requester').show();
   $('#btn_cancel_requester').hide();
}

function plugin_formcreator_displayWatcherForm() {
   $('#form_add_watcher').show();
   $('#btn_add_watcher').hide();
   $('#btn_cancel_watcher').show();
}

function plugin_formcreator_hideWatcherForm() {
   $('#form_add_watcher').hide();
   $('#btn_add_watcher').show();
   $('#btn_cancel_watcher').hide();
}

function plugin_formcreator_displayAssignedForm() {
   $('#form_add_assigned').show();
   $('#btn_add_assigned').hide();
   $('#btn_cancel_assigned').show();
}

function plugin_formcreator_hideAssignedForm() {
   $('#form_add_assigned').hide();
   $('#btn_add_assigned').show();
   $('#btn_cancel_assigned').hide();
}

function plugin_formcreator_ChangeActorRequester(value) {
   $('#block_requester_user').hide();
   $('#block_requester_question_user').hide();
   $('#block_requester_group').hide();
   $('#block_requester_question_group').hide();
   $('#block_requester_group_from_object').hide();
   $('#block_requester_tech_group_from_object').hide();
   $('#block_requester_question_actors').hide();
   $('#block_requester_supplier').hide();
   $('#block_requester_question_supplier').hide();

   switch (value) {
      case '3' : $('#block_requester_user').show();                   break;
      case '4' : $('#block_requester_question_user').show();          break;
      case '5' : $('#block_requester_group').show();                  break;
      case '6' : $('#block_requester_question_group').show();         break;
      case '10': $('#block_requester_group_from_object').show();      break;
      case '11': $('#block_requester_tech_group_from_object').show(); break;
      case '9' : $('#block_requester_question_actors').show();        break;
      case '7' : $('#block_requester_supplier').show();               break;
      case '8' : $('#block_requester_question_supplier').show();      break;
   }
}

function plugin_formcreator_ChangeActorWatcher(value) {
   $('#block_watcher_user').hide();
   $('#block_watcher_question_user').hide();
   $('#block_watcher_group').hide();
   $('#block_watcher_question_group').hide();
   $('#block_watcher_group_from_object').hide();
   $('#block_watcher_tech_group_from_object').hide();
   $('#block_watcher_question_actors').hide();
   $('#block_watcher_supplier').hide();
   $('#block_watcher_question_supplier').hide();

   switch (value) {
      case '3' : $('#block_watcher_user').show();                   break;
      case '4' : $('#block_watcher_question_user').show();          break;
      case '5' : $('#block_watcher_group').show();                  break;
      case '6' : $('#block_watcher_question_group').show();         break;
      case '9' : $('#block_watcher_question_actors').show();        break;
      case '10': $('#block_watcher_group_from_object').show();      break;
      case '11': $('#block_watcher_tech_group_from_object').show(); break;
      case '9' : $('#block_watcher_question_actors').show();        break;
      case '7' : $('#block_watcher_supplier').show();               break;
      case '8' : $('#block_watcher_question_supplier').show();      break;
   }
}

function plugin_formcreator_ChangeActorAssigned(value) {
   $('#block_assigned_user').hide();
   $('#block_assigned_question_user').hide();
   $('#block_assigned_group').hide();
   $('#block_assigned_question_group').hide();
   $('#block_assigned_group_from_object').hide();
   $('#block_assigned_tech_group_from_object').hide();
   $('#block_assigned_question_actors').hide();
   $('#block_assigned_supplier').hide();
   $('#block_assigned_question_supplier').hide();

   // The numbers match PluginFormcreatorTarget_Actor::ACTOR_TYPE_* constants
   switch (value) {
      case '3' : $('#block_assigned_user').show();                   break;
      case '4' : $('#block_assigned_question_user').show();          break;
      case '5' : $('#block_assigned_group').show();                  break;
      case '6' : $('#block_assigned_question_group').show();         break;
      case '9' : $('#block_assigned_question_actors').show();        break;
      case '10': $('#block_assigned_group_from_object').show();      break;
      case '11': $('#block_assigned_tech_group_from_object').show(); break;
      case '9' : $('#block_assigned_question_actors').show();        break;
      case '7' : $('#block_assigned_supplier').show();               break;
      case '8' : $('#block_assigned_question_supplier').show();      break;
   }
}

// === FIELDS EDITION ===

function plugin_formcreator_changeDropdownItemtype(rand) {
   var dropdown_type = $('[data-itemtype="PluginFormcreatorQuestion"] [name="dropdown_values"]').val();
   var dropdown_id   = $('[data-itemtype="PluginFormcreatorQuestion"] [name="id"]').val();

   $.post({
      url: formcreatorRootDoc + '/ajax/dropdown_values.php',
      type: 'GET',
      data: {
         dropdown_itemtype: dropdown_type,
         'id': dropdown_id
      },
   }).done(function(response) {
      var showTicketCategorySpecific = false;
      var showServiceLevelSpecific = false;
      if (dropdown_type == 'ITILCategory') {
         showTicketCategorySpecific = true;
      } else if (dropdown_type == 'SLA' || dropdown_type == 'OLA') {
         showServiceLevelSpecific = true;
      }
      $('#dropdown_default_value_field').html(response);
      $('.plugin_formcreator_dropdown_ticket').toggle(showTicketCategorySpecific);
      $('.plugin_formcreator_dropdown_service_level').toggle(showServiceLevelSpecific);

      if (!isNaN(dropdown_type)) {
         // The variable seems not to be a itemtype
         return;
      }

      $.post({
         url: formcreatorRootDoc + '/ajax/commontree.php',
         data: {
            itemtype: dropdown_type,
            root: $("#commonTreeDropdownRoot").val(),
            maxDepth: $("#commonTreeDropdownMaxDepth").val(),
            selectableRoot: $("#commonTreeDropdownSelectableRoot").val(),
         },
      }).done(function(response) {
         $('.plugin_formcreator_dropdown').html(response);
         $('.plugin_formcreator_dropdown').toggle(true);
      }).fail(function() {
         $('.plugin_formcreator_dropdown').html("");
         $('.plugin_formcreator_dropdown').toggle(false);
      });

      var entityAssignable = [
         'Location',
         'ITILCategory',
         'TaskCategory',
         'TaskTemplate',
         'SolutionType',
         'SolutionTemplate',
         'ProjectTaskTemplate',
         'SoftwareLicenseType',
         'CertificateType',
         'RackType',
         'PDUType',
         'ClusterType',
         'BusinessCriticity',
         'KnowbaseItemCategory',
         'Calendar',
         'Holiday',
         'Netpoint',
         'Vlan',
         'LineOperator',
         'DomainType',
         'DomainRecordType',
         'DomainRelation',
         'IPNetwork',
         'FQDN',
         'WifiNetwork',
         'NetworkName',
         'Fieldblacklist',
         'ApplianceType'
      ];
      var showEntityAssignable = (entityAssignable.indexOf(dropdown_type) >= 0);
      $('.plugin_formcreator_entity_assignable').toggle(showEntityAssignable);
   });
}

function plugin_formcreator_changeGlpiObjectItemType() {
   var glpi_object    = $('[data-itemtype="PluginFormcreatorQuestion"] [name="glpi_objects"]').val();
   var glpi_object_id = $('[data-itemtype="PluginFormcreatorQuestion"] [name="id"]').val();

   $.post({
      url: formcreatorRootDoc + '/ajax/dropdown_values.php',
      data: {
         dropdown_itemtype: glpi_object,
         id: glpi_object_id
      },
   }).done(function(response) {
      $('#dropdown_default_value_field').html(response);
   });

   $.post({
      url: formcreatorRootDoc + '/ajax/commontree.php',
      data: {
         itemtype: glpi_object,
         root: $("#commonTreeDropdownRoot").val(),
         maxDepth: $("#commonTreeDropdownMaxDepth").val(),
         selectableRoot: $("#commonTreeDropdownSelectableRoot").val(),
      },
   }).done(function(response) {
      $('.plugin_formcreator_dropdown').html(response);
      $('.plugin_formcreator_dropdown').toggle(true);
   }).fail(function() {
      $('.plugin_formcreator_dropdown').html("");
      $('.plugin_formcreator_dropdown').toggle(false);
   });
}

// === CONDITIONS ===

function plugin_formcreator_toggleCondition(target) {
   var form = $(target).closest('form');

   var selector = 'tr[data-itemtype="PluginFormcreatorCondition"]';
   if (target.value == '1') {
      form.find(selector).hide();
   } else {
      if (form.find(selector).length < 1) {
         plugin_formcreator_addEmptyCondition(target);
      }
      form.find(selector).show();
   }
}

function plugin_formcreator_addEmptyCondition(target) {
   var form     = $(target).closest('form');
   var itemtype = form.attr('data-itemtype');
   // value if the hidden id input field
   var id       = form.find('[name="id"]').val();
   var parentKey;
   var parentId;
   var data = form.serializeArray();
   data.push({
      name: 'itemtype',
      value: itemtype
   });
   data.push({
      name: 'items_id',
      value: id
   });
   $.ajax({
      type: 'POST',
      url: formcreatorRootDoc + '/ajax/condition.php',
      data: data
   }).done(function (data) {
      $(target).parents('tr').after(data);
   });
}

function plugin_formcreator_removeNextCondition(target) {
   $(target).parents('tr').remove();
}

// === FIELDS ===

/**
 * Initialize a simple field
 */
function pluginFormcreatorInitializeField(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   var timer = getTimer(field);
   var callback = function() {
      plugin_formcreator.showFields($(field[0].form));
   }
   timer(300, callback);
   timers.push(timer);
}

/**
 * Initialize an actor field
 */
function pluginFormcreatorInitializeActor(fieldName, rand) {
   var field = $('select[name="' + fieldName + '[]"]');
   field.on("change", function(e) {
      plugin_formcreator.showFields($(field[0].form));
   });
}


/**
 * Initialize a checkboxes field
 */
function pluginFormcreatorInitializeCheckboxes(fieldName, rand) {
   var field = $('[name="' + fieldName + '[]"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a date field
 */
function pluginFormcreatorInitializeDate(fieldName, rand) {
   var field = $('[name="_' + fieldName + '"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
   $('#resetdate' + rand).on("click", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a dropdown field
 */
function pluginFormcreatorInitializeDropdown(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   field.on("change", function(e) {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a email field
 */
function pluginFormcreatorInitializeEmail(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   var timer = getTimer(field);
   var callback = function() {
      plugin_formcreator.showFields($(field[0].form));
   }
   timer(300, callback);
   timers.push(timer);
}

/**
 * Initialize a radios field
 */
function pluginFormcreatorInitializeRadios(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a multiselect field
 */
function pluginFormcreatorInitializeMultiselect(fieldName, rand) {
   var field = $('select[name="' + fieldName + '[]"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a request type field
 */
function pluginFormcreatorInitializeRequestType(fieldName, rand) {
   var field = $('select[name="' + fieldName + '"]');
   field.on("change", function(e) {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a select field
 */
function pluginFormcreatorInitializeSelect(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a tag field
 */
function pluginFormcreatorInitializeTag(fieldName, rand) {
   var field = $('[name="' + fieldName + '[]"]');
   field.on("change", function(e) {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a textarea field
 */
function pluginFormcreatorInitializeTextarea(fieldName, rand) {
   if (tinyMCE.majorVersion < 5) {
      var field = $('[name="' + fieldName + '"]');
      field.on("change", function(e) {
         plugin_formcreator.showFields($(field[0].form));
      });
   } else {
      var i = 0;
      var e;
      while (e = tinymce.get(i++)) {
         var field = $('[name="' + fieldName + '"]');
         var form = field[0].form;
         if (e.formElement != form) {
            continue;
         }
         // https://stackoverflow.com/a/63342064
         e.on('input NodeChange', function(e) {
            tinyMCE.triggerSave();
            plugin_formcreator.showFields($(form));
         });
         return;
     }
   }
}

/**
 * Initialize a time field
 */
function pluginFormcreatorInitializeTime(fieldName, rand) {
   var field = $('[name="_' + fieldName + '"]');
   field.on("change", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
   $('#resetdate' + rand).on("click", function() {
      plugin_formcreator.showFields($(field[0].form));
   });
}

/**
 * Initialize a urgency field
 */
function pluginFormcreatorInitializeUrgency(fieldName, rand) {
   var field = $('[name="' + fieldName + '"]');
   field.on("change", function(e) {
      plugin_formcreator.showFields($(field[0].form));
   });
}

function plugin_formcreator_changeQuestionType(rand) {
   var questionId = $('form[name="form"][data-itemtype="PluginFormcreatorQuestion"] [name="id"]').val();
   var questionType = $('form[name="form"][data-itemtype="PluginFormcreatorQuestion"] [name="fieldtype"]').val();

   $.post({
      url: formcreatorRootDoc + '/ajax/question_design.php',
      data: {
         questionId: questionId,
         questionType: questionType,
      },
   }).done(function(response) {
      try {
         var response = JSON.parse(response);
      } catch (e) {
         console.error('Plugin Formcreator: Failed to get subtype fields');
         return;
      }

      $('.plugin_formcreator_required').attr('disabled', !response.may_be_required);
      $('.plugin_formcreator_mayBeEmpty').attr('disabled', !response.may_be_empty);
      $('#plugin_formcreator_subtype_label').html(response.label);
      $('#plugin_formcreator_subtype_value').html(response.field);
      plugin_formcreator_updateQuestionSpecific(response.additions);
   });
}

function plugin_formcreator_updateQuestionSpecific(html) {
   $('.plugin_formcreator_question_specific').slice(1).remove();
   if (html == '') {
      $('.plugin_formcreator_question_specific').hide();
      return;
   }
   $('.plugin_formcreator_question_specific').replaceWith(html);
}

function plugin_formcreator_changeLDAP(ldap) {
   var ldap_directory = ldap.value;

   jQuery.ajax({
   url: formcreatorRootDoc + '/ajax/ldap_filter.php',
   type: 'POST',
   data: {
         value: ldap_directory,
      },
   }).done(function(response) {
      document.getElementById('ldap_filter').value = response;
   });
}

/**
 * preview of the selected pictogram
 */
function plugin_formceator_showPictogram(id, preview) {
   var value = $(id).val();
   $('#' + preview).removeClass().addClass(value);
}

/**
 * update composite ticket (links between tickets) in target ticket (design mode)
 */
function plugin_formcreator_updateCompositePeerType(type) {
   $('#plugin_formcreator_link_ticket').hide();
   $('#plugin_formcreator_link_target').hide();
   $('#plugin_formcreator_link_question').hide();

   switch ($(type).val()) {
      case 'Ticket':
         $('#plugin_formcreator_link_ticket').show();
         break;
      case 'PluginFormcreatorTargetTicket':
         $('#plugin_formcreator_link_target').show();
         break;
      case 'PluginFormcreatorQuestion':
         $('#plugin_formcreator_link_question').show();
         break;
   }
}

/**
 * update category settings in a form of a target object (design mode)
 */
 function plugin_formcreator_changeCategory(rand) {
   $('#category_specific_title').hide();
   $('#category_specific_value').hide();
   $('#category_question_title').hide();
   $('#category_question_value').hide();

   switch($('#dropdown_category_rule' + rand).val()) {
      case '3' :
         $('#category_question_title').show();
         $('#category_question_value').show();
         break;
      case '2' :
         $('#category_specific_title').show();
         $('#category_specific_value').show();
         break;
   }
}

/**
 * change request type of a target item (design mode)
 */
function plugin_formcreator_changeRequestType(rand) {
   $('#requesttype_specific_title').hide();
   $('#requesttype_specific_value').hide();
   $('#requesttype_question_title').hide();
   $('#requesttype_question_value').hide();

   switch($('#dropdown_type_rule' + rand).val()) {
      case '1': // PluginFormcreatorTargetTicket::REQUESTTYPE_SPECIFIC
         $('#requesttype_specific_title').show();
         $('#requesttype_specific_value').show();
         break;
      case '2': // PluginFormcreatorTargetTicket::REQUESTTYPE_ANSWER
         $('#requesttype_question_title').show();
         $('#requesttype_question_value').show();
         break;
   }
}

/**
 * change urgency of a target item (design mode)
 */
function plugin_formcreator_changeUrgency(rand) {
   $('#urgency_specific_title').hide();
   $('#urgency_specific_value').hide();
   $('#urgency_question_title').hide();
   $('#urgency_question_value').hide();

   switch($('#dropdown_urgency_rule' + rand).val()) {
      case '2' :
         $('#urgency_specific_title').show();
         $('#urgency_specific_value').show();
         break;
      case '3':
         $('#urgency_question_title').show();
         $('#urgency_question_value').show();
         break;
   }
}

function plugin_formcreator_change_associate(rand) {
   $('#plugin_formcreator_associate_specific_title').hide();
   $('#plugin_formcreator_associate_specific_value').hide();
   $('#plugin_formcreator_associate_question_title').hide();
   $('#plugin_formcreator_associate_question_value').hide();

   switch($('#dropdown_associate_rule' + rand).val()) {
      case '3': // PluginFormcreatorTargetTicket::ASSOCIATE_RULE_ANSWER
         $('#plugin_formcreator_associate_question_title').show();
         $('#plugin_formcreator_associate_question_value').show();
         break;
      case '2': // PluginFormcreatorTargetTicket::ASSOCIATE_RULE_SPECIFIC
         $('#plugin_formcreator_associate_specific_title').show();
         $('#plugin_formcreator_associate_specific_value').show();
         break;
   }
}

function plugin_formcreator_change_location(rand) {
   $('#location_specific_title').hide();
   $('#location_specific_value').hide();
   $('#location_question_title').hide();
   $('#location_question_value').hide();

   switch($('#dropdown_location_rule' + rand).val()) {
      case '3' : // PluginFormcreatorAbstractTarget::CATEGORY_RULE_ANSWER
         $('#location_question_title').show();
         $('#location_question_value').show();
         break;
      case '2' : // PluginFormcreatorAbstractTarget::CATEGORY_RULE_SPECIFIC
         $('#location_specific_title').show();
         $('#location_specific_value').show();
         break;
   }
}

function plugin_formcreator_change_entity(rand) {
   $('#entity_specific_title').hide();
   $('#entity_user_title').hide();
   $('#entity_entity_title').hide();
   $('#entity_specific_value').hide();
   $('#entity_user_value').hide();
   $('#entity_entity_value').hide();

   switch($('#dropdown_destination_entity' + rand).val()) {
      case '7' : // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_SPECIFIC
         $('#entity_specific_title').show();
         $('#entity_specific_value').show();
         break;
      case '8' : // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_USER
         $('#entity_user_title').show();
         $('#entity_user_value').show();
         break;
      case '9' : // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_ENTITY
         $('#entity_entity_title').show();
         $('#entity_entity_value').show();
         break;
   }
}

function plugin_formcreator_changeValidators(value) {
   if (value == 1) {
      document.getElementById("validators_users").style.display  = "block";
      document.getElementById("validators_groups").style.display = "none";
   } else if (value == 2) {
      document.getElementById("validators_users").style.display  = "none";
      document.getElementById("validators_groups").style.display = "block";
   } else {
      document.getElementById("validators_users").style.display  = "none";
      document.getElementById("validators_groups").style.display = "none";
   }
}

function plugin_formcreator_cancelMyTicket(id) {
   $.ajax({
      url: formcreatorRootDoc + '/ajax/cancelticket.php',
      data: {id: id},
      type: "POST",
      dataType: "text"
   }).done(function(response) {
      window.location.replace(formcreatorRootDoc + '/front/issue.php?reset=reset');
   }).error(function(response) {
      alert(response.responseText);
   });
}

function plugin_formcreator_refreshCaptcha() {
   var captchaId = $('input[name="plugin_formcreator_captcha_id"]').val();
   $('form[name="plugin_formcreator_form"] button[type="submit"]').attr('disabled', 'disabled');
   $.ajax({
      url : formcreatorRootDoc + '/ajax/getcaptcha.php',
      data: {captcha_id: captchaId},
      type: 'POST',
      dataType: 'text'
   }).done(function(response) {
      $('#plugin_formcreator_captcha_section img').attr('src', response);
   }).complete(function(response) {
      $('form[name="plugin_formcreator_form"] button[type="submit"]').removeAttr('disabled');
   });
}