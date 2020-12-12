<?php
// @irosource  https://t.me/irosource

if (file_exists('MadelineProto.log')) { unlink ("MadelineProto.log");}
if (!file_exists ("data/link.txt")){ file_put_contents("data/link.txt","");}
if (!file_exists ("data/admin.txt")){ file_put_contents("data/admin.txt","415299577");}

if (!is_dir("data")){ mkdir ("data");}
if (!file_exists ("data/number.txt")){ file_put_contents("data/number.txt","0");}
if (!file_exists ("data/join.txt")){ file_put_contents("data/join.txt","off");}
if (!file_exists ("data/timer.txt")){ file_put_contents("data/timer.txt","0");}

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';


function is_english($str) {
	return strlen($str) === mb_strlen($str,'utf-8');
}


class EventHandler extends \danog\MadelineProto\EventHandler
{
    public function __construct($MadelineProto)
    {
        parent::__construct($MadelineProto);
    }
    public function onUpdateSomethingElse($update)
    {
        if (isset($update['_'])) {
            if ($update['_'] == 'updateNewMessage')  onUpdateNewMessage($update);
            else if ($update['_'] == 'updateNewChannelMessage') onUpdateNewChannelMessage($update);
        }
    }
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['out']) && $update['message']['out']) {
            return;
        }

        $randi  = rand(100, 1600);
        $timer  = file_get_contents("data/timer.txt");
        $number = file_get_contents("data/number.txt");
        $join   = file_get_contents("data/join.txt");
        $admin  = "#ADMIN#";
        $msg    = $update['message']['message']?? '';
        $msg_id = $update['message']['id'];
        $userID  = isset($update['message']['from_id']) ? $update['message']['from_id'] : '';
        $info    = yield $this->getInfo($update);
        $chatID  = $info['bot_api_id'];
        @$mg     = round(memory_get_usage() / 1024 / 1024, 1);
        $replyID = $update['message']['reply_to_msg_id']?? null;
        try {
            date_default_timezone_set('Asia/Tehran');
            $admin_list = file_get_contents("data/admin.txt");
            $exp = explode("\n", $admin_list);
            if (in_array($userID, $exp)) {
                if (strpos($msg, "http://t.me/joinchat/")         !== false ||
                    strpos($msg, "https://t.me/joinchat/")        !== false ||
                    strpos($msg, "https://telegram.me/joinchat/") !== false ||
                    strpos($msg, "http://telegram.me/joinchat/")  !== false  )
                {
                    $link = explode("https://t.me/joinchat/", $msg);
                    $link_count = 0;
                    $linkk = file_get_contents("data/link.txt");
                    $link3 = explode(',', $linkk);

                    foreach ($link as $link2) {
                        $pm = "https://t.me/joinchat/" . $link2;
                        $msg1 = explode("https://t.me/joinchat/", $pm)[1];
                        $msg2 = explode("\n", "$msg1")[0];
                        if (is_english($msg2) == true) {
                            $msg5 = "https://t.me/joinchat/$msg2";
                            if (!in_array($msg5, $link3)) {
                                $link_count++;
                                $myfile2 = fopen("data/link.txt", "a") or die("Unable to open file!");
                                fwrite($myfile2, "https://t.me/joinchat/$msg2,");
                                fclose($myfile2);
                            } else {
                                continue;
                            }
                        }
                    }
                    yield $this->messages->sendMessage([
                        'peer' => $chatID,
                        'reply_to_msg_id' => $msg_id,
                        'message'         =>
                            "<br>" .
                            "تعداد<br>".
                            "$link_count <br>".
                            "لینک با موفقیت ذخیره شد و در اولین فرصت جوین میشوم<br>".
                            "اگر تعداد لینک های شما از تعداد شمارش من کمتر است احتمالا ان لینک ها قبلا ذخیره شده بودند<br>".
                            "——————",
                    ' ' => 'html'
                    ]);
                }


                if ($msg == "امار") {
                    $linkk = file_get_contents("data/link.txt");
                    $link3 = explode(',', $linkk);
                    $colink = count($link3) - 1;
                    $co1 = $colink - $number;
                    $allpv = 0;
                    $allchannel = 0;
                    $allgroup = 0;
                    $dialogs = yield $this->get_dialogs();
                    foreach ($dialogs as $k => $v) {
                        if ($v["_"] == "peerUser") {
                            $allpv++;
                        }
                        if ($v["_"] == "peerChat") {
                            $allgroup++;
                        }
                        if ($v["_"] == "peerChannel") {
                            $allchannel++;
                        }
                    }
                    $all = $allpv + $allgroup + $allchannel;
                    yield $this->messages->sendMessage([
                        'peer'            => $chatID,
                        'reply_to_msg_id' => $msg_id,
                        'message'         => "
⚠️ stats 🔐 Stats For Tabchi • GRT •
 <> ••••••••••••••••••••••••• <>
-💁‍♂ allStats : $all
-🙇‍♂ allpv : $allpv
-👥 allgroup : $allgroup
-🆑 all channel and supergroup : $allchannel
 <> ••••••••••••••••••••••••• <>
-🔻 count all link : $colink
-▫️ count link for join : $co1
-🔈 count link joined : $number
-⏱ time for join after :  $randi  second
-♻️ Memry using :  $mg  Ms
 <> ••••••••••••••••••••••••• <>
-🔸 join auto : $join
-🔹 stats tabchi : on
 <> ••••••••••••••••••••••••• <>
-👨‍💻 Creator : @IroSource
",
                        'parse_mode' => 'markdown',
                    ]);
                }

                if ($msg == "راهنما") {
                    yield $this->messages->sendMessage([
                        'peer'            => $chatID,
                        'reply_to_msg_id' => $msg_id,
                        'message'         =>
"
🔎 راهنمای تبچی GRT :
v : 2.0

☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
📊 وضعیت ربات :
 [ ping ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
📚 آمار ربات :
[ stats ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
👨‍💻 اطلاعات ادمین ربات :
 [ whois ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
📈 لیست لینک ها :
 [ listlink ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
❇️ خاموش کردن جوین :
 [ join off ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
❇️ روشن کردن جوین :
 [ join on ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
↗️ فروارد به گروه ها :
[ f2gps ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
↗️ فروارد به سوپر گروه ها :
 [ f2sgps ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
↗️ فروارد به کاربران :
[ f2pv ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
↗️ فروارد به همه گروه ها و کاربران :
[ f2all ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
🔸 افزودن ادمین :
[ setsudo ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
❌ حذف ادمین :
[ remsudo ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
❌ حذف همه ادمین ها :
[ cleansudo ]
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
🔆 لیست ادمین ها :
sudolist
☆<>•••••••••••••••••••••••••●•••••••••••••••••••••<>☆
🆑️ : @IroSource
",
                        'parse_mode' => 'markdown',
                    ]);
                }

                if (preg_match('/^senduser (.*)/', $msg, $paramter)) {
                    yield $this->messages->sendMessage([
                        'peer'            => $chatID,
                        'reply_to_msg_id' => $msg_id,
                        'message'         => "✅ برای تمامی کاربران ارسال شد"
                    ]);
                    $dialogs =  yield $this->get_Dialogs();
                    foreach ($dialogs as $k => $v) {
                        if ($v["_"] == "peerUser") {
                            yield $this->messages->sendMessage([
                                'peer'    => $v["user_id"],
                                'message' => $paramter[1]]);
                        }
                    }
                }

                if ($msg == "forpv" and isset($replyID)) {
                    yield $this->messages->sendMessage(['peer' => $chatID, 'reply_to_msg_id' => $msg_id, 'message' => "✅ برای تمامی کاربران فوروارد شد"]);
                    $dialogs =  yield $this->get_Dialogs();
                    foreach ($dialogs as $k => $v) {
                        if ($v["_"] == "peerUser") {
                            yield $this->messages->forwardMessages(['from_peer' => $chatID, 'id' => [$replyID], 'to_peer' => $v["user_id"]]);
                        }
                    }
                }

                if ($msg == "f2gps" || $msg == "/f2gps") {
                    $rid =  $update['message']['reply_to_msg_id'];
                    $dialogs = yield $this->get_dialogs();
                    $c = 0;
                    foreach ($dialogs as $peer) {
                        $type = yield $this->get_info($peer);
                        $type3 = $type['type'];
                        if ($type3 == "chat") {
                            try {
                                yield $this->messages->forwardMessages(['from_peer' => $update, 'to_peer' => $peer, 'id' => [$rid],]);
                                $c++;
                                yield $this->sleep(3);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                            } catch (\danog\MadelineProto\Exception $e) {
                            }
                        }
                    }
                    $_T = "🌀 پیام ریپلای شده شما به $c  گروه معمولی ارسال شد.";
                    yield $this->messages->sendMessage(['peer' => $update, 'message' => "$_T"]);
                }

                if ($msg == "f2sgps" || $msg == "/f2sgps") {
                    $rid =  $update['message']['reply_to_msg_id'];
                    $dialogs = yield $this->get_dialogs();
                    $c = 0;
                    foreach ($dialogs as $peer) {
                        $type = yield $this->get_info($peer);
                        $type3 = $type['type'];
                        if ($type3 == "supergroup") {
                            try {
                                yield $this->messages->forwardMessages(['from_peer' => $update, 'to_peer' => $peer, 'id' => [$rid],]);
                                $c++;
                                yield $this->sleep(3);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                            } catch (\danog\MadelineProto\Exception $e) {
                            }
                        }
                    }
                    $_T = "🌀 پیام ریپلای شده شما به $c  سوپر گروه  ارسال شد.";
                    yield $this->messages->sendMessage(['peer' => $update, 'message' => "$_T"]);
                }

                if ($msg == "f2all" || $msg == "/f2all") {
                    $rid =  $update['message']['reply_to_msg_id'];
                    $dialogs = yield $this->get_dialogs();
                    $c = 0;
                    foreach ($dialogs as $peer) {
                        $type = yield $this->get_info($peer);
                        $type3 = $type['type'];
                        if ($type3 == "supergroup" || $type3 == "user") {
                            try {
                                yield $MadelineProto->messages->forwardMessages(['from_peer' => $update, 'to_peer' => $peer, 'id' => [$rid],]);
                                $c++;
                                yield $this->sleep(3);
                            } catch (\danog\MadelineProto\RPCErrorException $e) {
                            } catch (\danog\MadelineProto\Exception $e) {
                            }
                        }
                    }
                    $_T = "🌀 پیام ریپلای شده شما به $c  گروه و کاربر ارسال شد";
                    yield $this->messages->sendMessage(['peer' => $update, 'message' => "$_T"]);
                }

                if ($msg == "online" || $msg == "bot" || $msg == "انلاینی") {
                    yield $this->messages->forwardMessages([
                        'from_peer' => $chatID,
                        'id'        => [$msg_id],
                        'to_peer'   => $chatID
                    ]);
                }
                if ($msg == "join on" || $msg == "جوین خودکار روشن") {
                    if ($join != "on") {
                        file_put_contents("data/join.txt", "on");
                        yield $this->messages->sendMessage([
                            'peer' => $chatID,
                            'message' => "• Join Auto For Link Is **Enable**",
                            'reply_to_msg_id' => $msg_id,
                            'parse_mode'      => 'markdown'
                        ]);
                    } else {
                        yield $this->messages->sendMessage([
                            'peer'            => $chatID,
                            'message'         => "• Join Auto For Link **Has Been Enabled**",
                            'reply_to_msg_id' => $msg_id,
                            'parse_mode'      => 'markdown'
                        ]);
                    }
                }
                if ($msg == "join off" || $msg == "جوین خودکار خاموش") {
                    if ($join == "on") {
                        file_put_contents("data/join.txt", "off");
                        yield $this->messages->sendMessage([
                            'peer'            => $chatID,
                            'message'         => "• Join Auto For Link Is <b>Disable</b>",
                            'reply_to_msg_id' => $msg_id,
                            'parse_mode'      => 'http'
                        ]);
                    } else {
                        yield $this->messages->sendMessage([
                            'peer'            => $chatID,
                            'message'         => "• Join Auto For Link <b>Has Been Disabled</b>",
                            'reply_to_msg_id' => $msg_id,
                            'parse_mode'      => 'html'
                        ]);
                    }
                }
                if ($msg == "list link" || $msg == "listlink" || $msg == "لیست لینک ها") {
                    $linkkk = file_get_contents("data/link.txt");
                    $links = count(explode(",", $linkkk)) - 1;
                    if ($links > 5) {
                        $alll = explode(",", $linkkk);
                        $s = "";
                        foreach ($alll as $m) {
                            $s .= "$m<br>".
                                        "〰〰〰〰〰〰〰〰〰〰〰<br>";
                        }
                        file_put_contents("link.txt",
                            "in the name of god \n".
                            " 〰〰〰〰〰〰〰〰〰〰〰 \n".
                            " list link for Tabchi \n".
                            " 〰〰〰〰〰〰〰〰〰〰〰 \n".
                            " powered by @IroSource \n \n" .
                            "〰〰〰〰〰〰〰〰〰〰〰 $s \n".
                            " end links");
                        $Updates = yield $this->messages->sendMedia([
                            'peer'            => $chatID,
                            'reply_to_msg_id' => $msg_id,
                            'media'           =>  [
                                '_'          => 'inputMediaUploadedDocument',
                                'file'       => 'link.txt',
                                'attributes' => [[
                                    '_'        => 'documentAttributeFilename',
                                    'file_name' => 'Tabchi_Links'
                                ]]
                            ],
                            'message'           =>
                                            "<br>" .
                                            " $links link<br>".
                                            "🔥powered by  @IroSource",
                            'parse_mode'   => 'html',]);
                        unlink("link.txt");
                    } else {
                        yield $this->messages->sendMessage([
                            'peer' => $chatID,
                            'message' => "• تعداد لینک ها کمتر از **5** است.",
                            'reply_to_msg_id' => $msg_id,
                            'parse_mode' => 'markdown',]);
                    }
                }
                if ($msg == "ping") {
                    yield $this->messages->sendMessage([
                        'peer'            => $chatID,
                        'message'         => "• Tabchi **GRT** Is Online ",
                        'reply_to_msg_id' => $msg_id,
                        'parse_mode'      => 'markdown',
                    ]);
                }
            }
            if ($join == "on") {
                $linkkk = file_get_contents("data/link.txt");
                $links = count(explode(",", $linkkk)) - 1;
                if (($links - $number) > 5) {
                    if (date("i") != $timer) {
                        $i12345 = explode(",", $linkkk);
                        file_put_contents("data/timer.txt", date("i"));
                        file_put_contents("data/number.txt", $number + 1);
                        yield $this->messages->importChatInvite([
                            'hash' => "$i12345[$number]",
                        ]);
                    }
                }
            }
            if ($userID == $admin) {
                if (preg_match('/^whois (.*)/', $msg, $m)) {
                    $meee = yield $this->get_full_info($m[1]);
                    $meeee = $meee['User'];
                    $first_name1 = $meeee['first_name'];
                    $id1 = $meeee['id'];
                    $iii = '<a href="mention:' . $id1 . '">' . $id1 . '</a>';
                    yield $this->messages->sendMessage([
                        'peer' => $chatID,
                        'reply_to_msg_id' => $msg_id, 
                        'message' => 
"
اطلاعات فرد :

id : $iii
——————
name : $first_name1
——————
", 
                        'parse_mode' => 'html'
                    ]);
                }
                if ($msg == 'setsudo') {
                    if (isset($update['message']['reply_to_msg_id'])) {
                        $reply_id2 = yield $this->channels->getMessages([
                            'channel' => $chatID,
                            'id'      => [$replyID]
                        ]);
                        $reply_from_id = $reply_id2['messages'][0]['from_id'];
                        $admin_list = file_get_contents("data/admin.txt");
                        $exp = explode("\n", $admin_list);
                        if (!in_array($reply_from_id, $exp)) {
                            $meee = yield $this->get_full_info($reply_from_id);
                            $meeee = $meee['User'];
                            $first_name1 = $meeee['first_name'];
                            $id1 = $meeee['id'];
                            $myfile2 = fopen("data/admin.txt", "a") || die("Unable to open file!");
                            fwrite($myfile2, "\n$id1");
                            fclose($myfile2);
                            $iii = '<a href="mention:' . $id1 . '">' . $id1 . '</a>';
                            yield $this->messages->sendMessage([
                                'peer' => $chatID,
                                'reply_to_msg_id' => $msg_id,
                                'message'         =>"<br>" .
                                                    "فرد مورد نظر با موفقیت ادمین ربات شد<br>" .
                                                    "<br>" .
                                                    "name : $first_name1<br>" .
                                                    "——————<br>" .
                                                    "id : $iii<br>" .
                                                    "——————<br>" .
                                                    "<br>",
                                'parse_mode' => 'html'
                            ]);
                        } else {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msg_id,
                                'message'         =>"<br>" .
                                                    "داش این که ادمین بود<br>" .
                                                    "——————<br>" .
                                                    "<br>",
                                'parse_mode'      => 'html'
                            ]);
                        }
                    }
                }

                if ($msg == 'sudolist') {
                    $admin2 = "";
                    $admin3 = "";
                    foreach ($exp as $admin) {
                        $admin2 .= "$admin \n";
                    }
                    if (count($exp) - 1 > 0) {
                        yield $this->messages->sendMessage([
                            'peer'            => $chatID,
                            'reply_to_msg_id' => $msg_id,
                            'message'         =>
"
ادمین های ربات:

$admin2
——————

برای دیدن هر فرد این دستور را ارسال کنید
whois [id]   ایدی عددی فرد را وارد کنید
مثال
whois 267785153
——————
",
                            'parse_mode' => 'html'
                        ]);
                    } else {
                        yield $this->messages->sendMessage([
                            'peer'            => $chatID,
                            'reply_to_msg_id' => $msg_id,
                            'message'         =>
"
لیست خالی است
——————
",
                            'parse_mode' => 'html'
                        ]);
                    }
                }

                if ($msg == 'remsudo') {
                    if (isset($update['message']['reply_to_msg_id'])) {
                        $reply_id2 = yield $this->channels->getMessages([
                            'channel' => $chatID,
                            'id' => [$replyID]
                        ]);
                        $reply_from_id = $reply_id2['messages'][0]['from_id'];
                        $admin_list = file_get_contents("data/admin.txt");
                        $exp = explode("\n", $admin_list);
                        if (in_array($reply_from_id, $exp)) {

                            $meee = yield $this->get_full_info($reply_from_id);
                            $meeee = $meee['User'];
                            $first_name1 = $meeee['first_name'];
                            $id1 = $meeee['id'];
                            $source = file_get_contents("data/admin.txt");
                            $source1 = str_replace("$reply_from_id", "", $source);
                            file_put_contents("data/admin.txt", $source1);
                            $ooo = '<a href="mention:' . $id1 . '">' . $id1 . '</a>';
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msg_id,
                                'message'         =>"<br>" .
                                                    "فرد مورد نظر از ادمینی ربات عزل شد<br>".
                                                    "<br>" .
                                                    "name : $first_name1<br>" .
                                                    "——————<br>".
                                                    "id : $ooo<br>".
                                                    "——————<br>".
                                                    "<br>",
                                'parse_mode'      => 'html'
                            ]);
                        } else {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msg_id,
                                'message'         => "<br>",
                                                     "این که اصلا ادمین نبود داش<br>".
                                                     "——————<br>".
                                                     "<br>",
                            'parse_mode' => 'html']);
                        }
                    }
                }
                if ($msg == 'cleansudo') {
                    file_put_contents("data/admin.txt", "");
                    yield $this->messages->sendMessage(['
                    peer' => $chatID,
                    'reply_to_msg_id' => $msg_id,
                    'message'         => "<br>" .
                                         "لیست ادمین ها پاکسازی شد<br>".
                                         "——————<br>".
                                         "<br>",
                        'parse_mode' => 'html'
                    ]);
                }
            }
 
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            yield $this->messages->sendMessage(['peer' => '0000000', 'message' => $e]);
        } catch (\danog\MadelineProto\Exception $e) {
            $this->messages->sendMessage([
                'peer' => '@',
                'message' => $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()]);
        }
    }
}

$settings['logger']['max_size'] = 100 * 1024 * 1024;
$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
$MadelineProto->loop();

?>