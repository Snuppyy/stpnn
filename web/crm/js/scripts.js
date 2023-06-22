var reminders_iterator, notifications_iterator;
var reminders_times, notifications_times;
var reminders_pause = false, notifications_pause = false;
var reminderID = 0;
var notiID = 0;
var notifications;
var file;
$(document).ready(function () {
    $('.switcheryLongReq').on('change', function(e){
        var _self = $(this);
        e.preventDefault();
        $.ajax({
            url: '/request/set-long-type',
            type: 'POST',
            dataType: 'json',
            data:{
                id: _self.data('id'),
            }
        }).done(function( respond ){
            if(respond.code == 'success'){
                window.location.reload();
                /*swal("Успешно", 'Скопированы телефоны', "success").done();
                var copyWrapEmails = $('#copyWrapEmails');
                copyWrapEmails.val(respond.emails);
                copyWrapEmails.select();
                var selection = window.getSelection();

                // Копируем выделенное в буфер обмена.
                document.execCommand('copy');
                // Можем очистить выделение.
                selection.removeAllRanges();*/
            }else{
                swal("Ошибка", respond.msg, "error").done();
            }
        }).fail(function( jqXHR,error ){
            var text = error;
            if(jqXHR.responseJSON != undefined){
                text = jqXHR.responseJSON.join('<br>')
            }else{
                text = jqXHR.responseText;
            }
            swal("Ошибка", text, "error").done();
        });
    });

    change_total_income_filter();
    $('.clickPage').on('click', function(event){
        event.preventDefault();
        if(!$(this).hasClass('btn-success')){
            let str = window.location.href;
            if(str.indexOf('?') != -1)
            {
                str = str.split('?');
                str = str[0];
                str += '?user_id='+$(this).data('user_id');
            }
            else
            {
                str += '?user_id='+$(this).data('user_id');
            }
            window.location.href = str;
        }
        else
        {
            let str = window.location.href;
            str = str.split('?');
            str = str[0];
            window.location.href = str;
            return false;
        }
    });

    $('body').on('click','.trashRequest,.trashElement', function(event){
        event.preventDefault();
        var _id = $(this).data('request_id');
        var parent = $(this).parents('[data-parent]');
        if(parent.length && _id != undefined){
            parent = $(parent[0]);
            var remove = confirm('Вы действительно хотите удалить?');
            if(remove){
                var link = $(this).data('link');
                if(link != undefined){
                    $.ajax({
                        url: link,
                        type: 'POST',
                        cache: false,
                        dataType: 'json'
                    }).done(function( respond ){
                        parent.remove();
                    }).fail(function( jqXHR, textStatus ){
                        console.log('error');
                    });
                }else{
                    parent.remove();
                }
            }
        }
    });

    if($.fn.pickadate !== undefined){
        $('.pickadate').each(function () {
            var _self = $(this);
            $(this).pickadate({
                format: 'dd.mm.yyyy',
                firstDay: 1,
                monthsFull: [
                    'Январь',
                    'Февраль',
                    'Март',
                    'Апрель',
                    'Май',
                    'Июнь',
                    'Июль',
                    'Август',
                    'Сентябрь',
                    'Октябрь',
                    'Ноябрь',
                    'Декабрь'
                ],
                weekdaysShort: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                today: "",
                clear: '',
                close: "",
                selectYears: 70,
                selectMonths: true,
                max: _self.data('max')
            });
        });
        if($.fn.pickatime !== undefined) {
            $('.timepicker').pickatime({
                format: 'H:i',
                min: [6,00],
                max: [22,00],
                interval: 15,
                clear: 'Очистить',
                //editable: true
            });
        }


    }

    phoneMask(".phoneMask");

    $('#checkbox-notifications-input').on('change', function(){
        $(this).parents('.checkbox-notifications').find('.checkbox-notifications-group').toggleClass('show');
    });

    $('body').on('click','.addItem', function () {

        var item = $(this).next(),
            wrap = $(this).prev();

        if(wrap != undefined && item != undefined){
            var rand = 'rand_'+Math.round(Math.random() * 1000000);
            var clone = item.clone(true, true).removeAttr('hidden').data('clone',rand);
            var disabledElements = clone.find('[disabled]');
            if ($(disabledElements).length) {
                $(disabledElements).each(function () {
                    if ($(this).parents('[hidden]').length == 0)
                        $(this).removeAttr('disabled');
                    var name = $(this).attr('name');
                    if(name.indexOf('avClone') != -1){
                        name = name.replace(/avClone/,rand);
                        $(this).attr('name',name);
                    }
                });
            }
            wrap.append(clone);
            afterAppend(clone);
        }
    });

    $('#formSignup').on('submit',function (event) {
        event.preventDefault();
        if(event.isTrigger == undefined){
            var _self = this,
                data = $(this).serialize(),
                link = $(this).attr('action'),
                btn = $(this).find('[type="submit"]');
            if(btn != undefined && !btn.prop('disabled')){
                btn.attr('disabled', true);
                $.ajax({
                    url: link,
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: data
                }).done(function( respond ){
                    if(respond.code == 'success'){
                        _self.reset();
                        $('.avatarImage').remove();
                        swal("Успешно", respond.msg, "success").done();
                    }else{
                        swal("Ошибка", respond.msg, "error").done();
                    }

                }).fail(function (respond,error,xhr) {
                    var text = '';
                    $.each(respond.responseJSON,function (index,element) {
                        $('input[id*="-'+index+'"]').parents('.form-group').addClass('error');
                        text += index+': '+element+'<br/>';
                    });
                    swal("Ошибка", text, "error").done();
                }).always(function () {
                    btn.removeAttr('disabled');
                });
            }
        }
    });

    $('#userprofile-avatar').on('change',function (event) {
        var _self = $(this);
        var files = event.target.files;
        var data = new FormData();
        $.each(files, function(key, value)
        {
            data.append('UploadForm[files]', value);
        });
        $.ajax({
            url: '/user/file-upload',
            type: 'POST',
            data: data,
            cache: false,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(data)
            {
                if(data != false){
                    $.each(data,function (index,el) {
                        $('.avatarImage').remove();
                        _self
                            .parents('.form-group')
                            .prepend('<img class="avatarImage" src="'+el+'">')
                            .append('<input type="hidden" name="'+_self.attr("name")+'" value="'+el+'" class="avatarImage">');
                        _self.val('');
                        $(_self).val(el);
                    });

                }
            }
        });
    })

    //Отпрытие окон из дропдауна
    $('.modalShow').on('click', function () {
        var modalShow = $(this).data('target');
        if($(modalShow).length){
            var not_user = $(this).data('not_user_id');
            if(not_user != undefined){
                var button = $(modalShow).find('.modal-body button[data-user_id="'+not_user+'"]');
                var buttons = $(modalShow).find('.modal-body button[data-user_id]');
                if(button != undefined){
                    button.prop('disabled',true);
                    $(modalShow).on('hidden.bs.modal', function () {
                        $(this).find('.modal-body button[data-user_id]').prop('disabled', false);
                        $(this).find('.modal-body button[data-user_id]').each(function () {
                            $(this).removeAttr('data-user_id_old');
                        })
                    })
                }
                if(buttons != undefined){
                    buttons.each(function () {
                      $(this).attr('data-user_id_old', not_user);
                    });
                }
            }
            $(modalShow).find('.modal-body').data('id',$(this).data('request_id'))
        }
    });

    //Сделать назначение
    $('.btnPurposeModal').on('click', function () {
        var _self = $(this),
            user_id = $(this).data('user_id'),
            req_id = $(this).parents('.modal-body').data('id');
        _self.prop('disabled',true);
        $.ajax({
            url: '/request/start-purpose',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: {
                user_id: user_id,
                req_id: req_id
            }
        }).done(function( respond ){
            _self.prop('disabled', false);
            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                swal("Успешно", respond.msg, "success").done();
                var modal = '#'+_self.parents('.modal.fade.show').attr('id');
                $(modal).on('hidden.bs.modal', function () {
                    window.location.reload(true);
                })
            }

        }).fail(function( jqXHR, textStatus ){
            _self.prop('disabled', false);
            swal("Ошибка", textStatus, "error").done();
        });

    });
    //Сменить исполнителя
    $('.btnPurposeModalChange').on('click', function () {
        var _self = $(this),
            user_id = $(this).data('user_id'),
            user_id_old = $(this).data('user_id_old'),
            req_id = $(this).parents('.modal-body').data('id');
        _self.prop('disabled',true);
        $.ajax({
            url: '/request/change-purpose',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: {
                user_id: user_id,
                user_id_old: user_id_old,
                req_id: req_id
            }
        }).done(function( respond ){
            _self.prop('disabled', false);
            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                swal({
                    title: 'Успешно',
                    text: respond.msg,
                    type: 'success',
                    showCancelButton: false,
                    onClose: function () {
                        window.location.reload(true);
                    }
                });
            }

        }).fail(function( jqXHR, textStatus ){
            _self.prop('disabled', false);
            swal("Ошибка", textStatus, "error").done();
        });

    });
    // Отменить назначение если ты админ
    $('.btnPurposeCancel').on('click', function () {
        var _self = $(this),
            user_id = $(this).data('user_id'),
            req_id = $(this).data('req_id');
        _self.prop('disabled',true);
        $.ajax({
            url: '/request/cancel-purpose',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: {
                user_id: user_id,
                req_id: req_id
            }
        }).done(function( respond ){

            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                swal("Успешно", respond.msg, "success").then(function(value) {
                    switch (value) {
                        default:
                            window.location.reload(true);
                    }
                });
            }
        }).fail(function( jqXHR, textStatus ){
            swal("Ошибка", textStatus, "error").done();
        }).always(function () {
            _self.prop('disabled', false);
        });

    });
    //Отказаться если ты менеджер
    $('.btnPurposeCancelManager').on('click', function () {
        var _self = $(this),
            user_id = $(this).data('user_id'),
            req_id = $(this).data('req_id');
        _self.prop('disabled',true);
        swal({
            title: 'Вы уверены?',
            text: "Вы хотите отказатся от данной заявки, подтвердите действие",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Да',
            cancelButtonText: 'Нет',
            onClose: function () {
                _self.prop('disabled', false);
            }
        }).then(function (result) {
            if(result){
                $.ajax({
                    url: '/request/cancel-purpose',
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: {
                        user_id: user_id,
                        req_id: req_id
                    }
                }).done(function( respond ){

                    if(respond.code == 'error'){
                        swal("Ошибка", respond.msg, "error").done();
                    }else if(respond.code == 'success'){
                        swal("Успешно", respond.msg, "success").then(function(value) {
                            switch (value) {
                            default:
                                window.location.reload(true);
                            }
                        });
                    }
                }).fail(function( jqXHR, textStatus ){
                    swal("Ошибка", textStatus, "error").done();
                }).always(function () {
                    _self.prop('disabled', false);
                });
            }
        });
    });
    //Прниять назначенную заявку, если ты менеджер
    $('.btnPurposeAccept').on('click', function () {
        var _self = $(this),
            user_id = $(this).data('user_id'),
            req_id = $(this).data('req_id');
        _self.prop('disabled',true);
        swal({
            title: 'Вы уверены?',
            text: "Вы хотите взяться за работу над заявкой, подтвердите действие",
            type: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Да',
            cancelButtonText: 'Нет',
            onClose: function () {
                _self.prop('disabled', false);
            }
        }).then(function (result) {
            if(result){
                $.ajax({
                    url: '/request/accept-purpose',
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: {
                        user_id: user_id,
                        req_id: req_id
                    }
                }).done(function( respond ){

                    if(respond.code == 'error'){
                        swal("Ошибка", respond.msg, "error").done();
                    }else if(respond.code == 'success'){
                        swal("Успешно", respond.msg, "success").then( function (value) {
                            switch (value) {
                                default:
                                    window.location.reload(true);
                            }
                        });
                    }
                }).fail(function( jqXHR, textStatus ){
                    swal("Ошибка", textStatus, "error").done();
                }).always(function () {
                    _self.prop('disabled', false);
                });
            }
        });
    });

    $('body').on('click', '.trashDataItem', function (event) {
        event.preventDefault();
        var item = $(this).parents('[data-item]');
        if(item.length){
            $(item[0]).remove();
        }
    })

    $('.searchOrg').on('click', function (event) {
        event.preventDefault();
        var _self = $(this),
            form = _self.parents('form');
        $.ajax({
            url: '/client/search-organization',
            type: 'POST',
            dataType: 'json',
            data: form.serializeArray()
        }).done(function( respond ){
            if(respond.code == 'success'){
                var name = 'clientorganization-';
                $.each(respond.org, function (index,el) {
                    var id = '#'+name+index;
                    if($(id).length){
                        $(id).val(el);
                    }
                })
            }else{
                swal("Не найдено", respond.msg, "error").done();
            }
        }).fail(function( jqXHR,error ){
            var text = error;
            if(jqXHR.responseJSON != undefined){
                text = jqXHR.responseJSON.join('<br>')
            }else{
                text = jqXHR.responseText;
            }
            swal("Ошибка", text, "error").done();
        });
    });
    $('.searchLizing').on('click', function (event) {
        event.preventDefault();
        var _self = $(this),
            form = _self.parents('form');
        $.ajax({
            url: '/client/search-lizing',
            type: 'POST',
            dataType: 'json',
            data: form.serializeArray()
        }).done(function( respond ){
            if(respond.code == 'success'){
                var name = 'clientlizing-';
                $.each(respond.org, function (index,el) {
                    var id = '#'+name+index;
                    if($(id).length){
                        $(id).val(el);
                    }
                })
            }else{
                swal("Не найдено", respond.msg, "error").done();
            }
        }).fail(function( jqXHR,error ){
            var text = error;
            if(jqXHR.responseJSON != undefined){
                text = jqXHR.responseJSON.join('<br>')
            }else{
                text = jqXHR.responseText;
            }
            swal("Ошибка", text, "error").done();
        });
    });

    $('form').on('blur', 'input[type=number]', function (e) {
        $(this).off('mousewheel.disableScroll')
    });
    $('form').on('focus', 'input[type=number]', function (e) {
        $(this).on('mousewheel.disableScroll', function (e) {
            e.preventDefault()
        })
    });

    $('.addNewRequest').on('click', function (event) {
        event.preventDefault();
        $('#addNewRequest').modal("show");
    });

    $('.listStatusSet').click(function (event) {
        event.preventDefault();
        var key = $(this).data('key'),
            ico = $(this).find('span');

        $('#formNewRequest').find('.setStatus').val(key);
        $(this).parents('ul').prev('.dropdown-toggle').html(ico.html());
    });
    let dataCreateReq = new FormData();
    $("#client-_file").change(function(){
        if (window.FormData === undefined) {
            alert('В вашем браузере FormData не поддерживается')
        } else {
            dataCreateReq.append('file', $(this)[0].files[0]);
        }
    });

    $('#formNewRequest').on('beforeValidate', function (event) {
        event.preventDefault();
        let _self = $(this),
            setStatus = $(this).find('.setStatus');
        _self.serializeArray().forEach(function(el, ind){
            dataCreateReq.append(el.name, el.value);
        });
        if(setStatus.val() != ''){
            $.ajax({
                url: _self.attr('action'),
                type: 'POST',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                data: dataCreateReq
            }).done(function( respond ){
                if(respond.code == 'success'){
                    swal("Успешно", respond.msg, "success").then(function () {
                        window.location.reload(true);
                    }).done();
                }else{
                    swal("Ошибка", respond.msg, "error").done();
                }
            }).fail(function( jqXHR,error ){
                var text = error;
                if(jqXHR.responseJSON != undefined){
                    if(typeof(jqXHR.responseJSON) != 'object'){
                        text = jqXHR.responseJSON.join('<br>');
                    }
                }else{
                    text = jqXHR.responseText;
                }
                swal("Ошибка", text, "error").done();
            }).always(function(){
                dataCreateReq.forEach(function(val, key){
                    dataCreateReq.delete(key);
                });
            });
        }else{
            swal("", 'Выберите статус заявки', "info").done();
        }

        return false;
    });
    $('#addOtherInfo').on('beforeValidate', function (event) {
        event.preventDefault();
        var _self = $(this);

        $.ajax({
            url: _self.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: _self.serializeArray()
        }).done(function( respond ){
            if(respond.code == 'success'){
                swal("Успешно", respond.msg, "success").then(function () {
                    window.location.reload(true);
                }).done();
            }else{
                swal("Ошибка", respond.msg, "error").done();
            }
        }).fail(function( jqXHR,error ){
            var text = error;
            if(jqXHR.responseJSON != undefined){
                if(typeof(jqXHR.responseJSON) != 'object'){
                    text = jqXHR.responseJSON.join('<br>');
                }
            }else{
                text = jqXHR.responseText;
            }
            swal("Ошибка", text, "error").done();
        });
        return false;
    });
    $('.setOtherInfo').click(function (event) {
        event.preventDefault();
        var _self = $(this),
            target = _self.data('target'),
            id = _self.data('id'),
            title = _self.data('title'),
            value = _self.data('value');
        if(target.length){
            var setIdOtherInfo = $(target).find('.setIdOtherInfo');
            var setTitleOtherInfo = $(target).find('.setTitleOtherInfo');
            var setValueOtherInfo = $(target).find('.setValueOtherInfo');
            if(setIdOtherInfo.length){
                setIdOtherInfo.val(id);
            }
            if(setTitleOtherInfo.length){
                setTitleOtherInfo.val(title);
            }
            if(setValueOtherInfo.length){
                setValueOtherInfo.val(value);
            }
        }
    });
    $('#updateOtherInfo').on('beforeValidate', function (event) {
        event.preventDefault();
        var _self = $(this);

        $.ajax({
            url: _self.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: _self.serializeArray()
        }).done(function( respond ){
            if(respond.code == 'success'){
                swal("Успешно", respond.msg, "success").then(function () {
                    window.location.reload(true);
                }).done();
            }else{
                swal("Ошибка", respond.msg, "error").done();
            }
        }).fail(function( jqXHR,error ){
            var text = error;
            if(jqXHR.responseJSON != undefined){
                if(typeof(jqXHR.responseJSON) != 'object'){
                    text = jqXHR.responseJSON.join('<br>');
                }
            }else{
                text = jqXHR.responseText;
            }
            swal("Ошибка", text, "error").done();
        });
        return false;
    });

    $('#main-menu-navigation .has-sub > a').on('click', function (ev) {
        ev.preventDefault();
    });

    $('.deleteClient').on('click',function (ev) {
        ev.preventDefault();
        var _self = $(this);
        let _text = "Вы хотите удалить клиента и все его данные!";
        if( !!parseInt(_self.data('income')) ) {
            _text += " У данного клиента сумма контрактов "+_self.data('income')+"руб. Его удаление может повлиять на статистику";
        }
        swal({
            title: 'Вы уверены?',
            text: _text,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Да',
            cancelButtonText: 'Нет'
        }).then(function (result) {
            if(result){
                $.ajax({
                    url: _self.attr('href'),
                    type: 'POST',
                    cache: false,
                    dataType: 'json',
                    data: {
                        id: _self.data('id')
                    }
                }).done(function( respond ){

                    if(respond.code == 'error'){
                        swal("Ошибка", respond.msg, "error").done();
                    }else if(respond.code == 'success'){
                        swal("Успешно", respond.msg, "success").then(function(value) {
                            switch (value) {
                                default:
                                    window.location.reload(true);
                            }
                        });
                    }
                }).fail(function( jqXHR, textStatus ){
                    swal("Ошибка", textStatus, "error").done();
                });
            }
        });
    });

    $('.searchClient').on('click', function (ev) {
        ev.preventDefault();
        var _self = $(this);
        $.ajax({
            url: '/client/search-client',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: _self.parents('form').serialize()
        }).done(function( respond ){

            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                if(respond.clients.length){
                    var access = false,
                        managers = respond.managers,
                        html = '<div class="row">';
                    $.each(respond.clients,function (index,el) {
                        if(el.access == true) {
                            access = true;
                            html += '<div class="col-12">';
                                html += '<div class="itemUser mb-3">';
                                    html += '<div class="row align-items-center">';
                                        html += '<div class="col-9"><div class="text-center">';
                                            if(el.firstname != '' && el.firstname != null){
                                                html += el.firstname;
                                            }
                                            if(el.fathername != '' && el.fathername != null){
                                                html += " "+el.fathername;
                                            }
                                            if(el.lastname != '' && el.lastname != null){
                                                html += " "+el.lastname;
                                            }
                                            if(el.org.title !== undefined && el.org.title != ''){
                                                html += "<b><br>Компания: "+el.org.title+'</b>';
                                            }
                                        html += '</div></div>';

                                        html += '<div class="col-3">';
                                            html += '<a class="btn btn-info px-1 btn-round btnSelectClient" data-id="'+el.id+'">Выбрать</a>';
                                        html += '</div>';
                                    html += '</div>';
                                html += '</div>';
                            html += '</div>';
                        }
                    });
                    html += '</div>';
                    if(!access){
                        swal("Нет прав", 'Имеют доступ: <br>'+managers, "warning").then(function(value) {});
                    }else{
                        $('#selectClient .modal-body').html(html);
                        $('#addNewRequest').modal('hide');
                        $('#selectClient').modal('show');
                        setTimeout(function () {
                            $('body').addClass('modal-open');
                        },500);

                    }
                }else{
                    swal("Не найдено", respond.msg, "warning").then(function(value) {});
                }
            }
        }).fail(function( jqXHR, textStatus ){
            swal("Ошибка", textStatus, "error").done();
        });
    });
    $('#formNewRequest .phoneMain,#formNewRequest .emailMain').on('change', function (ev) {
        ev.preventDefault();
        let _self = $(this);
        let el = '';
        if( _self.hasClass('phoneMain') ) {
            el = '.phoneOther';
        }
        else {
            el = '.emailOther';
        }
        if( _self.val() != '' ) {
            $(el).prop('disabled', false);
        }
        else {
            $(el).prop('disabled', true);
            $(el).val('');
        }
        $.ajax({
            url: '/client/search-client',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: _self.parents('form').serialize()
        }).done(function( respond ){

            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                if(respond.clients.length){
                    var access = false,
                        managers = respond.managers,
                        html = '<div class="row">';
                    $.each(respond.clients,function (index,el) {
                        if(el.access == true) {
                            access = true;
                            html += '<div class="col-12">';
                            html += '<div class="itemUser mb-3">';
                            html += '<div class="row align-items-center">';
                            html += '<div class="col-9"><div class="text-center">';
                            if(el.firstname != '' && el.firstname != null){
                                html += el.firstname;
                            }
                            if(el.fathername != '' && el.fathername != null){
                                html += " "+el.fathername;
                            }
                            if(el.lastname != '' && el.lastname != null){
                                html += " "+el.lastname;
                            }
                            if(el.org.title !== undefined && el.org.title != ''){
                                html += "<b><br>Компания: "+el.org.title+'</b>';
                            }
                            html += '</div></div>';

                            html += '<div class="col-3">';
                            html += '<a class="btn btn-info px-1 btn-round btnSelectClient" data-id="'+el.id+'">Выбрать</a>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        }
                    });
                    html += '</div>';
                    if(!access){
                        swal("Нет прав", 'Имеют доступ: <br>'+managers, "warning").then(function(value) {});
                    }else{
                        $('#selectClient .modal-body').html(html);
                        $('#addNewRequest').modal('hide');
                        $('#selectClient').modal('show');
                        setTimeout(function () {
                            $('body').addClass('modal-open');
                        },500);

                    }
                }else{
                    swal("Не найдено", respond.msg, "warning").then(function(value) {});
                }
            }
        }).fail(function( jqXHR, textStatus ){
            swal("Ошибка", textStatus, "error").done();
        });
    });
    $('body').on('click', '.btnSelectClient', function (ev) {
        ev.preventDefault();
        var _self = $(this);
        $.ajax({
            url: '/client/get-info-for-add-request',
            type: 'POST',
            cache: false,
            dataType: 'json',
            data: {id: _self.data('id')}
        }).done(function( respond ){
            if(respond.code == 'error'){
                swal("Ошибка", respond.msg, "error").done();
            }else if(respond.code == 'success'){
                $('#client-id').val(respond.client.id);
                $('#client-phone').val(respond.client.phone);
                $('#client-email').val(respond.client.email);
                $('#client-firstname').val(respond.client.firstname);
                $('#selectClient').modal('hide');
                $('#addNewRequest').modal('show');
                setTimeout(function () {
                    $('body').addClass('modal-open');
                },500);
            }
        });
    });

    $('#selectClient').on('hidden.bs.modal', function () {
        $('#addNewRequest').modal('show');
    });

    $('.filterItemManager').on('click', function (e) {
        e.preventDefault();
        if($('.filterItemManager.btn-success').data('user_id') !== $(this).data('user_id')){
            $('.filterItemManager.btn-success').removeClass('btn-success');
        }
        $(this).toggleClass('btn-success');
        var parent = $(this).parents('.filterItemsManagers');
        if(parent.length){
            var items = parent.find('.filterItemManager.btn-success');
            var table = $(this).parents('.card').find('table');
            if(table.length){
                if(items === undefined || items.length == 0){
                    table.find('tbody tr').show(1000);
                }else{
                    var user_ids = [];
                    items.each(function () {
                        user_ids.push($(this).data('user_id'));
                    });
                    table.each(function (ind, el) {
                        var trs = $(this).find('tbody tr');
                        trs.each(function () {
                            var id = $(this).data('user_id');
                            if($.inArray(id,user_ids) == -1){
                                $(this).hide(1000);
                            }else{
                                $(this).show(1000);
                            }
                        })
                    });
                }

            }
        }
        setTimeout(function(){change_total_income_filter()}, 1500);
    });

    $('.clearInfo').on('click', function () {
        $('#clientorganization-id').val('');
    });
    $('.baseInfo').on('click', function () {
        $('#clientorganization-id').val($('#clientorganization-id').data('value'));
    });
    $(document).on('click', '.list-group-item', function(ev){
        ev.preventDefault();
        var val = $(this).attr('href');
        $(this).parents('.twitter-typeahead').find('input').each(function () {
            $(this).val(val);
        });
    });
    $(document).on('click', '.docItemEdit', function(ev){
       $.ajax({
           url: '/request/edit-chet',
           type: 'GET',
           dataType: 'json',
           data: {id: $(this).data('pdf')}
       }).done(function (res) {
           console.log(res);
       })
    });

    $(document).on('submit', '#saveVin', function(ev){
        ev.preventDefault();
        $.ajax({
            url: '/request/save-vin',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize()
        }).done(function (res) {
            if(res.code == 'success'){
                $('#editVin').modal('hide');
                swal("Успешно", '', "success").then(function(value) {
                    $('#vinValue').text(res.value);

                });
            }else{
                swal("Ошибка", res.msg, "error").done();
            }
        }).fail(function( jqXHR, textStatus ){
            swal("Ошибка", textStatus, "error").done();
        });
    });

    $('.showTrend').on('click', function(e){
        e.preventDefault();
        $(this).toggleClass('active');
        if($(this).hasClass('active')){
            $(this).next().find('tbody').find('tr').each(function () {
                if($(this).data('trend') !== 'yes'){
                    $(this).hide();
                }
            });
        }else{
            $(this).next().find('tbody').find('tr').each(function () {
                $(this).show();
            });
        }
    });

    var reminders = getCookie('reminders');
    if( reminders != undefined)
    {
        var reminders_mass = phpUnserialize(reminders.substr(reminders.indexOf('a:')));
        if(!!reminders_mass[1])
        {
            reminders_times = JSON.parse(reminders_mass[1]);
            reminders_iterator = arrayKeys(reminders_times);
            setInterval(function ()
            {
                var time = Math.floor(Date.now() / 1000);
                reminders_iterator.forEach(function (val,index)
                {
                    if(val <= time && !reminders_pause)
                    {
                        reminders_pause = true;

                        var ids = reminders_times[val];
                        $.ajax({
                            url: '/reminder/load-ids',
                            type: 'POST',
                            dataType: 'json',
                            data: {ids: ids},
                        })
                        .done(function(res) {
                            if(res.code == 'success')
                            {
                                if(res.reminders.length)
                                {
                                    delete reminders_times[val];
                                    delete reminders_iterator[index];
                                    var reminders = res.reminders;
                                    for (var i = 0; i < reminders.length; i++)
                                    {
                                        if(reminders[i] !== undefined && reminders[i] !== null)
                                        {
                                            swal({
                                                title: reminders[i].title,
                                                text: reminders[i].text,
                                                type: "warning",
                                                buttons: true,
                                                confirmButtonText: '<span class="reminderID" data-id="'+reminders[i].id+'">Да</span>',
                                                dangerMode: true,
                                            }).then(function(willDelete)
                                            {
                                                if(willDelete)
                                                {
                                                    $.ajax({
                                                        url: '/reminder/set-status-executed',
                                                        type: 'POST',
                                                        dataType: 'json',
                                                        data: {id: $('.reminderID').data('id')},
                                                    });
                                                }
                                                if(reminders.length == i)
                                                {
                                                    reminders_pause = false;
                                                }
                                            });
                                        }
                                        else
                                        {
                                            reminders_pause = false;
                                        }
                                    }
                                }
                            }
                        });
                        return;


                    }
                });
                $('#navbarSupportedContent').data('time',time+5);
            }, 5000);
        }
    }

    $('body').on('click', '.closeNotification', function(){
       let _self = $(this),
            id = _self.data('id');
        swal({
            title: "Ваши действия",
            input:"textarea",
            inputPlaceholder:"Введите текст",
            type: "warning",
            buttons: true,
            closeOnConfirm: false,
            confirmButtonText: '<span class="notiID" data-id="'+id+'">Да</span>',
            dangerMode: true,
        }).then(function(willDelete)
        {
            if(!!willDelete) {
                $.ajax({
                    url: '/reminder/set-status-executed-notifications',
                    type: 'POST',
                    dataType: 'json',
                    data: {id: $('.notiID').data('id'), action: willDelete},
                });
            }

        });
    });

    $('.toggleActiveAlert').on('click', function(){
       $(this).parents('.alertItems') .toggleClass('active');
    });

    notifications = getCookie('notifications');
    if( notifications != undefined)
    {
        var notifications_mass = phpUnserialize(notifications.substr(notifications.indexOf('a:')));

        if(!!notifications_mass[1])
        {
            notifications_times = JSON.parse(notifications_mass[1]);
            notifications_iterator = arrayKeys(notifications_times);
            console.log(notifications_times);
            console.log(notifications_iterator);
            setInterval(function ()
            {
                var time = Math.floor(Date.now() / 1000);
                notifications_iterator.forEach(function (val,index)
                {
                    if(val <= time && !notifications_pause)
                    {

                        var ids = notifications_times[val];
                        $.ajax({
                            url: '/reminder/load-notification-ids',
                            type: 'POST',
                            dataType: 'json',
                            data: {ids: ids},
                        })
                        .done(function(res) {
                            if(res.code == 'success')
                            {
                                if(res.notifications.length)
                                {
                                    delete notifications_times[val];
                                    delete notifications_iterator[index];
                                    var notifications = res.notifications;
                                    for (var i = 0; i < notifications.length; i++)
                                    {
                                        if(notifications[i] !== undefined && notifications[i] !== null)
                                        {

                                            let title = "№"+notifications[i].request_id;
                                            let text = notifications[i].text;
                                            if( notifications[i].title != null ) {
                                                title += " - "+notifications[i].title;
                                            }

                                            $('.alertItems').append(
                                                "<div class=\"alert alert-info alert-dismissible fade show position-relative\" role=\"alert\">\n" +
                                                    title+" "+text+"\n" +
                                                "   <button type=\"button\" class=\"close closeNotification\" data-dismiss=\"alert\" aria-label=\"Close\" data-id='"+notifications[i].id+"'>\n" +
                                                "       <span aria-hidden=\"true\">×</span>\n" +
                                                "   </button>\n" +
                                                "</div>"
                                            );
                                        }
                                        else
                                        {
                                            notifications_pause = false;
                                        }
                                    }
                                }
                            }
                        });
                        return;


                    }
                });
                $('#navbarSupportedContent').data('time',time+5);
            }, 5000);
        }
    }

    $('body').on('click', '.reminderID', function()
    {
        reminderID = $(this).data('id');
    });

    $('body').on('click', '.notiID', function()
    {
        notiID = $(this).data('id');
    });

    if($.fn.Clipboard != undefined){
        var clipboard = new Clipboard('.photoItemCopy', {
            text: function(e) {
                return $(e).find('input[type=hidden]').val();
            }
        });

        clipboard.on('success', function(e) {
            e.clearSelection();
        });
    }
});

$(document).on('keyup', function(e) {
    if(e.which == 46) {
        if($('.checkboxDelete:checked').length){
            var deleteItems = [];
            $('.checkboxDelete:checked').each(function () {
                deleteItems.push($(this).val());
            });
            if(deleteItems.length){
                $.ajax({
                    url: '/request/delete-mass',
                    type: 'POST',
                    dataType: 'json',
                    data: {ids: deleteItems}
                }).done(function (respond) {
                    if(respond.code == 'success'){
                        swal("Успешно", respond.msg, "success").then(function () {
                            window.location.reload(true);
                        }).done();
                    }else{
                        swal("Ошибка", respond.msg, "error").done();
                    }
                })
            }
        }
    }
});

function change_total_income_filter()
{
    if( $('.totalIncomeFilter').length > 0 ) {
        let container_paste = $('.totalIncomeFilter span'),
            trs = container_paste.parents('table').find('tbody tr'),
            paste_summ = 0;
        if( trs.length > 0 ) {
            trs.each(function(index, item){
                if( $(item).is(':visible') ) {
                    paste_summ += $(item).data('amount');
                }
            });
            paste_summ = new Intl.NumberFormat('ru', {
                style: 'decimal',
                maximumFractionDigits: 2,
                minimumFractionDigits: 2 }).format(paste_summ);
        }
        else {
            paste_summ = new Intl.NumberFormat('ru', {
                style: 'decimal',
                maximumFractionDigits: 2,
                minimumFractionDigits: 2 }).format(paste_summ);
        }
        container_paste.text(paste_summ);
    }
}

function phoneMask(item) {
    if($.fn.mask !== undefined) {
        $(item).mask("+9 (999) 999-9999", {clearIfNotMatch: true, autoclear: 0});
    }
}
function afterAppend(clone) {
    var _phoneMask = $(clone).find('.phoneMask:not("[disabled]")');
    if(_phoneMask.length){
        phoneMask(_phoneMask);
    }
    var search = $(clone).find('.searchInput');
    if(search.length){
        search.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            source: engine.ttAdapter(),
            name: 'usersList',
            templates: {
                empty: [
                    //'<div class="list-group search-results-dropdown"><div class="list-group-item">Nothing found.</div></div>'
                ],
                header: [
                    '<div class="list-group search-results-dropdown">'
                ],
                suggestion: function (data) {
                    return '<a href="' + data + '" class="list-group-item">' + data + '</a>'
                }
            }
        });
    }
}

function arrayKeys(input) {
    var output = new Array();
    var counter = 0;
    for (i in input) {
        output[counter++] = i;
    }
    return output;
}

function getCookie(name) {

    var matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined
}

function setCookie(name, value, props) {

    props = props || {}

    var exp = props.expires

    if (typeof exp == "number" && exp) {

        var d = new Date();

        d.setTime(d.getTime() + exp*1000);

        exp = props.expires = d;

    }

    if(exp && exp.toUTCString) { props.expires = exp.toUTCString() }

    value = encodeURIComponent(value)

    var updatedCookie = name + "=" + value

    for(var propName in props){

        updatedCookie += "; " + propName

        var propValue = props[propName]

        if(propValue !== true){ updatedCookie += "=" + propValue }
    }

    document.cookie = updatedCookie

}

// удаляет cookie
function deleteCookie(name) {

    setCookie(name, null, { expires: -1 })

}

function getAllUrlParams(url) {

    var $_GET = {};
    var __GET = window.location.search.substring(1).split("&");
    for(var i=0; i<__GET.length; i++) {
        var getVar = __GET[i].split("=");
        $_GET[getVar[0]] = typeof(getVar[1])=="undefined" ? "" : getVar[1];
    }
    return $_GET;
}