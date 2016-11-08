$(function(){


    //$(window).on("navigate", function (event, data) {
    //    //var direction = data.state.direction;
    //    //if ( !! direction) {
    //    //    alert(direction);
    //    //}
    //
    //    console.log(data);
    //});
    $.nette.ext('history').cache = false;

    $.nette.ext('fadeSnippets', {
        start: function (xhr, settings) {
            $("#snippet--content").stop().fadeTo("slow", 0.5);
        },
        complete: function () {
            $("#snippet--content").stop().fadeTo("slow", 1);
        }
    }, {
        snippets: {}
    });



    $.nette.ext('keepActiveTab', {
        before: function(xhr, settings) {
            var tabs = [];
            $('ul.nav > li.active > a[data-toggle=tab]').each(function() {
                tabs.push( this.hash );
            });
            this.activeTabs = tabs;
        },
        success: function() {
            var tabs = this.activeTabs;
            if (tabs.length) {
                for (t in tabs) {
                    console.log(tabs[t]);
                    $('ul.nav > li > a[data-toggle=tab][href="'+tabs[t]+'"]').tab('show');
                }
            }
        }
    }, {
        activeTabs: null
    });


   /* $.nette.ext('modal', {
        load: function(requestHandler) {
            $('a[data-ajax-modal]').off('click').on('click', requestHandler); // TODO proste tomu dat tridu ajax
        },
        before: function(jqXHR, settings) {
            if(settings.nette.el.is('[data-ajax-modal]'))
                this.snippetToModal = settings.nette.el.data('ajax-modal');
            else
                this.snippetToModal = null;

        },
        success: function (payload, status, jqXHR, settings) {
            if(!this.snippetToModal)
                return;
            var snippetId = 'snippet--'+this.snippetToModal;
            var content = payload.snippets[snippetId];

            var $modal = $(content);
            var modal = $modal.modal(); // otevrit modal

            // po otevreni modalu navesime udalosti
            modal.on('shown.bs.modal', function () {
                $.nette.load();
            });
            // po zavreni modalu odstranit jeho html
            modal.on('hidden.bs.modal', function () {
                $(this).remove();
            });
        }
    }, {
        // ... shared context (this) of all callbacks
        snippetToModal: null
    });*/

    /**
     * This file is part of the Nextras community extensions of Nette Framework
     *
     * @license    MIT
     * @link       https://github.com/nextras
     * @author     Jan Skrasek
     */

    $.nette.ext('datagrid', {
        init: function() {
            var datagrid = this;
            this.grids = $('.grid').each(function() {
                datagrid.load($(this));
            });
        },
        load: function() {
            var datagrid = this;
            $('.grid thead input').off('keypress.datagrid').on('keypress.datagrid', function(e) {
                if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                    $(this).parents('tr').find('[name=filter\\[filter\\]]').trigger(datagrid.createClickEvent($(this)));
                    e.preventDefault();
                }
            });
            $('.grid thead select').off('change.datagrid').on('change.datagrid', function(e) {
                $(this).parents('tr').find('[name=filter\\[filter\\]]').trigger(datagrid.createClickEvent($(this)));
                e.preventDefault();
            });
            $('.grid tbody td:not(.grid-col-actions)').off('click.datagrid').on('click.datagrid', function(e) {
                if (e.ctrlKey) {
                    $(this).parents('tr').find('a[data-datagrid-edit]').trigger(datagrid.createClickEvent($(this)));
                    e.preventDefault();
                }
            });
            $('.grid tbody input').off('keypress.datagrid').on('keypress.datagrid', function(e) {
                if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                    $(this).parents('tr').find('[name=edit\\[save\\]]').trigger(datagrid.createClickEvent($(this)));
                    e.preventDefault();
                }
            });
        },
        before: function(xhr, settings) {
            if(settings.nette)
                this.grid = settings.nette.el.parents('.grid');
        },
        success: function() {
            this.load(this.grid);
        }
    }, {
        activeGrid: null,
        load: function(grid) {
            var idToClose = [];
            var paramName = grid.attr('data-grid-name');
            grid.find('tr:has([name=edit\\[cancel\\]])').each(function(i, el) {
                $(el).find('input').get(0).focus();
                idToClose.push($(el).find('.grid-primary-value').val());
            });

            if (idToClose.length == 0) {
                return;
            }

            grid.find('a[data-datagrid-edit]').each(function() {
                var href = $(this).data('grid-href');
                if (!href) {
                    $(this).data('grid-href', href = $(this).attr('href'));
                }

                $(this).attr('href', href + '&' + paramName + '-cancelEditPrimaryValue=' + idToClose.join(','));
            });
        },
        createClickEvent: function(item) {
            var offset = item.offset();
            return jQuery.Event('click', {
                pageX: offset.left + item.width(),
                pageY: offset.top + item.height()
            });
        }
    });


    $.nette.init();




    $('select[multiple]').livequery(function(){
        $(this).multiselect({
            nonSelectedText: '- vyberte -',
            nSelectedText: 'vybráno',
            numberDisplayed: 0,
            buttonClass: 'btn btn-default btn-sm'
        });
    });



    $('.ajax-edit').livequery(function(){
        var showEdit = true;
//        var objectId = $(this).closest('form').data('object-id');
        var ajaxEditLink = $(this).closest('form').data('edit-link');

        var $_this = $(this).css('position','relative');
        var $value = $(this).find('.ajax-edit-val').css('position','relative');

        var $target = $(this).find('.ajax-edit-target')
            .css({
                position: 'absolute',
                top: 0,
                left: 0,
                zIndex: 100
            })
            .appendTo($value)
            .hide();
        var $status = $('<span class="status"></span>')
            .css({
                position: 'absolute',
                top: 6,
                right: 0,
                fontSize: '10px',
                fontWeight: 'normal'
            })
            .appendTo($(this))
            .hide();



        // vezme nazev snippetu
        var snippet = $value.find('[id^=snippet--]').attr('id').replace('snippet--','');

        var type = 'byInputBlur';
        if($target.find('button[role=save]').length > 0)
            type = 'byButton';

         var obj2array = function(values) {
            var sendValues = {};
            for (var i = 0; i < values.length; i++) {
                var name = values[i].name;

                // multi
                if (name in sendValues) {
                    var val = sendValues[name];
                    if (!(val instanceof Array)) {
                        val = [val];
                    }
                    val.push(values[i].value);
                    sendValues[name] = val;
                } else {
                    sendValues[name] = values[i].value;
                }
            }
            return sendValues;
        }

        var saveValues = function(){
            //var data = $target.find('input,textarea,select').serializeArray();
            //data = obj2array(data);

            var data = $target.find('input,textarea,select').serializeFullArray();
            // vytahneme i checkboxy
            $target.find('input:checkbox').each(function(){
                data[this.name] = this.checked ? 1 : 0;
            });


            $.post(ajaxEditLink, {
                dataType: 'json',
                data: data,
                snippet: snippet
            },function(payload){
                if(payload.status == 200) {
                    showStatus('Uloženo','success');
                    $target.fadeOut(function(){
                        showEdit = true;
                    });
                    for(var s in payload.snippets)
                    {
                        $('#' + s).html(payload.snippets[s]);
                    }

                } else {
                    showStatus(payload.message ? payload.message : 'Chyba','danger');
                }
            });
        }

        var showStatus = function(text, type) {
            $status.attr('class','status text-'+type).text(text).fadeIn(function(){
                $(this).delay(1000).fadeOut();
            });
        }


        $(this).click(function(e) {
            e.stopPropagation();
//            e.preventDefault();

            showEdit = false;
            // Zobrazime targetBlok a nastavime akci blur
            if(type == 'byButton') {
                $target.fadeIn().find('button[role=save]').unbind('click').click(saveValues);
            } else {
                var width = $value.width()-15;
                $target.fadeIn().find('input,textarea,select').filter(':first').focus().unbind('blur').blur(saveValues).width(width);
            }
        });


        var $edit = $('<a href="#" class="edit">Editovat</a>')
            .css({
                position: 'absolute',
                top: 6,
                right: 0,
                fontSize: '10px',
                fontWeight: 'normal',
                zIndex: 101
            })
            .appendTo($(this))
            .hide()
            .click(function() {

                // Schovame edit odkaz a zakazeme jeho zobrazovani
                showEdit = false;
                $(this).fadeOut();
                // Zobrazime targetBlok a nastavime akci blur
                if(type == 'byButton') {
                    $target.fadeIn().find('button[role=save]').unbind('click').click(saveValues);
                } else {
                    var width = $value.width()-15;
                    $target.fadeIn().find('input,textarea,select').filter(':first').focus().unbind('blur').blur(saveValues).width(width);
                }
                return false;
            });


        $(this).hover(function(){
            if(showEdit) {
                $status.hide();
                $edit.fadeIn();
            }
        },function(){
            $edit.fadeOut();
        });

    });
});


/**
 * Czech translation for bootstrap-datetimepicker
 * Matěj Koubík <matej@koubik.name>
 * Fixes by Michal Remiš <michal.remis@gmail.com>
 */
;(function($){
    $.fn.datetimepicker.dates['cs'] = {
        days: ["Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle"],
        daysShort: ["Ned", "Pon", "Úte", "Stř", "Čtv", "Pát", "Sob", "Ned"],
        daysMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So", "Ne"],
        months: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
        monthsShort: ["Led", "Úno", "Bře", "Dub", "Kvě", "Čer", "Čnc", "Srp", "Zář", "Říj", "Lis", "Pro"],
        today: "Dnes",
        suffix: [],
        meridiem: []
    };
}(jQuery));

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/forms
 * @author     Jan Skrasek
 */



/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/forms
 * @author     Jan Skrasek
 */

jQuery(function($) {
    $('input.date, input.datetime-local').livequery(function(i, el) {
        el = $(el);
        el.get(0).type = 'text';
        el.datetimepicker({
            language: 'cs',
            startDate: el.attr('min'),
            endDate: el.attr('max'),
            weekStart: 1,
            minView: el.is('.date') ? 'month' : 'hour',
            format: el.is('.date') ? 'd. m. yyyy' : 'd. m. yyyy - hh:ii', // for seconds support use 'd. m. yyyy - hh:ii:ss'
            autoclose: true
        });
        el.attr('value') && el.datetimepicker('setValue');
    });
});

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license    MIT
 * @link       https://github.com/nextras/forms
 * @author     Jan Skrasek
 */

jQuery(function($) {
    $('.typeahead[data-typeahead-url]').livequery(function() {
        $(this).typeahead({
            remote: {
                url: $(this).attr('data-typeahead-url'),
                wildcard: '__QUERY_PLACEHOLDER__'
            }
        });
    });
});


