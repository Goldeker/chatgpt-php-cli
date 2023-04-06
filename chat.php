<?php

$config = include_once './src/config.php';

$conservationName = '';
include_once './src/common.php';

define('ROOT_PATH', __dir__);
$args = $_SERVER['argv'];
$argsCount = count($args);

switch ($args[1]) {

	case 'history':
		$historys = readFileList();
		if ($historys) echo PHP_EOL;
		foreach ($historys as $key => $value) { 
			echo $value['file_name']. PHP_EOL;
		}
		exit();
		break;

	case 'select':
		$conservationName = $args[2];
		setConversation($conservationName);
		break;

	case 'create':
		$conservationName = $args[2];
		createConversation($conservationName);
		break;

	case 'help':
		manual();
		break;

	default:
		helpInfo();
		break;
}

while (true) {

	echo '>>: ';
	sapi_windows_cp_set(936); //设置GBK
	$content = trim(fgets(STDIN));

	if ($content == 'exit') {
		exit('Bye..');
	} else {
		sapi_windows_cp_set(65001); //设置utf8

		$current_encode = mb_detect_encoding($content, ['ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8']);
		$content = mb_convert_encoding($content, 'UTF-8', $current_encode);

		$result = send($content);

		if ($result->error) {
			echo 'error: '. $result->error->message .PHP_EOL;
		} else {
			$botName = $result->choices[0]->message->role;
			$botResponse = $result->choices[0]->message->content;

			echo $botName .': '. $botResponse . PHP_EOL;

			$tempArr = [
				'user_content'		=> $content,
				'bot_content'		=> $botResponse,
			];
			saveChat($conservationName, $tempArr);
		}
	}
}

