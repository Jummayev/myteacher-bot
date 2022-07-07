<?php
	include 'helpers/db/DB.php';
	include 'helpers/DotEnv.php';

	(new DotEnv(__DIR__ . '/.env'))->load();

	/**
	 * Database connection
	 */
	$db = new DB(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv("DB_PASSWORD"), getenv("DB_DATABASE"));

	/**
	 * Send a request to Telegram
	 * @param $method
	 * @param array $data
	 * @return mixed|void
	 */
	function send($method, array $data=[]){
		$url = "https://api.telegram.org/bot".getenv('BOT_TOKEN')."/".$method;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		$res = curl_exec($ch);
		if(curl_error($ch))
			var_dump(curl_error($ch));
		else
			return json_decode($res);
	}

	/**
	 * @param int $chat_id
	 * @return void
	 */
	function typing(int $chat_id){
		send("sendChatAction",[
			"chat_id"=>$chat_id,
			"action"=>"typing",
		]);
	}

	$update = json_decode(file_get_contents('php://input'));
	$message = $update->message;
	$message_id = $message->message_id;
	$text = $message->text;
	$chat_id = $message->chat->id;
	$first_name = $message->chat->first_name;
	$last_name = $message->chat->last_name;
	$username = $message->chat->username;
	$step = file_get_contents("step/$chat_id.txt");

	typing($chat_id);


	$user = $db->query("SELECT * FROM users WHERE  telegram_id = ?", $chat_id)->fetchArray();
	if (!empty($user)){
		include "lang/".$user['lang']."/message.php";
		echo $message['start'];
	}else{
		$db->query("INSERT INTO users (telegram_id, first_name, last_name, username) VALUES(?, ?, ?, ?)", $chat_id, $first_name ?? "", $last_name ?? "", $username ?? "");
		switch ($text){
			case "/start" :
				send("sendMessage", [
					"chat_id" => $chat_id,
					'parse_mode'=>"html",
					"text" => "<b>Assalomu alaykum,</b> $first_name botimizdan foydalinishingiz uchun myteacher.uz hisobingizni botga bog'lashingiz kerak",
					"reply_markup"=>json_encode([
						'resize_keyboard'=>true,
						"inline_keyboard"=>[
							[
								["text"=>"Hisobni bog'lash","url"=>"https://myteacher.uz/login?telegram=$chat_id"],
							],[
								["text"=>"Yangi hisob yaratish","url"=>"https://myteacher.uz/register?telegram=$chat_id"],
							]
						],
						'remove_keyboard' => true,
					])
				]);
				break;
			case "/help" :
				file_put_contents("step/$chat_id.txt", "help");
				send("sendMessage", [
					"chat_id" => $chat_id,
					'parse_mode'=>"html",
					"text" => "Siz yuborgan xarbarni Administratorlaraga yuboraman sizga tez orada javob yozishadi admin: @".getenv("ADMIN_USERNAME"),
					"reply_markup"=>json_encode([
						'resize_keyboard'=>true,
						'keyboard'=>[
							[
								['text'=>"◀️Ortga"]
							]
						],
						'one_time_keyboard' => true,

					])
				]);
				break;
			case "◀️Ortga" :
				file_put_contents("step/$chat_id.txt", "start");
				send("sendMessage", [
					"chat_id" => $chat_id,
					'parse_mode'=>"html",
					"text" => "Bosh sahifa",
					'reply_markup' => json_encode([
						'remove_keyboard' => true,
					]),
				]);
				break;
		}
		if ($step == "help" || $step == "send" and $text != "◀️Ortga" and $text != "/help" and $text != "/start"){
			file_put_contents("step/$chat_id.txt", "send");
			send("sendMessage", [
				"chat_id" => $chat_id,
				'parse_mode'=>"html",
				"text" => "tez orada javob beramiz admin: @".getenv("ADMIN_USERNAME"),
			]);
			send("forwardMessage", [
				"chat_id" => getenv("ADMIN_ID"),
				"from_chat_id" => $chat_id,
				"message_id" => $message_id,
				'disable_notification' => true
			]);
		}
	}