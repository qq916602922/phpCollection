<?php

/**
 * websocket服务器端程序
 * */

//require "一个dispatcher，用来将处理转发业务实现群组或者私聊";
require "/var/www/html/swoole/wschat/dispatcher.php";

$server = new swoole_websocket_server("0.0.0.0", 22223);

$server->on("open", function ($server, $request) {
    echo "client {$request->fd} connected, remote address: {$request->server['remote_addr']}:{$request->server['remote_port']}\n";
    $welcomemsg = "Welcome {$request->fd} joined this chat room.";

    foreach ($server->connections as $key => $fd) {
        $server->push($fd, $welcomemsg);
    }
});

$server->on("message", function ($server, $frame) {
    $dispatcher = new Dispatcher($frame);
    $chatdata = $dispatcher->parseChatData();
    $isprivatechat = $dispatcher->isPrivateChat();
    $fromid = $dispatcher->getSenderId();
    if ($isprivatechat) {
        $toid = $dispatcher->getReceiverId();
        $msg = "【{$fromid}】对【{$toid}】说：{$chatdata['chatmsg']}";
        $dispatcher->sendPrivateChat($server, $toid, $msg);
    } else {
        $msg = "【{$fromid}】对大家说：{$chatdata['chatmsg']}";
        $dispatcher->sendPublicChat($server, $msg);
    }

});

$server->on("close", function ($server, $fd) {
    $goodbyemsg = "Client {$fd} leave this chat room.";


    foreach ($server->connections as $key => $clientfd) {
        $server->push($clientfd, $goodbyemsg);
    }
});

$server->start();
