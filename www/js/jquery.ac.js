// ----------------------------------------------------------------------------
// autoComplete, JQuery plugin
// v 1.0
// ----------------------------------------------------------------------------
// Copyright (C) 2010 recens
// http://recens.ru/jquery/plugin_auto_complete.html
// ----------------------------------------------------------------------------
jQuery.fn.ac = function(o) { // объявление функции
	var o = $.extend({     // параметры по умолчанию
		url:'/station/search',  // URL для поиска слов
		onClose:function(suggest) {  // функция, которая срабатывает при закрытии окна с подсказками
			setTimeout(function(){
				suggest.slideUp('fast'); // плавно закрывает окно
			}, 100); // через 100 мс
		},
		dataSend:function(input) {  // функция, возвращающая данные для отправки на сервер
			return 'suggest_name=' + input.attr('name') + '&query=ac&word=' + word;
		},
		wordClick:function(input,link) { // функция, которая срабатывает при добавлении слова в input
			input.val(link.attr('href')).focus();
		}
	}, o);

	return $(this).each(function(){ // каждое поле для ввода
		var onClose = o.onClose;
		var input = $(this); // присваиваем переменной input
		input.after('<div class="auto-suggest"></div>'); // после него вставляем блок для подсказок
		var suggest = input.next(); // присваиваем его переменной
		suggest.width(input.width() + 6); // выставляем для него ширину
		input.blur(function(){ // когда input не в фокусе
			if (suggest.is(':visible'))  {  // если подсказки не скрыты
				input.focus(); // фокусируемся на input'e
				onClose(suggest); // и скрываем подсказки
			}
		}).keydown(function(e) {  // при нажатии клавиши
			if (e.keyCode == 38 || e.keyCode == 40) { // если эта клавиша вверх или вниз
	   			var tag = suggest.children('a.selected'),  // находим выделенный пункт
	   			new_tag = suggest.children('a:first'); // и первый в списке
	   			if (tag.length) { // если выделение существует
	   			   	if (e.keyCode == 38) { // нажата клавиша вверх
	   			   		if (suggest.children('a:first.selected').length) {  // и выделен первый пункт
		                	new_tag = suggest.children('a:last'); // выделяем последний
		   				} else {  // иначе
		   					new_tag = tag.prev('a');  // выделяем предыдущий
		   				}
		   			} else { // иначе
		   				if (!suggest.children('a:last.selected').length) new_tag = tag.next('a'); // выделяем следующий
		   			}
		   			tag.removeClass('selected'); // снимаем выделение со старого пункта
		    	}
		    	new_tag.addClass('selected');   // добавляем класс выделения
	            input.val(new_tag.attr('href')); // заменяем слово в поле ввода
		    	return;
			}
			if (e.keyCode == 13 || e.keyCode == 27) {   // если нажата клавиша Enter или Esc
	   			onClose(suggest); // закрываем окно
		    	return;
			}
		}).keyup(function(e) {
	       	if (e.keyCode == 38 || e.keyCode == 40 || e.keyCode == 13 || e.keyCode == 27) return; // если нажата одна из вышеперечисленных клавиш, выходим
			word = input.val(); // добавляем переменную со значением поля ввода
			if (word) { // если переменная не пуста
				$.post(o.url, o.dataSend(input), function(data){  // отправляем запрос
					if (data.length > 0) { // если есть список подходящих слов
						suggest.html(data).show().children('a').click(function(){ // функция, срабатывающая при нажатии на слово
							o.wordClick(input,$(this)); // пользовательская функция, объявленная выше
							return false;
						});
					} else {  // если нет
						onClose(suggest); // закрываем окно
					}
				});
			} else { // если переменная пуста
	    		onClose(suggest); // закрываем окно
			}
		});
	});
}