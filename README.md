# Form Creator

[![License GPL 3.0](https://img.shields.io/badge/License-GPL%203.0-blue.svg)](https://github.com/itsmng/formcreator/blob/main/LICENSE.md)
[![Project Status: Active](http://www.repostatus.org/badges/latest/active.svg)](http://www.repostatus.org/#active)
[![Conventional Commits](https://img.shields.io/badge/Conventional%20Commits-1.0.0-yellow.svg)](https://conventionalcommits.org)
[![GitHub All Releases](https://img.shields.io/github/downloads/itsmng/formcreator/total)](https://github.com/itsmng/formcreator/releases)

Extend GLPI with Plugins.

## Table of Contents

  - [Form Creator](#form-creator)
  - [Table of Contents](#table-of-contents)
  - [Synopsis](#synopsis)
  - [Features](#features)
  - [Build Status](#build-status)
  - [Documentation](#documentation)
  - [Versioning](#versioning)
  - [Contact](#contact)
  - [Professional Services](#professional-services)
  - [Build from source](#build-from-source)
  - [Contribute](#contribute)
  - [Copying](#copying)

## Synopsis

Formcreator is a plugin which allow creation of custom forms of easy access.
At the same time, the plugin allow the creation of one or more tickets when the form is filled.

## Features

1. Direct access to forms self-service interface in main menu
2. Highlighting forms in homepages
3. Access to forms controlled: public access, identified user access, restricted access to some profiles
4. Simple and customizable forms
5. Forms organized by categories, entities and languages.
6. Questions of any type of presentation: Textareas, lists, LDAP, files, etc.
7. Questions organised in sections. Choice of the display order.
8. Possibility to display a question based on certain criteria (response to a further question)
9. A sharp control on responses from forms: text, numbers, size of fields, email, mandatory fields, regular expressions, etc.
10. Creation of one or more tickets from form answers
11. Adding a description per fields, per sections, per forms, entities or languages.
12. Formatting the ticket set: answers to questions displayed, tickets templates.
13. Preview form created directly in the configuration.
14. An optional service catalog to browse for forms and FAQ in an unified interface.

## Documentation

We maintain a detailed documentation of the project on the website, check the [How-tos](https://pluginsglpi.github.io/formcreator/howtos/) and [Development](https://pluginsglpi.github.io/formcreator/) section.

For more information you can visit [formcreator on the GLPI Plugins documentation](http://glpi-plugins.readthedocs.io/en/latest/formcreator/)

## Build from source

To build the plugin you need [Composer](http://getcomposer.org) and an internet access to download some resources from Github.

After dowloading the source of Formcreator, go in its folder and run the following
* composer install

## Contribute

Want to file a bug, contribute some code, or improve documentation? Excellent! Read up on our
guidelines for [contributing](https://github.com/itsmng/formcreator/blob/main/.github/CONTRIBUTING.md) and then check out one of our issues in the [Issues Dashboard](https://github.com/itsmng/formcreator/issues).

## Copying

* **Code**: you can redistribute it and/or modify it under the terms of the GNU General Public License ([GPLv3](https://www.gnu.org/licenses/gpl-3.0.en.html)).
* **Documentation**: released under Attribution 4.0 International ([CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)).
