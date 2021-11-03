
//блок для вывода информации на экран
function message(text) {
	//выводим в chat-result текст сообщения
	jQuery('#chat-result').append(text);
}



jQuery(document).ready(function($) {

	//Открываем соединение с сервером
	/* У сокета есть 4 коллбека (описаны ниже) */
	var socket = new WebSocket("ws://chat:8090/server.php");

	//Событие при соединении с сервером
	socket.onopen = function() {
		message("<div>[open] Соединение установлено.</div>");
	};

	//Событие срабатывает при ошибке сщединения с сервером
	socket.onerror = function(error) {
		message("<div>[error] Ошибка соединения с сервером. " +  (event.code ? "Код = " + event.code + " " + error.massage : "") + "</div>");
	};

	//Событие срабатывает при закрытии соединения
	socket.onclose = function() {
		if (event.wasClean) {
			//если соединение закрытщ чисто : 
			message(`<div>[close] Соединение закрыто. Код = ${event.code} причина = ${event.reason}</div>`);
		} else {
			// например, сервер убил процесс или сеть недоступна
			// обычно в этом случае event.code 1006
			message(`[close] Соединение прервано. Код = ${event.code}`);
		}
	};

	//Событие обрабатывает Сообщение
	socket.onmessage = function(event) {
		// Получаем данные в формате JSON и декодируем
		var data = JSON.parse(event.data);
		message("<div>" + data.type + " - " + data.message + "</div>");
	};

	// Обработчик отправки сообщения через форму
	$("#submit").on('click', function() {
			var message = {
				chat_message:$("#chat-message").val(),
				chat_user:$("#chat-user").val(),
			};

			// Если пользователь уже вввел имя, то нет необходимости выводить поле "name" снова
			$("#chat-user").attr("type","hidden");

			// Очистим поле message от предыдущего значения
			$("#chat-message").val("");

			socket.send(JSON.stringify(message));
			return false;
	});

});