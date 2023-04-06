<?php

function setConversation($convsFile = '')
{
    if (empty($convsFile)) exit('请选择一个会话');
    $filePath = ROOT_PATH.'/converse/'.$convsFile. '.txt';

    if (!file_exists($filePath)) exit('会话不存在');
}

function createConversation($convsFile = '')
{   
    if (empty($convsFile)) {
        global $conservationName;
        $convsFile = getConversationName();
        $conservationName = $convsFile;
    } 
    $filePath = ROOT_PATH.'/converse/'.$convsFile. '.txt';

    if (!file_exists($filePath)) {
        file_put_contents($filePath, serialize([]));
    }
}

function readFileList()
{
    $files = [];
    $file_list = scandir(ROOT_PATH.'/converse');
    foreach ($file_list as $k => $value) {
        if ($value != '.' && $value != '..') {
            $filename = str_replace('.txt', '', $value);
            $arr = [
                'file_name'     => $filename,
                'create_time'   => filectime(ROOT_PATH.'/converse/'.$filename. '.txt'),
            ];
            array_push($files, $arr);
        }
    }
    return $files;
}

function getConversationName()
{
    return date('Y-m-d',time()) .'-'. uniqid();
}

function manual()
{   
    echo PHP_EOL;
    echo '  使用: php chat.php [create][select][history][help]'.PHP_EOL;
    echo PHP_EOL;
    echo '  create    创建一个新的会话'.PHP_EOL;
    echo '  select    选择会话'.PHP_EOL;
    echo '  history   查看历史会话'.PHP_EOL;
    echo '  help      查看帮助'.PHP_EOL;
    exit();
}

function helpInfo()
{
    exit('查看帮助 php chat.php help');
}

function saveChat($convsFile, $data)
{
    $filePath = ROOT_PATH.'/converse/'.$convsFile. '.txt';

    $messagesRecord = unserialize(file_get_contents($filePath));
    array_push($messagesRecord, $data);

    file_put_contents($filePath, serialize($messagesRecord));
}

function send($content)
{
    global $config;
    global $conservationName;

    $userMsg = [];
    $url = 'https://api.openai.com/v1/chat/completions';

    $filePath = ROOT_PATH.'/converse/'.$conservationName. '.txt';
    $messagesRecord = unserialize(file_get_contents($filePath));

    foreach ($messagesRecord as $key => $value) {
        $temparr = [
            'role'      => 'user',
            'content'   => $value['user_content']
        ];
        array_push($userMsg, $temparr);                        
    }
    $item = [
        'role'      => 'user',
        'content'   => $content
    ];
    array_push($userMsg, $item);

    $param = [
        'model'     => $config['gpt_model'],
        'messages'  => $userMsg
    ];

    $header = [
        'Content-Type:application/json',
        'Authorization:Bearer '. $config['key'],
    ];

    $ret = requestUrl($url, json_encode($param, JSON_UNESCAPED_UNICODE), $header);
    return json_decode($ret);
}

function requestUrl($url, $param = '', $header = [])
{
    global $config;
    //初始化curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    curl_setopt($ch, CURLOPT_PROXY, $config['proxy_host']); //代理服务器地址
    curl_setopt($ch, CURLOPT_PROXYPORT, $config['proxy_port']); //代理服务器端口

    //post提交方式
    if ($param) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);  
    }
    //终止从服务端进行验证
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    $data = curl_exec($ch); 

    if ($data == false) {
        echo curl_error($ch); exit();
    }
    curl_close($ch);

    return $data;
}