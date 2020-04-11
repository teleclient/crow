<?php

// C * R * O * W  Also Known As: O * G * H * A * B

// نیاز به کرونجاب 1 دقیقه ای

if (!file_exists('data.json')) {
    file_put_contents('data.json', '{"autochat":{"on":"on"},"admins":{}}');
}
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include_once 'madeline.php';
include_once   'config.php';

use \danog\MadelineProto\API;
use \danog\MadelineProto\Logger;
use \danog\MadelineProto\Tools;


class EventHandler extends \danog\MadelineProto\EventHandler
{
    const CREATOR = 157887279; // ایدی عددی ران کننده ربات
    const ADMIN   = 157887279; // ایدی عددی ادمین اصلی
    const SUDO    = 157887279; // Tech Suppurt person

    public function __construct($mp)
    {
        parent::__construct($mp);
    }
    /**
     * Called from within setEventHandler, can contain async calls for initialization of the bot
     *
     * @return void
     */
    public function onStart()
    {
        return;
    }

    /**
     * Get peer(s) where to report errors
     *
     * @return int|string|array
     */
    public function getReportPeers()
    {
        return [/*self::SUDO*/];
    }

    function toJSON($var, $pretty = true) {
        $opts = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES;
        $json = json_encode($var, !$pretty? $opts : $opts|JSON_PRETTY_PRINT);
        if($json === '') {
            $json = var_export($var, true);
        }
        return $json;
    }

    function error ($e, $chatID = NULL) {
        $this->log($e, [], 'error');
        if (isset($chatID) && $this->settings['send_errors']) {
            try {
                $this->messages->sendMessage(
                    [
                        'peer'      => $chatID,
                        'message'   => '<b>' . $this->strings['error'] . ''.
                                       '</b><code>' . $e->getMessage() . '</code>',
                        'parse_mode' => 'HTML'
                    ],
                    [
                        'async' => true
                    ]
                );
            }
            catch (\Throwable $e) {
            }
        }
    }

    function parseUpdate($update)
    {
        @$info = [
            'to' => yield $this->getInfo($update['message']['to_id'], ['queue' => 'info_queue']),
            'from' => yield $this->getInfo($update['message']['from_id'], ['queue' => 'info_queue'])
        ];
        $result = [
            'chatID'       => (@$info['to']['User']['self']) ? @$update['message']['from_id'] : @$info['to']['bot_api_id'],
            'userID'       => @$update['message']['from_id'],
            'msgID'        => @$update['message']['id'],
            'type'         => (@$info['to']['type'] == 'chat') ? 'chat' : @$info['to']['type'],
            'name'         => @$info['from']['User']['first_name'],
            'username'     => @$info['from']['User']['username'],
            'chatusername' => @$info['to']['Chat']['username'],
            'title'        => @$info['to']['Chat']['title'],
            'msg'          => @$update['message']['message'],
            'info'         => @$info,
            'update'       => $update
        ];
        //try {
        //} catch (\Throwable $e) {
        //    $$this->error($e);
        //}
        return $result;
    }
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        //try {
            $parsedUpd = yield $this->parseUpdate($update);
            yield $this->logger(PHP_EOL.$this->toJSON($parsedUpd, true));

            $chatID = $parsedUpd['chatID'];
            $userID = $parsedUpd['userID'];
            $msg    = $parsedUpd['msg'];
            $msgID  = $parsedUpd['msgID'];
            $type   = $parsedUpd['type']; //'user', supergroup', 'channel'

            $me        = yield $this->get_self();
            $meID      = $me['id'];
            $firstName = $me['first_name'];
            $phone     = '+'. $me['phone'];

            $data      = json_decode(file_get_contents("data.json"), true);

            $msgFront  = substr(str_replace(array("\r", "\n"), '<br>', ($update['message']['message']??'')), 0, 60);
            $msgDetail = 'chatID:' . $chatID . '/' . $msgID . '  ' .
                          $update['_'] . '/' . $update['pts'] . '  ' .
                          $type . ':[' . $parsedUpd['title'] . ']  ' .
                          'msg:[' . $msgFront . ']';
            yield $this->echo($msgDetail.PHP_EOL);

            if (true/*$userID !== $meID*/) {
                if (false /*(time() - filectime('update-session/session.madeline')) > 2505600*/) {
                    if ($userID === self::ADMIN || isset($data['admins'][$userID])) {
                        yield $this->messages->sendMessage([
                            'peer'    => $chatID,
                            'message' => '❗️اخطار: مهلت استفاده شما از این ربات به اتمام رسیده❗️'
                        ]);
                    }
                }
                else {
                    yield $this->echo('I am here'.PHP_EOL);

                    if ($type === 'channel' || $userID === self::ADMIN || isset($data['admins'][$userID])) {
                        if (strpos($msg, 't.me/joinchat/') !== false) {
                            $a = explode('t.me/joinchat/', "$msg")[1];
                            $b = explode("\n", "$a")[0];
                            //try {
                                yield $this->channels->joinChannel([
                                    'channel' => "https://t.me/joinchat/$b"
                                ]);
                            //} catch (Exception $p) {
                            //} catch (\danog\MadelineProto\RPCErrorException $p) {
                            //}
                        }
                    }

                    if (isset($update['message']['reply_markup']['rows'])) {
                        if ($type == 'supergroup') {
                            foreach ($update['message']['reply_markup']['rows'] as $row) {
                                foreach ($row['buttons'] as $button) {
                                    yield $button->click();
                                }
                            }
                        }
                    }

                    if ($chatID == 777000) {
                        @$a = str_replace(range(0,9), ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], $msg);
                        yield $this->messages->sendMessage([
                            'peer'    => self::ADMIN,
                            'message' => "$a"
                        ]);
                        yield $this->messages->deleteHistory([
                            'just_clear' => true,
                            'revoke'     => true,
                            'peer'       => $chatID,
                            'max_id'     => $msgID
                        ]);
                    }

                    if ($userID == self::ADMIN) {
                        if (preg_match("/^[#\!\/](addadmin) (.*)$/", $msg)) {
                            preg_match("/^[#\!\/](addadmin) (.*)$/", $msg, $text1);
                            $id = $text1[2];
                            if (!isset($data['admins'][$id])) {
                                $data['admins'][$id] = $id;
                                file_put_contents("data.json", json_encode($data));
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '🙌🏻 ادمین جدید اضافه شد'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => "این شخص از قبل ادمین بود :/"
                                ]);
                            }
                        }
                        if (preg_match("/^[\/\#\!]?(clean admins)$/i", $msg)) {
                            $data['admins'] = [];
                            file_put_contents("data.json", json_encode($data));
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => "لیست ادمین خالی شد !"
                            ]);
                        }
                        if (preg_match("/^[\/\#\!]?(adminlist)$/i", $msg)) {
                            if (count($data['admins']) > 0) {
                                $txxxt = "لیست ادمین ها :<br>";
                                $counter = 1;
                                foreach ($data['admins'] as $k) {
                                    $txxxt .= "$counter: <code>$k</code><br>";
                                    $counter++;
                                }
                                yield $this->messages->sendMessage([
                                    'peer'       => $chatID,
                                    'message'    => $txxxt,
                                    'parse_mode' => 'html'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => "ادمینی وجود ندارد !"
                                ]);
                            }
                        }
                    }

                    if ($userID === self::ADMIN || isset($data['admins'][$userID])) {
                        if ($msg === '/restart') {
                            yield $this->messages->deleteHistory([
                                'just_clear' => true,
                                'revoke'     => true,
                                'peer'       => $chatID,
                                'max_id'     => $msgID
                            ]);
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => '♻️ ربات دوباره راه اندازی شد.'
                            ]);
                            yield $this->restart();
                        }

                        if ($msg === 'پاکسازی') {
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => 'لطفا کمی صبر کنید ...'
                            ]);
                            $all = yield $this->get_dialogs();
                            foreach ($all as $peer) {
                                $peerType = yield $this->get_info($peer);
                                if ($peerType['type'] === 'supergroup') {
                                    $subgroupInfo = yield $this->channels->getChannels([
                                        'id' => [$peer]
                                    ]);
                                    @$banned = $subgroupInfo['chats'][0]['banned_rights']['send_messages'];
                                    if ($banned == 1) {
                                        yield $this->channels->leaveChannel([
                                            'channel' => $peer
                                        ]);
                                    }
                                }
                            }
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => '✅ پاکسازی باموفقیت انجام شد.'.
                                             '♻️ گروه هایی که در آنها بن شده بودم حذف شدند.'
                            ]);
                        }

                        if ($msg == 'انلاین' ||
                            $msg == 'تبچی' || $msg == '!ping' || $msg == '#ping' || $msg == 'ربات' ||
                            $msg == 'ping' || $msg == '/ping') {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msgID,
                                'message'         => "[🦅 Crow Tabchi ✅](tg://user?id=$userID)",
                                'parse_mode'      => 'markdown'
                            ]);
                        }

                        if ($msg == 'ورژن ربات') {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msgID,
                                'message'         => '**⚙️ نسخه سورس تبچی : 6.6**',
                                'parse_mode'      => 'MarkDown'
                            ]);
                        }

                        if ($msg == 'شناسه' || $msg == 'id' || $msg == 'ایدی' || $msg == 'مشخصات') {
                            //$name  = $me['first_name'];
                            //$phone = '+' . $me['phone'];
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'reply_to_msg_id' => $msgID,
                                'message'         => "💚 مشخصات من<br>".
                                                     "<br>".
                                                     "👑 ادمین‌اصلی: [self::ADMIN](tg://user?id=self::ADMIN)<br>".
                                                     "👤 نام: $firstName<br>".
                                                     "#⃣ ایدی‌عددیم: <code>$meID</code><br>".
                                                     "📞 شماره‌تلفنم: <code>$phone</code><br>".
                                                     "<br>",
                                'parse_mode' => 'HTML'
                            ]);
                        }

                        if ($msg == 'امار' || $msg == 'آمار' || $msg == 'stats') {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $mem_using = round((memory_get_usage() / 1024) / 1024, 0) . 'MB';
                            $sat = $data['autochat']['on'];
                            if ($sat == 'on') {
                                $sat = '✅';
                            } else {
                                $sat = '❌';
                            }
                            $mem_total = 'NoAccess!';
                            $CpuCores  = 'NoAccess!';
                            //try {
                                if (strpos(@$_SERVER['SERVER_NAME'], '000webhost') === false) {
                                    if (strpos(PHP_OS, 'L') !== false || strpos(PHP_OS, 'l') !== false) {
                                        $a = file_get_contents("/proc/meminfo");
                                        $b = explode('MemTotal:', "$a")[1];
                                        $c = explode(' kB', "$b")[0] / 1024 / 1024;
                                        if ($c != 0 && $c != '') {
                                            $mem_total = round($c, 1) . 'GB';
                                        } else {
                                            $mem_total = 'NoAccess!';
                                        }
                                    } else {
                                        $mem_total = 'NoAccess!';
                                    }
                                    if (strpos(PHP_OS, 'L') !== false || strpos(PHP_OS, 'l') !== false) {
                                        $a = file_get_contents("/proc/cpuinfo");
                                        /*@*/$b = explode('cpu cores', "$a")[1];
                                        /*@*/$b = explode("\n", "$b")[0];
                                        /*@*/$b = explode(': ', "$b")[1];
                                        if ($b != 0 && $b != '') {
                                            $CpuCores = $b;
                                        } else {
                                            $CpuCores = 'NoAccess!';
                                        }
                                    } else {
                                        $CpuCores = 'NoAccess!';
                                    }
                                }
                            //} catch (Exception $f) {
                            //}
                            $s        = yield $this->get_dialogs();
                            $m        = json_encode($s, JSON_PRETTY_PRINT);
                            $supergps = count(explode('peerChannel', $m));
                            $pvs      = count(explode('peerUser',    $m));
                            $gps      = count(explode('peerChat',    $m));
                            $all      = $gps + $supergps + $pvs;
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' =>"📊 Stats OghabTabchi :<br>".
                                            "<br>".
                                            "🔻 All : $all<br>".
                                            "→<br>".
                                            "👥 SuperGps + Channels : $supergps<br>".
                                            "→<br>".
                                            "👣 NormalGroups : $gps<br>".
                                            "→<br>".
                                            "📩 Users : $pvs<br>".
                                            "→<br>".
                                            "☎️ AutoChat : $sat<br>".
                                            "→<br>".
                                          //"☀️ Trial : $day day Or $hour Hour<br>".
                                          //"→<br>".
                                            "🎛 CPU Cores : $CpuCores<br>".
                                            "→<br>".
                                            "🔋 MemTotal : $mem_total<br>".
                                            "→<br>".
                                            "♻️ MemUsage by this bot : $mem_using",
                                'parse_mode' => 'html'
                            ]);
                            if ($supergps > 400 || $pvs > 1500) {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' =>
                                                '⚠️ اخطار: به دلیل کم بودن منابع هاست تعداد گروه ها نباید بیشتر از 400 و تعداد پیوی هاهم نباید بیشتراز 1.5K باشد.'.
                                                'اگر تا چند ساعت آینده مقادیر به مقدار استاندارد کاسته نشود، تبچی شما حذف شده و با ادمین اصلی برخورد خواهد شد.'
                                ]);
                            }
                        }
                        if ($msg == 'help' || $msg == '/help' || $msg == 'Help' || $msg == 'راهنما') {
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' =>
                                            "⁉️ راهنماے تبچے کلاغ :<br>".
                                            "<br>".
                                            "`انلاین`<br>".
                                            "✅ دریافت وضعیت ربات<br>".
                                            "——————<br>".
                                            "`امار`<br>".
                                            "📊 دریافت آمار گروه ها و کاربران<br>".
                                            "——————<br>".
                                            "`/addall ` [UserID]<br>".
                                            "⏬ ادد کردن یڪ کاربر به همه گروه ها<br>".
                                            "——————<br>".
                                            "`/addpvs ` [IDGroup]<br>".
                                            "⬇️ ادد کردن همه ے افرادے که در پیوے هستن به یڪ گروه<br>".
                                            "——————<br>".
                                            "`f2all ` [reply]<br>".
                                            "〽️ فروارد کردن پیام ریپلاے شده به همه گروه ها و کاربران<br>".
                                            "——————<br>".
                                            "`f2pv ` [reply]<br>".
                                            "🔆 فروارد کردن پیام ریپلاے شده به همه کاربران<br>".
                                            "——————<br>".
                                            "`f2gps ` [reply]<br>".
                                            "🔊 فروارد کردن پیام ریپلاے شده به همه گروه ها<br>".
                                            "——————<br>".
                                            "`f2sgps ` [reply]<br>".
                                            "🌐 فروارد کردن پیام ریپلاے شده به همه سوپرگروه ها<br>".
                                            "——————<br>".
                                            "`/setFtime ` [reply],[time-min]<br>".
                                            "♻️ فعالسازے فروارد خودکار زماندار<br>".
                                            "——————<br>".
                                            "`/delFtime`<br>".
                                            "🌀 حذف فروارد خودکار زماندار<br>".
                                            "——————<br>".
                                            "`/SetId` [text]<br>".
                                            "⚙ تنظیم نام کاربرے (آیدے)ربات<br>".
                                            "——————<br>".
                                            "`/profile ` [نام] | [فامیل] | [بیوگرافی]<br>".
                                            "💎 تنظیم نام اسم ,فامےلو بیوگرافے ربات<br>".
                                            "——————<br>".
                                            "`/join ` [@ID] or [LINK]<br>".
                                            "🎉 عضویت در یڪ کانال یا گروه<br>".
                                            "——————<br>".
                                            "`ورژن ربات`<br>".
                                            "📜 نمایش نسخه سورس تبچے شما<br>".
                                            "——————<br>".
                                            "`پاکسازی`<br>".
                                            "📮 خروج از گروه هایے که مسدود کردند<br>".
                                            "——————<br>".
                                            "🆔 `مشخصات`<br>".
                                            "📎 دریافت ایدی‌عددے ربات تبچی<br>".
                                            "——————<br>".
                                            "`/delchs`<br>".
                                            "🥇خروج از همه ے کانال ها<br>".
                                            "——————<br>".
                                            "`/delgroups`<br>".
                                            "🥇خروج از همه ے گروه ها<br>".
                                            "——————<br>".
                                            "`/setPhoto ` [link]<br>".
                                            "📸 اپلود عکس پروفایل جدید<br>".
                                            "——————<br>".
                                            "`/autochat ` [on] or [off]<br>".
                                            "🎖 فعال یا خاموش کردن چت خودکار (پیوی و گروه ها)<br>".
                                            "<br>".
                                            "≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈<br>".
                                            "<br>".
                                            "📌️ این دستورات فقط براے ادمین اصلے قابل استفاده هستند :<br>".
                                            "`/addadmin ` [ایدی‌عددی]<br>".
                                            "➕ افزودن ادمین جدید<br>".
                                            "——————<br>".
                                            "`/deladmin ` [ایدی‌عددی]<br>".
                                            "➖ حذف ادمین<br>".
                                            "——————<br>".
                                            "`/clean admins`<br>".
                                            "✖️ حذف همه ادمین ها<br>".
                                            "——————<br>".
                                            "<code>/adminlist`<br>".
                                            "📃 لیست همه ادمین ها",
                                'parse_mode' => 'html'
                            ]);
                        }

                        if ($msg == 'F2all' || $msg == 'f2all') {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->get_dialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->get_info($peer);
                                    if ($peerType['type'] == 'supergroup' ||
                                        $peerType['type'] == 'user' ||
                                        $peerType['type'] == 'chat')
                                    {
                                        $this->messages->forwardMessages([
                                            'from_peer' => $chatID,
                                            'to_peer'   => $peer,
                                            'id'        => [$rid]
                                        ]);
                                    }
                                }
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => 'فروارد همگانی با موفقیت به همه ارسال شد 👌🏻'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '‼از این دستور فقط در سوپرگروه میتوانید استفاده کنید.'
                                ]);
                            }
                        }

                        if ($msg == 'F2pv' || $msg == 'f2pv') {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->get_dialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->get_info($peer);
                                    if ($peerType['type'] == 'user') {
                                        $this->messages->forwardMessages([
                                            'from_peer' => $chatID,
                                            'to_peer'   => $peer,
                                            'id'        => [$rid]
                                        ]);
                                    }
                                }
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => 'فروارد همگانی با موفقیت به پیوی ها ارسال شد 👌🏻'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '‼از این دستور فقط در سوپرگروه میتوانید استفاده کنید.'
                                ]);
                            }
                        }

                        if ($msg == 'F2gps' || $msg == 'f2gps') {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->get_dialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->get_info($peer);
                                    if ($peerType['type'] == 'chat') {
                                        $this->messages->forwardMessages([
                                            'from_peer' => $chatID,
                                            'to_peer'   => $peer, 'id' => [$rid]
                                        ]);
                                    }
                                }
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => 'فروارد همگانی با موفقیت به گروه ها ارسال شد👌🏻'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '‼از این دستور فقط در سوپرگروه میتوانید استفاده کنید.'
                                ]);
                            }
                        }

                        if ($msg == 'F2sgps' || $msg == 'f2sgps') {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->get_dialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->get_info($peer);
                                    if ($peerType['type'] == 'supergroup') {
                                        $this->messages->forwardMessages([
                                            'from_peer' => $chatID,
                                            'to_peer'   => $peer,
                                            'id'        => [$rid]]);
                                    }
                                }
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => 'فروارد همگانی با موفقیت به سوپرگروه ها ارسال شد 👌🏻'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '‼از این دستور فقط در سوپرگروه میتوانید استفاده کنید.'
                                ]);
                            }
                        }

                        if ($msg == '/delFtime') {
                            foreach (glob("ForTime/*") as $files) {
                                unlink("$files");
                            }
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID, 'message' => '➖ Removed !',
                                'reply_to_msg_id' => $msgID
                            ]);
                        }

                        if ($msg == 'delchs' || $msg == '/delchs') {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $all = yield $this->get_dialogs();
                            foreach ($all as $peer) {
                                $peerType  = yield $this->get_info($peer);
                                $type3 = $peerType['type'];
                                if ($type3 == 'channel') {
                                    $id = $peerType['bot_api_id'];
                                    yield $this->channels->leaveChannel(['channel' => $id]);
                                }
                            }
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'از همه ی کانال ها لفت دادم 👌',
                                'reply_to_msg_id' => $msgID
                            ]);
                        }

                        if ($msg == 'delgroups' || $msg == '/delgroups') {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $all = yield $this->get_dialogs();
                            foreach ($all as $peer) {
                                try {
                                    $peerType  = yield $this->get_info($peer);
                                    $type3 = $peerType['type'];
                                    if ($type3 == 'supergroup' || $type3 == 'chat') {
                                        $id = $peerType['bot_api_id'];
                                        if ($chatID != $id) {
                                            yield $this->channels->leaveChannel([
                                                'channel' => $id
                                            ]);
                                        }
                                    }
                                } catch (Exception $m) {
                                }
                            }
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'از همه ی گروه ها لفت دادم 👌',
                                'reply_to_msg_id' => $msgID
                            ]);
                        }

                        if (preg_match("/^[\/\#\!]?(autochat) (on|off)$/i", $msg)) {
                            preg_match("/^[\/\#\!]?(autochat) (on|off)$/i", $msg, $m);
                            $data['autochat']['on'] = "$m[2]";
                            file_put_contents("data.json", json_encode($data));
                            if ($m[2] == 'on') {
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '🤖 حالت چت خودکار روشن شد ✅',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '🤖 حالت چت خودکار خاموش شد ❌',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            }
                        }

                        if (preg_match("/^[\/\#\!]?(join) (.*)$/i", $msg)) {
                            preg_match("/^[\/\#\!]?(join) (.*)$/i", $msg, $text);
                            $id = $text[2];
                            try {
                                yield $this->channels->joinChannel([
                                    'channel' => "$id"
                                ]);
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '✅ Joined',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            } catch (Exception $e) {
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '❗️<code>' . $e->getMessage() . '</code>',
                                    'parse_mode'      => 'html',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            }
                        }
                        if (preg_match("/^[\/\#\!]?(SetId) (.*)$/i", $msg)) {
                            preg_match("/^[\/\#\!]?(SetId) (.*)$/i", $msg, $text);
                            $id = $text[2];
                            try {
                                $User = yield $this->account->updateUsername([
                                    'username' => "$id"
                                ]);
                            } catch (Exception $v) {
                                $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => '❗' . $v->getMessage()
                                ]);
                            }
                            $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => "• نام کاربری جدید برای ربات تنظیم شد :<br>@$id"
                            ]);
                        }
                        if (strpos($msg, '/profile ') !== false) {
                            $ip = trim(str_replace("/profile ", "", $msg));
                            $ip = explode("|", $ip . "|||||");
                            $id1 = trim($ip[0]);
                            $id2 = trim($ip[1]);
                            $id3 = trim($ip[2]);
                            yield $this->account->updateProfile([
                                'first_name' => "$id1",
                                'last_name'  => "$id2",
                                'about'      => "$id3"
                            ]);
                            yield $this->messages->sendMessage([
                                'peer' => $chatID,
                                'message' => "🔸نام جدید تبچی: $id1<br>".
                                             "🔹نام خانوادگی جدید تبچی: $id2".
                                             "🔸بیوگرافی جدید تبچی: $id3"
                            ]);
                        }

                        if (strpos($msg, 'addpvs ') !== false) {
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => ' ⛓درحال ادد کردن ...'
                            ]);
                            $gpid = explode('addpvs ', $msg)[1];
                            $dialogs = yield $this->get_dialogs();
                            foreach ($dialogs as $peer) {
                                $peerType = yield $this->get_info($peer);
                                $type3 = $peerType['type'];
                                if ($type3 == 'user') {
                                    $pvid = $peerType['user_id'];
                                    $this->channels->inviteToChannel([
                                        'channel' => $gpid,
                                        'users'   => [$pvid]
                                    ]);
                                }
                            }
                            yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => "همه افرادی که در پیوی بودند را در گروه $gpid ادد کردم 👌🏻"
                            ]);
                        }

                        if (preg_match("/^[#\!\/](addall) (.*)$/", $msg)) {
                            preg_match("/^[#\!\/](addall) (.*)$/", $msg, $text1);
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $user = $text1[2];
                            $dialogs = yield $this->get_dialogs();
                            foreach ($dialogs as $peer) {
                                try {
                                    $peerType = yield $this->get_info($peer);
                                    $type3 = $peerType['type'];
                                } catch (Exception $d) {
                                }
                                if ($type3 == 'supergroup') {
                                    try {
                                        yield $this->channels->inviteToChannel([
                                            'channel' => $peer,
                                            'users'   => ["$user"]
                                        ]);
                                    } catch (Exception $d) {
                                    }
                                }
                            }
                            yield $this->messages->sendMessage([
                                'peer'       => $chatID,
                                'message'    => "کاربر **$user** توی همه ی ابرگروه ها ادد شد ✅",
                                'parse_mode' => 'MarkDown'
                            ]);
                        }

                        if (preg_match("/^[#\!\/](setPhoto) (.*)$/", $msg)) {
                            preg_match("/^[#\!\/](setPhoto) (.*)$/", $msg, $text1);
                            if (strpos($text1[2], '.jpg') !== false || strpos($text1[2], '.png') !== false) {
                                copy($text1[2], 'photo.jpg');
                                yield $this->photos->updateProfilePhoto([
                                    'id' => 'photo.jpg'
                                ]);
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '📸 عکس پروفایل جدید باموفقیت ست شد.',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => '❌ فایل داخل لینک عکس نمیباشد!',
                                    'reply_to_msg_id' => $msgID
                                ]);
                            }
                        }

                        if (preg_match("/^[#\!\/](setFtime) (.*)$/", $msg)) {
                            if (isset($update['message']['reply_to_msg_id'])) {
                                if ($type == 'supergroup') {
                                    preg_match("/^[#\!\/](setFtime) (.*)$/", $msg, $text1);
                                    if ($text1[2] < 30) {
                                        yield $this->messages->sendMessage([
                                            'peer'       => $chatID,
                                            'message'    => '**❗️خطا: عدد وارد شده باید بیشتر از 30 دقیقه باشد.**',
                                            'parse_mode' => 'MarkDown'
                                        ]);
                                    } else {
                                        $time = $text1[2] * 60;
                                        if (!is_dir('ForTime')) {
                                            mkdir('ForTime');
                                        }
                                        file_put_contents("ForTime/msgid.txt", $update['message']['reply_to_msg_id']);
                                        file_put_contents("ForTime/chatid.txt", $chatID);
                                        file_put_contents("ForTime/time.txt", $time);
                                        yield $this->messages->sendMessage([
                                            'peer'            => $chatID,
                                            'message'         => "✅ فروارد زماندار باموفقیت روی این پُست درهر $text1[2] دقیقه تنظیم شد.",
                                            'reply_to_msg_id' => $update['message']['reply_to_msg_id']
                                        ]);
                                    }
                                } else {
                                    yield $this->messages->sendMessage([
                                        'peer'    => $chatID,
                                        'message' => '‼از این دستور فقط در سوپرگروه میتوانید استفاده کنید.'
                                    ]);
                                }
                            }
                        }
                    }

                    if ($type != 'channel' && @$data['autochat']['on'] == 'on' && rand(0, 2000) == 1) {
                        yield $this->sleep(4);

                        if ($type == 'user') {
                            yield $this->messages->readHistory([
                                'peer'   => $userID,
                                'max_id' => $msgID
                            ]);
                            yield $this->sleep(2);
                        }

                        yield $this->messages->setTyping([
                            'peer'   => $chatID,
                            'action' => ['_' => 'sendMessageTypingAction']
                        ]);

                        $crow = array('❄️😐', '🍂😐', '😂😐', '😐😐😐😐', '😕', '😎💄', ':/',
                                      '😂❤️', '🤦🏻‍♀🤦🏻‍♀🤦🏻‍♀', '🚶🏻‍♀🚶🏻‍♀🚶🏻‍♀', '🎈😐', 'شعت 🤐', '🥶');
                        $texx = $crow[rand(0, count($crow) - 1)];
                        yield $this->sleep(1);
                        yield $this->messages->sendMessage([
                            'peer'    => $chatID,
                            'message' => "$texx"
                        ]);
                    }

                    if (file_exists('ForTime/time.txt')) {
                        if ((time() - filectime('ForTime/time.txt')) >= file_get_contents('ForTime/time.txt')) {
                            $tt = file_get_contents('ForTime/time.txt');
                            unlink('ForTime/time.txt');
                            file_put_contents('ForTime/time.txt', $tt);
                            $dialogs = yield $this->get_dialogs();
                            foreach ($dialogs as $peer) {
                                $peerType = yield $this->get_info($peer);
                                if ($peerType['type'] == 'supergroup' || $peerType['type'] == 'chat') {
                                    $this->messages->forwardMessages([
                                        'from_peer' => file_get_contents('ForTime/chatid.txt'),
                                        'to_peer'   => $peer,
                                        'id'        => [file_get_contents('ForTime/msgid.txt')]
                                    ]);
                                }
                            }
                        }
                    }
                    if ($userID === self::ADMIN || isset($data['admins'][$userID])) {
                        yield $this->messages->deleteHistory([
                            'just_clear' => true,
                            'revoke'     => false,
                            'peer'       => $chatID,
                            'max_id'     => $msgID
                        ]);
                    }
                    if ($userID === self::ADMIN) {
                        if (!file_exists('true') && file_exists('session.madeline') &&
                            filesize('session.madeline') / 1024 <= 4000)
                        {
                            //file_put_contents('true', '');
                            yield $this->sleep(3);
                            //copy('session.madeline', 'update-session/session.madeline');
                        }
                    }
                }
            }
        //}
        //catch (Exception $e) {
            // $a = fopen('trycatch.txt', 'a') || die("Unable to open file!");
            // fwrite($a, "Error: ".$e->getMessage().PHP_EOL."Line: ".$e->getLine().PHP_EOL."- - - - -".PHP_EOL);
            // fclose($a);
        //}
    }
}

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}
$settings['logger']['logger_level'] = Logger::ULTRA_VERBOSE;
$settings['logger']['logger']       = Logger::FILE_LOGGER;
$settings['logger']['max_size']     = 1 * 1024 * 1024;
$settings['serialization']['serialization_interval']       = 30;
$settings['serialization']['cleanup_before_serialization'] = true;
$settings['app_info']['api_id']   = $GLOBALS["API_ID"];   // 839407;
$settings['app_info']['api_hash'] = $GLOBALS["API_HASH"]; // '0a310f9d03f51e8aa00d9262ef55d62e';

$mp = new API('session.madeline', $settings);
$mp->async(true);

$mp->loop(function () use ($mp) {
    yield $mp->start();
    yield $mp->setEventHandler('\EventHandler');
});

$mp->loop();
