/* Клиент чата */

// Блок для вывода информации на экран
function message(text) {
	text = '<div>' + text + '</div>';
	//выводим в chat-result текст сообщения 
	jQuery('#chat-result').append(text);
}


jQuery(document).ready(function($) {

	// Открываем соединение с сервером
	/* У сокета есть 4 коллбека (описаны ниже) */
	let socket = new WebSocket("ws://192.168.2.165:8090/server.php");

	// Событие при соединении с сервером
	socket.onopen = function() {
		message("[open] Соединение установлено.");
	};

	// Событие срабатывает при ошибке соединения с сервером
	socket.onerror = function(error) {
		message("[error] Ошибка соединения с сервером. " +  (event.code ? "Код = " + event.code + " " + error.massage : ""));
	};

	// Событие срабатывает при закрытии соединения
	socket.onclose = function() {
		if (event.wasClean) {
			//если соединение закрыто чисто : 
			message(`[close] Соединение закрыто. Код = ${event.code} причина = ${event.reason}`);
		} else {
			// например, сервер убил процесс или сеть недоступна(когда TCP протокол вернет ошибку)
			// обычно в этом случае event.code 1006
			message(`[close] Соединение прервано. Код = ${event.code}`);
		}
	};

	//Событие обрабатывает Сообщение
	socket.onmessage = function(event) {
		// Получаем данные в формате JSON и декодируем
		let data = JSON.parse(event.data);
		message(data.message);
	};

	// Обработчик отправки сообщения через форму
	$("#submit").on('click', function() {
			let message = {
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


	// Проверка соединения с сервером
	const func = () => {
		let pingMessage = {
			chat_message: ping,
			char_user:$('#chat-user').val()
		};
		socket.send(JSON.stringify(pingMessage));
	}

	setInterval(func, 1000);
});