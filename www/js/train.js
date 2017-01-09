function add_stop(timezones) {
    var new_stop = '<tr><td><a href="javascript:void(0)" onclick="delete_stop(this)" class="stop_delete"></a></td><td class="dragHandle"> </td>';
    new_stop += '<td><input class="station_input" type="text" value="" name="stops[]">';
    new_stop += '<span class="add_time_label">Временная зона</span>'+timezones;
    new_stop += '<span class="add_time_label">Прибытие</span><input type="text" name="arrival[]"/>';
    new_stop += '<span class="add_time_label">Отправление</span><input type="text" name="departure[]"/>';
    new_stop += '<div class="clear stops_delimiter"></div></td></tr>';
    $("#stop_rows").append(new_stop);
    set_auto();
    $("#stop_rows").tableDnD({
      onDragClass: "myDragClass",
      dragHandle: "dragHandle"
    });    
}
function set_auto() {
    $(".station_input").autocomplete("/station/search", {
        delay:10,
        minChars:2,
        matchSubset:1,
        autoFill:true,
        matchContains:1,
        cacheLength:10,
        selectOnly:true,
        maxItemsToShow:10
    });
}
function delete_stop(link) {
    var row = $(link).parents('tr');
    $(row).remove();
}

var search_length = 0;
var route_from_label;
var route_to_label;
var module;
$(document).ready(function(){
    if ($("div.all_stops > ul.errors").length > 0) {
        $("div.all_stops > ul.errors").insertAfter("dd#time_from_block > input#time_from");
    }
    if ($(".station_input").length > 0) set_auto();
    $("#train_search").keyup(function(event){
        module = '';
        if (document.location.href.indexOf('admin') > -1) module = 'admin';        
        var chr = $(this).val();
        if ((event.which==0 || event.which==8 || event.which==32 || event.which==46 
        || (event.which>=48 && event.which<=57) || (event.which>=65 && event.which<=90) || event.which==173 
        || ('хъжэбю-'.indexOf(chr.slice(-1)) != -1 && chr.length > search_length)) && chr.length != search_length) {
            search_length = chr.length;
            $.post("/train/search", {q: $.trim(chr), filter: 1}, function(data) {
                $("div.table table").html('');
                $(".pagination").html('');
                if (data=='\n') {
                    row = '<tr><td class="no_results" style="width: 570px;">Результаты не найдены</td></tr>';
                    $("div.table table").append(row);
                } else {
                    var rows = data.split('\n');
                    for (var key in rows) {
                        if (key != 0 && rows[key] != '') {
                            var values = rows[key].split('|');
                            row = '<tr>';
                            row += '<td width="30">'+Number(key)+'</td>';
                            row += '<td class="name-cell">'+values[1]+'</td>';
                            row += '<td class="name-cell">'+values[2]+'</td>';
                            row += '<td class="name-cell station-cell"><a href="station/'+values[3]+'">'+values[4]+'</a></td>';
                            row += '<td class="name-cell station-cell"><a href="station/'+values[6]+'">'+values[7]+'</a></td>';
                            row += '<td class="name-cell">'+values[5]+'</td>';
                            row += '<td class="name-cell">'+values[8]+'</td>';
                            row += '<td class="actions"><a href="train/'+values[0]+'"><img src="/images/admin_view.png" alt="Просмотр" title="Просмотр"/></a>';
                            if (module=='admin') {
                                row += '<a href="train/edit/'+values[0]+'"><img src="/images/admin_edit.png" alt="Редактировать" title="Редактировать"/></a>';
                                row += '<a class="del_link" href="train/delete/'+values[0]+'"><img src="/images/admin_delete.png" alt="Удалить" title="Удалить"/></a>';
                            }
                            row += '</td></tr>';
                            $("div.table table").append(row);
                            if (module=='admin') {
                                $("a.del_link").click(function(){
                                    if (confirm('Вы увурены что хотите выполнить удаление?')) return true;
                                    else return false;
                                });
                            }
                        }
                    }
                    if (rows[0] != '') {
                        var pagination = rows[0].split(',');
                        current = 1;
                        total = Number(pagination[0]);
                        max = Number(pagination[2]);
                        if (total > max && current > Math.ceil(max/2)) {
                            start = current - Math.floor(max/2);
                            if (start > (total - (max - 1))) start = total - (max - 1);
                            end = start + (max - 1);
                            if (end > total) end = total;
                        } else if (total < max) {
                            start = 1;
                            end = total;
                        } else {
                            start = 1;
                            end = max;
                        }
                        q = encodeURIComponent($.trim($("#train_search").val()));
                        module += '/';
                        if (current > 1) {
                            if (start > 1) $(".pagination").append('<a href="/'+module+'train/?page=1&q='+q+'"><<</a>');
                            $(".pagination").append('<a href="/'+module+'train/?page='+(current-1)+'&q='+q+'"><</a>');
                        }
                        for (p = start; p <= end; p++) {
                            if (p == current) $(".pagination").append('<span>'+p+'</span>');
                            else $(".pagination").append('<a href="/'+module+'train/?page='+p+'&q='+q+'">'+p+'</a>');
                        }
                        if (current < total) {
                            $(".pagination").append('<a href="/'+module+'train/?page='+(current+1)+'&q='+q+'">></a>');
                            if (start < (total - (max - 1))) $(".pagination").append('<a href="/'+module+'train/?page='+total+'&q='+q+'">>></a>');
                        }
                    }
                }
            });
        }
    });
    route_from_label = $("#route_from").val();
    route_to_label = $("#route_to").val();
    $("#route_search").submit(function(){
        var st_from = $.trim($("#route_from").val());
        var st_to = $.trim($("#route_to").val());
        if (st_from != '' && st_to != '' && st_from != route_from_label && st_to != route_to_label) {
            $.post("/train/route", {st_from: st_from, st_to: st_to}, function(data) {
                $("div.table table").html('');
                module = data[0];
                if (module=='default') module = '';
                else module += '/';
                data = data[1];
                if (data.length==0) {
                    row = '<tr><td class="no_results" style="width:410px">Результаты не найдены</td></tr>';
                    $("div.table table").append(row);
                } else {
                    for (var key in data) {
                        row = '<tr>';
                        row += '<td width="30">'+Number(key+1)+'</td>';
                        row += '<td class="name-cell">'+data[key]['info']['number']+'</td>';
                        row += '<td class="name-cell">'+data[key]['info']['period']+'</td>';
                        row += '<td class="name-cell station-cell"><a href="/'+module+'station/'+data[key]['info']['station_from_id']+'">'+data[key]['info']['station_from_name']+'</a></td>';
                        row += '<td class="name-cell station-cell"><a href="/'+module+'station/'+data[key]['info']['station_to_id']+'">'+data[key]['info']['station_to_name']+'</a></td>';
                        row += '<td class="name-cell">'+data[key]['departure']+'</td>';
                        row += '<td class="name-cell">'+data[key]['arrival']+'</td>';
                        row += '<td class="actions"><a href="/'+module+'train/'+data[key]['id']+'"><img src="/images/admin_view.png" alt="Просмотр" title="Просмотр"/></a>';
                        if (module=='admin/') {
                            row += '<a href="/'+module+'train/edit/'+data[key]['id']+'"><img src="/images/admin_edit.png" alt="Редактировать" title="Редактировать"/></a>';
                            row += '<a class="del_link" href="/'+module+'train/delete/'+data[key]['id']+'"><img src="/images/admin_delete.png" alt="Удалить" title="Удалить"/></a>';
                        }
                        row += '</td></tr>';
                        $("div.table table").append(row);
                        if (module=='admin/') {
                            $("a.del_link").click(function(){
                                if (confirm('Вы увурены что хотите выполнить удаление?')) return true;
                                else return false;
                            });
                        }
                    }
                }
            }, "json");
        }
        return false;
    });
    $("#route_search input[type='text']").focus(function(){
        if ($(this).attr('id')=='route_from') default_val = route_from_label;
        else default_val = route_to_label;
        if ($(this).val()==default_val) {
            $(this).val('');
            $(this).addClass('edit');
        }
    });
    $("#route_search input[type='text']").blur(function(){
        if ($(this).attr('id')=='route_from') default_val = route_from_label;
        else default_val = route_to_label;
        if ($(this).val()=='') {
            $(this).val(default_val);
            $(this).removeClass('edit');
        }
    });
});