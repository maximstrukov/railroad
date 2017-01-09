$(document).ready(function(){
    var window_width = $(window).width();
    
    if (window_width < 1000) {
        $('.page').css('width',String(window_width)+'px');
        $('#admin-header, #header-menu, .mainbar, .footer').css('width',String(Number(window_width)-20)+'px');
    }
    var window_height = $(window).height();
    var page_height = $("div.page").height();
    if ($.browser.webkit && page_height > window_height) {
        var footer_height = $("div.footer").height() + 'px';
        $("div.page").css('padding-bottom',footer_height);
    }
    $("a.del_link").click(function(){
        if (confirm('Вы увурены что хотите выполнить удаление?')) return true;
        else return false;
    });
});
function adjust_map() {
    var block = $("#map_canvas");
    var w_height = $(window).height();
    var w_width = $(window).width();
    var alerts_left = (w_width - $(block).width())/2;
    var alerts_top = (w_height - $(block).height())/2;
    if (alerts_top<25) alerts_top = 25;
    $(block).css("left",alerts_left+"px");
    $(block).css("top",alerts_top+"px");
    if (w_width < 1000) {
        alerts_left = alerts_left + 16;
        alerts_top = alerts_top - 16;
    }
    $("a.close_map").css("left",alerts_left-16+"px");
    $("a.close_map").css("top",alerts_top-16+"px");
}