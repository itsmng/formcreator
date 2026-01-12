"use strict";
$(function () {
    var logoLink = $('#c_logo > a');
    if (logoLink.length) {
        var href = logoLink.attr('href');
        if (href && href.indexOf('central.php') !== -1) {
            var wizardUrl;
            if (typeof formcreatorRootDoc !== 'undefined') {
                wizardUrl = formcreatorRootDoc + '/front/wizard.php';
            } else {
                wizardUrl = CFG_GLPI.root_doc + '/plugins/formcreator/front/wizard.php';
            }
            logoLink.attr('href', wizardUrl);
        }
    }
});
