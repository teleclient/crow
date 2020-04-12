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
include_once 'config.php';

use \danog\MadelineProto\API;
use \danog\MadelineProto\Logger;
use \danog\MadelineProto\Tools;

class EventHandler extends \danog\MadelineProto\EventHandler {

    const OWNER = 157887279;    // ایدی عددی ران کننده ربات (Account Owner)
    const OPERATOR = 157887279; // ایدی عددی ادمین اصلی
    const SUDO = 157887279;     // Tech Suppurt person
    const ADMIN = self::OPERATOR;

    public function __construct($mp) {
        parent::__construct($mp);
    }

    /**
     * Called from within setEventHandler, can contain async calls for initialization of the bot
     *
     * @return void
     */
    public function onStart() {
        return;
    }

    /**
     * Get peer(s) where to report errors
     *
     * @return int|string|array
     */
    public function getReportPeers() {
        return [/* self::SUDO */];
    }

    static function toJSON($var, $pretty = true) {
        $opts = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $json = json_encode($var, !$pretty ? $opts : $opts | JSON_PRETTY_PRINT);
        if ($json === '') {
            $json = var_export($var, true);
        }
        return $json;
    }

    static function parseMsg(string $msg): array {
        $command = ['verb' => '', 'pref' => '', 'count' => 0, 'params' => []];
        if ($msg) {
            $msg = ltrim($msg);
            $prefix = substr($msg, 0, 1);
            if (strlen($msg) > 1 && in_array($prefix, ['!', '@', '/'])) {
                $space = strpos($msg, ' ') ?? 0;
                $verb = strtolower(substr(rtrim($msg), 1, ($space === 0 ? strlen($msg) : $space) - 1));
                $verb = strtolower($verb);
                if (ctype_alnum($verb)) {
                    $command['pref'] = $prefix;
                    $command['verb'] = $verb;
                    $tokens = explode(' ', trim($msg));
                    $command['count'] = count($tokens) - 1;
                    for ($i = 1; $i < count($tokens); $i++) {
                        $command['params'][$i - 1] = trim($tokens[$i]);
                    }
                }
                return $command;
            }
        }
        return $command;
    }

    function error($e, $chatID = NULL) {
        $this->logger($e, [], 'error');
        if (isset($chatID) && $this->settings['send_errors']) {
            try {
                $this->messages->sendMessage(
                        [
                            'peer' => $chatID,
                            'message' => '<b>' . $this->strings['error'] . '' .
                                         '</b><code>' . $e->getMessage() . '</code>',
                            'parse_mode' => 'HTML'
                        ],
                        [
                            'async' => true
                        ]
                );
            } catch (\Throwable $e) {
            }
        }
    }

    function parseUpdate($update) {
        $result = [
            'chatID'      => null,
            'userID'       => null,
            'msgID'        => null,
            'type'         => null,
            'name'         => null,
            'username'     => null,
            'chatusername' => null,
            'title'        => null,
            'msg'          => null,
            'info'         => null,
            'update'       => $update
        ];
        //try {
        if (isset($update['message'])) {
            if (isset($update['message']['from_id'])) {
                $result['userID'] = $update['message']['from_id'];
            }
            if (isset($update['message']['id'])) {
                $result['msgID'] = $update['message']['id'];
            }
            if (isset($update['message']['message'])) {
                $result['msg'] = $update['message']['message'];
            }
            if (isset($update['message']['to_id'])) {
                $result['info']['to'] = yield $this->getInfo($update['message']['to_id'], ['async' => false]);
                Tools::wait($result['info']['to']);
            }
            if (isset($result['info']['to']['bot_api_id'])) {
                $result['chatID'] = $result['info']['to']['bot_api_id'];
            }
            if (isset($result['info']['to']['type'])) {
                $result['type'] = $result['info']['to']['type'];
            }
            if (isset($result['userID'])) {
                $result['info']['from'] = yield $this->getInfo($result['userID'], ['async' => false]);
                Tools::wait($result['info']['from']);
                if (!isset($result['info']['from'])) {
                    echo($this->toJSON($result) . PHP_EOL);
                    throw new Exception('undefined');
                }
            }
            if (isset($result['userID']) && isset($result['info']['to']['User']['self']) && $result['info']['to']['User']['self']) {
                $result['chatID'] = $result['userID'];
            }
            if (isset($result['type']) && $result['type'] == 'chat') {
                $result['type'] = 'group';
            }
            if (isset($result['info']['from']['User']['first_name'])) {
                $result['name'] = $result['info']['from']['User']['first_name'];
            }
            if (isset($result['info']['to']['Chat']['title'])) {
                $result['title'] = $result['info']['to']['Chat']['title'];
            }
            if (isset($result['info']['from']['User']['username'])) {
                $result['username'] = $result['info']['from']['User']['username'];
            }
            if (isset($result['info']['to']['Chat']['username'])) {
                $result['chatusername'] = $result['info']['to']['Chat']['username'];
            }
        }
        //} catch (\Throwable $e) {
        //    $$this->error($e);
        //}
        return $result;
    }

    public function onUpdateNewChannelMessage($update) {
        yield $this->onUpdateNewMessage($update);
    }

    public function onUpdateNewMessage($update) {
        //try {
        if(($update['message']['_']?? null) === 'messageService') {
            return;
        }
        $parsedUpd = yield $this->parseUpdate($update);
        yield $this->logger(PHP_EOL . $this->toJSON($parsedUpd, true));

        $chatID = $parsedUpd['chatID'];
        $userID = $parsedUpd['userID'];
        $msg    = $parsedUpd['msg'];
        $msgID  = $parsedUpd['msgID'];
        $type   = $parsedUpd['type']; //'user', supergroup', 'channel'

        $me = yield $this->getSelf();
        $meID = $me['id'];
        $firstName = $me['first_name'];
        $phone = '+' . $me['phone'];

        $data = json_decode(file_get_contents("data.json"), true);

        $msgFront = substr(str_replace(array("\r", "\n"), '<br>', ($update['message']['message'] ?? '')), 0, 60);
        $msgDetail = 'chatID:' . $chatID . '/' . $msgID . '  ' .
                $update['_'] . '/' . $update['pts'] . '  ' .
                $type . ':[' . $parsedUpd['title'] . ']  ' .
                'msg:[' . $msgFront . ']';
        yield $this->echo($msgDetail . PHP_EOL);

        $command = self::parseMsg($msg);
        $cnt = function(int $paramCount) use($command): bool {
            return $command['params']['count'] === $paramCount;
        };
        $in = function(string ... $verbs) use($command): bool {
            foreach ($verbs as $verb) {
                if ($command['verb'] === $verb) {
                    return true;
                }
            }
            return false;
        };
        $frstStr = function() use($command): string {
            return $command['params'][0];
        };
        $frstInt = function() use($command): int {
            return intval($command['params'][0]);
        };
        $mp = $this;
        $vrb = $command['verb'];
        $bad = function() use($mp, $vrb, $chatID) {
            yield $mp->messages->sendMessage([
                        'peer'    => $chatID,
                        'message' => 'Invalid ' . $vrb . ' arguments'
            ]);
        };

        if (true/* $userID !== $meID */) {
            if (false /* (time() - filectime('update-session/session.madeline')) > 2505600 */) {
                if ($userID === self::ADMIN || isset($data['admins'][$userID])) {
                    yield $this->messages->sendMessage([
                                'peer'    => $chatID,
                                'message' => '❗️اخطار: مهلت استفاده شما از این ربات به اتمام رسیده❗️'
                    ]);
                }
            } else {
                //yield $this->echo('I am here'.PHP_EOL);

                if ($type === 'channel' && ($userID === self::ADMIN || isset($data['admins'][$userID]))) { // EXS
                    if (strpos($msg, 't.me/joinchat/') !== false) {
                        $a = explode('t.me/joinchat/', "$msg")[1];
                        $b = explode("\n", "$a")[0];
                        //throw new Exception('JoinChannel 1 ?????'); EXS
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
                    if ($in('adminadd')) {
                        if (!$cnt(1)) {
                            $bad();
                        } else {
                            $id = $frstInt();
                            if (!isset($data['admins'][$id])) {
                                $data['admins'][$id] = $id;
                                file_put_contents("data.json", json_encode($data));
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' => '🙌🏻 ادمین جدید اضافه شد'
                                ]);
                            } else {
                                yield $this->messages->sendMessage([
                                            'peer' => $chatID,
                                            'message' => "این شخص از قبل ادمین بود :/"
                                ]);
                            }
                        }
                    }
                    if ($in('admindel')) {
                        if (!$cnt(1)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                        'peer' => $chatID,
                                        'message' => "Not implemented yet!"
                            ]);
                        }
                    }
                    if ($in('adminlist')) {
                        if ($cnt(0)) {
                            $bad();
                        } else {
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
                    if ($in('adminempty')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            $data['admins'] = [];
                            file_put_contents("data.json", json_encode($data));
                            yield $this->messages->sendMessage([
                                        'peer'    => $chatID,
                                        'message' => "لیست ادمین خالی شد !"
                            ]);
                        }
                    }
                }

                if ($userID === self::ADMIN || isset($data['admins'][$userID])) {
                    yield $this->echo('An admin here!' . PHP_EOL);

                    if ($in('restart')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
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
                    }

                    if ($in('cleanup', 'پاکسازی')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                        'peer'    => $chatID,
                                        'message' => 'لطفا کمی صبر کنید ...'
                            ]);
                            $all = yield $this->getDialogs();
                            foreach ($all as $peer) {
                                $peerType = yield $this->getInfo($peer);
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
                                        'peer'      => $chatID,
                                        'message'   => '✅ پاکسازی باموفقیت انجام شد.<br>' .
                                                       '♻️ گروه هایی که در آنها بن شده بودم حذف شدند.',
                                        'parse_mode' => 'HTML'
                            ]);
                        }
                    }

                    if ($in('ping', 'انلاین', 'تبچی', 'انلاین')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                        'peer'            => $chatID,
                                        'reply_to_msg_id' => $msgID,
                                        //'message'         => "[🦅 Crow Tabchi ✅](tg://user?id=$userID)",
                                        'message' => "<a href='//user?id=$userID'>🦅 Crow Tabchi ✅</a>",
                                        'parse_mode'      => 'html'
                            ]);
                        }
                    }

                    if ($in('version', 'ورژن ربات')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                        'peer'            => $chatID,
                                        'reply_to_msg_id' => $msgID,
                                        'message'         => '<strong>⚙️ نسخه سورس تبچی : 6.6</strong>',
                                        'parse_mode'      => 'html'
                            ]);
                        }
                    }

                    if ($in('id', 'ایدی', 'شناسه', 'مشخصات')) {
                        if (!$cnt(1)) {
                            $bad();
                        } else {
                            //$name  = $me['first_name'];
                            //$phone = '+' . $me['phone'];
                            yield $this->messages->sendMessage([
                                        'peer' => $chatID,
                                        'reply_to_msg_id' => $msgID,
                                        'message' => "💚 مشخصات من<br>" .
                                        "<br>" .
                                        "👑 ادمین‌اصلی: [self::ADMIN](tg://user?id=self::ADMIN)<br>" .
                                        "👤 نام: $firstName<br>" .
                                        "#⃣ ایدی‌عددیم: <code>$meID</code><br>" .
                                        "📞 شماره‌تلفنم: <code>$phone</code><br>" .
                                        "<br>",
                                        'parse_mode' => 'HTML'
                            ]);
                        }
                    }

                    if ($in('stats', 'امار')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
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
                            $CpuCores = 'NoAccess!';
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
                                    $b = explode('cpu cores', "$a")[1];
                                    $b = explode("\n", "$b")[0];
                                    $b = explode(': ', "$b")[1];
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
                            $s = yield $this->getDialogs();
                            $m = json_encode($s, JSON_PRETTY_PRINT);
                            $supergps = count(explode('peerChannel', $m));
                            $pvs = count(explode('peerUser', $m));
                            $gps = count(explode('peerChat', $m));
                            $all = $gps + $supergps + $pvs;
                            yield $this->messages->sendMessage([
                                        'peer' => $chatID,
                                        'message' => 
                                                    "📊 Stats OghabTabchi :<br>" .
                                                    "<br>" .
                                                    "🔻 All : $all<br>" .
                                                    "→<br>" .
                                                    "👥 SuperGps + Channels : $supergps<br>" .
                                                    "→<br>" .
                                                    "👣 NormalGroups : $gps<br>" .
                                                    "→<br>" .
                                                    "📩 Users : $pvs<br>" .
                                                    "→<br>" .
                                                    "☎️ AutoChat : $sat<br>" .
                                                    "→<br>" .
                                                    //"☀️ Trial : $day day Or $hour Hour<br>".
                                                    //"→<br>".
                                                    "🎛 CPU Cores : $CpuCores<br>" .
                                                    "→<br>" .
                                                    "🔋 MemTotal : $mem_total<br>" .
                                                    "→<br>" .
                                                    "♻️ MemUsage by this bot : $mem_using",
                                        'parse_mode' => 'html'
                            ]);
                            if ($supergps > 400 || $pvs > 1500) {
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' =>
                                            '⚠️ اخطار: به دلیل کم بودن منابع هاست تعداد گروه ها نباید بیشتر از 400 و تعداد پیوی هاهم نباید بیشتراز 1.5K باشد.' .
                                            'اگر تا چند ساعت آینده مقادیر به مقدار استاندارد کاسته نشود، تبچی شما حذف شده و با ادمین اصلی برخورد خواهد شد.'
                                ]);
                            }
                        }
                    }

                    if ($in('help', 'راهنما')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                        'peer'    => $chatID,
                                        'message' =>
                                                    "⁉️ راهنماے تبچے کلاغ :<br>" .
                                                    "<br>" .
                                                    "`انلاین`<br>" .
                                                    "✅ دریافت وضعیت ربات<br>" .
                                                    "——————<br>" .
                                                    "`امار`<br>" .
                                                    "📊 دریافت آمار گروه ها و کاربران<br>" .
                                                    "——————<br>" .
                                                    "`/addall ` [UserID]<br>" .
                                                    "⏬ ادد کردن یڪ کاربر به همه گروه ها<br>" .
                                                    "——————<br>" .
                                                    "`/addpvs ` [IDGroup]<br>" .
                                                    "⬇️ ادد کردن همه ے افرادے که در پیوے هستن به یڪ گروه<br>" .
                                                    "——————<br>" .
                                                    "`f2all ` [reply]<br>" .
                                                    "〽️ فروارد کردن پیام ریپلاے شده به همه گروه ها و کاربران<br>" .
                                                    "——————<br>" .
                                                    "`f2pv ` [reply]<br>" .
                                                    "🔆 فروارد کردن پیام ریپلاے شده به همه کاربران<br>" .
                                                    "——————<br>" .
                                                    "`f2gps ` [reply]<br>" .
                                                    "🔊 فروارد کردن پیام ریپلاے شده به همه گروه ها<br>" .
                                                    "——————<br>" .
                                                    "`f2sgps ` [reply]<br>" .
                                                    "🌐 فروارد کردن پیام ریپلاے شده به همه سوپرگروه ها<br>" .
                                                    "——————<br>" .
                                                    "`/setFtime ` [reply],[time-min]<br>" .
                                                    "♻️ فعالسازے فروارد خودکار زماندار<br>" .
                                                    "——————<br>" .
                                                    "`/delFtime`<br>" .
                                                    "🌀 حذف فروارد خودکار زماندار<br>" .
                                                    "——————<br>" .
                                                    "`/SetId` [text]<br>" .
                                                    "⚙ تنظیم نام کاربرے (آیدے)ربات<br>" .
                                                    "——————<br>" .
                                                    "`/profile ` [نام] | [فامیل] | [بیوگرافی]<br>" .
                                                    "💎 تنظیم نام اسم ,فامےلو بیوگرافے ربات<br>" .
                                                    "——————<br>" .
                                                    "`/join ` [@ID] or [LINK]<br>" .
                                                    "🎉 عضویت در یڪ کانال یا گروه<br>" .
                                                    "——————<br>" .
                                                    "`ورژن ربات`<br>" .
                                                    "📜 نمایش نسخه سورس تبچے شما<br>" .
                                                    "——————<br>" .
                                                    "`پاکسازی`<br>" .
                                                    "📮 خروج از گروه هایے که مسدود کردند<br>" .
                                                    "——————<br>" .
                                                    "🆔 `مشخصات`<br>" .
                                                    "📎 دریافت ایدی‌عددے ربات تبچی<br>" .
                                                    "——————<br>" .
                                                    "`/delchs`<br>" .
                                                    "🥇خروج از همه ے کانال ها<br>" .
                                                    "——————<br>" .
                                                    "`/delgroups`<br>" .
                                                    "🥇خروج از همه ے گروه ها<br>" .
                                                    "——————<br>" .
                                                    "`/setPhoto ` [link]<br>" .
                                                    "📸 اپلود عکس پروفایل جدید<br>" .
                                                    "——————<br>" .
                                                    "`/autochat ` [on] or [off]<br>" .
                                                    "🎖 فعال یا خاموش کردن چت خودکار (پیوی و گروه ها)<br>" .
                                                    "<br>" .
                                                    "≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈ ≈<br>" .
                                                    "<br>" .
                                                    "📌️ این دستورات فقط براے ادمین اصلے قابل استفاده هستند :<br>" .
                                                    "`/adminadd ` [ایدی‌عددی]<br>" .
                                                    "➕ افزودن ادمین جدید<br>" .
                                                    "——————<br>" .
                                                    "`/admindel ` [ایدی‌عددی]<br>" .
                                                    "➖ حذف ادمین<br>" .
                                                    "——————<br>" .
                                                    "`/adminclean`<br>" .
                                                    "✖️ حذف همه ادمین ها<br>" .
                                                    "——————<br>" .
                                                    "<code>/adminlist`<br>" .
                                                    "📃 لیست همه ادمین ها",
                                        'parse_mode' => 'html'
                            ]);
                        }
                    }

                    if ($in('f2all')) {
                        if (!$cnt(0)) {
                            $bad(0);
                        } else {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->get_info($peer);
                                    if ($peerType['type'] == 'supergroup' ||
                                            $peerType['type'] == 'user'   ||
                                            $peerType['type'] == 'chat') {
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
                    }

                    if ($in('f2pv')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->getInfo($peer);
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
                    }

                    if ($in('f2gps')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->getInfo($peer);
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
                    }

                    if ($in('F2sgps')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            if ($type == 'supergroup') {
                                yield $this->messages->sendMessage([
                                            'peer'    => $chatID,
                                            'message' => '⛓ درحال فروارد ...'
                                ]);
                                $rid = $update['message']['reply_to_msg_id'];
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->getInfo($peer);
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
                    }

                    if ($in('/delftime')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            foreach (glob("ForTime/*") as $files) {
                                unlink("$files");
                            }
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => '➖ Removed !',
                                'reply_to_msg_id' => $msgID
                            ]);
                        }
                    }

                    if ($in('/delchs')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $all = yield $this->get_dialogs();
                            foreach ($all as $peer) {
                                $peerType = yield $this->get_info($peer);
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
                    }

                    if ($in('delgroups')) {
                        if (!$cnt(0)) {
                            $bad();
                        } else {
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'لطفا کمی صبر کنید...',
                                'reply_to_msg_id' => $msgID
                            ]);
                            $all = yield $this->getDialogs();
                            foreach ($all as $peer) {
                                //try {
                                $peerType = yield $this->getInfo($peer);
                                $type3 = $peerType['type'];
                                if ($type3 == 'supergroup' || $type3 == 'chat') {
                                    $id = $peerType['bot_api_id'];
                                    if ($chatID != $id) {
                                        yield $this->channels->leaveChannel([
                                            'channel' => $id
                                        ]);
                                    }
                                }
                                //} catch (Exception $m) {
                                //}
                            }
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => 'از همه ی گروه ها لفت دادم 👌',
                                'reply_to_msg_id' => $msgID
                            ]);
                        }
                    }

                    if ($in('autochat')) {
                        if (!$cnt(1) || !in_array($frstStr(), ['on', 'off'])) {
                            $bad();
                        } else {
                            $option = $frstStr();
                            $data['autochat']['on'] = $option;
                            file_put_contents("data.json", json_encode($data));
                            $text = $option === 'on' ? '🤖 حالت چت خودکار روشن شد ✅' : '🤖 حالت چت خودکار خاموش شد ❌';
                            yield $this->messages->sendMessage([
                                'peer'            => $chatID,
                                'message'         => $text,
                                'reply_to_msg_id' => $msgID
                            ]);
                        }
                    }

                    if ($in('join')) {
                        if (!$cnt(1) /* || !is_numeric($command['params'][0]) */) {
                            $bad();
                        } else {
                            $id = $frstStr();
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

                        if ($in('setid')) {
                            if (!$cnt(1) /* || !is_numeric($command['params'][0] */) {
                                $bad();
                            } else {
                                $id = $frstStr();
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
                        }

                        if ($in('profile ')) {
                            if (false && !$cnt(1)) {
                                $bad();
                            } else {
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
                                    'peer'    => $chatID,
                                    'message' => "🔸نام جدید تبچی: $id1<br>" .
                                                 "🔹نام خانوادگی جدید تبچی: $id2<br>" .
                                                 "🔸بیوگرافی جدید تبچی: $id3",
                                    'parse_mode' => 'HTML'
                                ]);
                            }
                        }

                        if ($in('addpvs')) {
                            if (!$cnt(0)) {
                                $bad();
                            } else {
                                yield $this->messages->sendMessage([
                                    'peer'    => $chatID,
                                    'message' => ' ⛓درحال ادد کردن ...'
                                ]);
                                $gpid = explode('addpvs ', $msg)[1];
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    $peerType = yield $this->getInfo($peer);
                                    $type3    = $peerType['type'];
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
                        }

                        if ($in('addall')) {
                            if (!$cnt(1)) {
                                $bad();
                            } else {
                                $user = $frstStr();
                                yield $this->messages->sendMessage([
                                    'peer'            => $chatID,
                                    'message'         => 'لطفا کمی صبر کنید...',
                                    'reply_to_msg_id' => $msgID
                                ]);
                                $dialogs = yield $this->getDialogs();
                                foreach ($dialogs as $peer) {
                                    //try {
                                    $peerType = yield $this->getInfo($peer);
                                    $type3    = $peerType['type'];
                                    //} catch (Exception $d) {
                                    //}
                                    if ($type3 == 'supergroup') {
                                        //try {
                                        yield $this->channels->inviteToChannel([
                                            'channel' => $peer,
                                            'users'   => ["$user"]
                                        ]);
                                        //} catch (Exception $d) {
                                        //}
                                    }
                                }
                                yield $this->messages->sendMessage([
                                    'peer'       => $chatID,
                                    'message'    => "کاربر <b>$user</b> توی همه ی ابرگروه ها ادد شد ✅",
                                    'parse_mode' => 'html'
                                ]);
                            }
                        }

                        if ($in('setPhoto')) {
                            if (!$cnt(0)) {
                                $bad();
                            } else {
                                $photo = $frstStr();
                                if (strpos($photo, '.jpg') !== false || strpos($photo, '.png') !== false) {
                                    copy($photo, 'photo.jpg');
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
                        }

                        if ($in('setftime')) {
                            if (!$cnt(1)) {
                                $bad();
                            } elseif (isset($update['message']['reply_to_msg_id'])) {
                                if ($type == 'supergroup') {
                                    $time = $frstInt();
                                    if ($time < 30) {
                                        yield $this->messages->sendMessage([
                                            'peer'       => $chatID,
                                            'message'    => '<b>❗️خطا: عدد وارد شده باید بیشتر از 30 دقیقه باشد.</b>',
                                            'parse_mode' => 'html'
                                        ]);
                                    } else {
                                        $time = $time * 60;
                                        if (!is_dir('ForTime')) {
                                            mkdir('ForTime');
                                        }
                                        file_put_contents("ForTime/msgid.txt", $update['message']['reply_to_msg_id']);
                                        file_put_contents("ForTime/chatid.txt", $chatID);
                                        file_put_contents("ForTime/time.txt", $time);
                                        yield $this->messages->sendMessage([
                                            'peer'            => $chatID,
                                            'message'         => "✅ فروارد زماندار باموفقیت روی این پُست درهر $time دقیقه تنظیم شد.",
                                            'reply_to_msg_id' => $update['message']['reply_to_msg_id']
                                        ]);
                                    }
                                } else {
                                    yield $this->messages->sendMessage([
                                        'peer' => $chatID,
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

                        $crow = array(
                            '❄️😐', '🍂😐', '😂😐', '😐😐😐😐', '😕', '😎💄', ':/',
                            '😂❤️', '🤦🏻‍♀🤦🏻‍♀🤦🏻‍♀', '🚶🏻‍♀🚶🏻‍♀🚶🏻‍♀', '🎈😐', 'شعت 🤐', '🥶'
                        );
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

                    // Disabled by EXS
                    if (false && ($userID === self::ADMIN || isset($data['admins'][$userID]))) {
                        yield $this->echo('Delete History here!' . PHP_EOL);
                        throw new Exception('DeleteHistory ?????');
                        yield $this->messages->deleteHistory([
                            'just_clear' => true,
                            'revoke'     => false,
                            'peer'       => $chatID,
                            'max_id'     => $msgID
                        ]);
                    }

                    if ($userID === self::ADMIN) {
                        if (
                            !file_exists('true') &&
                            file_exists('session.madeline') &&
                            filesize('session.madeline') / 1024 <= 4000
                        ) {
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
}

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}
$settings['logger']['logger_level'] = Logger::ULTRA_VERBOSE;
$settings['logger']['logger'] = Logger::FILE_LOGGER;
$settings['logger']['max_size'] = 1 * 1024 * 1024;
$settings['serialization']['serialization_interval'] = 30;
$settings['serialization']['cleanup_before_serialization'] = true;
$settings['app_info']['api_id'] = $GLOBALS["API_ID"];   // 839407;
$settings['app_info']['api_hash'] = $GLOBALS["API_HASH"]; // '0a310f9d03f51e8aa00d9262ef55d62e';

$mp = new API('session.madeline', $settings);
$mp->async(true);

$mp->loop(function () use ($mp) {
    yield $mp->start();
    yield $mp->setEventHandler('\EventHandler');
});

$mp->loop();
