function add_station() {
    var timezones = '<select class="timezones" name="timezones[]">';
    $("select#timezone option").each(function(){
        timezones += '<option value="'+$(this).val()+'"';
        if ($(this).val()=='17') timezones += ' selected';
        timezones += '>'+$(this).text()+'</option>';
    });
    timezones += '</select>';
    $("a.add_more").remove();
    var add_more = '<a href="javascript:void(0)" onclick="add_station()" class="add_more">Добавить еще</a>';
    $("#names").append('<div class="record_separator"><hr/></div><input class="names" type="text" value="" name="names[]"><div class="field_separator"></div>'+timezones+'<div class="field_separator"></div><input class="names" type="text" value="" name="notes[]">'+add_more);
}
var search_length = 0;
$(document).ready(function(){
    $("#station_search").keyup(function(event){
        var chr = $(this).val();
        var module = '';
        if (document.location.href.indexOf('admin') > -1) module = 'admin';        
        if ((event.which==0 || event.which==8 || event.which==32 || event.which==46 
        || (event.which>=48 && event.which<=57) || (event.which>=65 && event.which<=90) || event.which==173 
        || ('хъжэбю-'.indexOf(chr.slice(-1)) != -1 && chr.length > search_length)) && chr.length != search_length) {
            search_length = chr.length;
            $.post("/station/search", {q: $.trim(chr), filter: 1}, function(data) {
                $("div.table table").html('');
                $(".pagination").html('');
                if (data=='\n') {
                    row = '<tr><td class="no_results" style="width: 290px;">Результаты не найдены</td></tr>';
                    $("div.table table").append(row);
                } else {
                    var rows = data.split('\n');
                    for (var key in rows) {
                        if (key != 0 && rows[key] != '') {
                            var values = rows[key].split('|');
                            row = '<tr>';
                            row += '<td width="30">'+Number(key)+'</td>';
                            row += '<td class="name-cell">'+values[1]+'</td>';
                            row += '<td class="actions"><a href="station/'+values[0]+'"><img src="/images/admin_view.png" alt="Просмотр" title="Просмотр"/></a>';
                            if (module=='admin') {
                                row += '<a href="station/edit/'+values[0]+'"><img src="/images/admin_edit.png" alt="Редактировать" title="Редактировать"/></a>';
                                row += '<a class="del_link" href="station/delete/'+values[0]+'"><img src="/images/admin_delete.png" alt="Удалить" title="Удалить"/></a>';
                            }
                            row += '</td></tr>';
                            $("div.table table").append(row);
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
                        q = encodeURIComponent($.trim($("#station_search").val()));
                        module += '/';
                        if (current > 1) {
                            if (start > 1) $(".pagination").append('<a href="/'+module+'station/?page=1&q='+q+'"><<</a>');
                            $(".pagination").append('<a href="/'+module+'station/?page='+(current-1)+'&q='+q+'"><</a>');
                        }
                        for (p = start; p <= end; p++) {
                            if (p == current) $(".pagination").append('<span>'+p+'</span>');
                            else $(".pagination").append('<a href="/'+module+'station/?page='+p+'&q='+q+'">'+p+'</a>');
                        }
                        if (current < total) {
                            $(".pagination").append('<a href="/'+module+'station/?page='+(current+1)+'&q='+q+'">></a>');
                            if (start < (total - (max - 1))) $(".pagination").append('<a href="/'+module+'station/?page='+total+'&q='+q+'">>></a>');
                        }
                    }
                }
            });
        }
    });
});