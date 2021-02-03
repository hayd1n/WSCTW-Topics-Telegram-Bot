<?php
    include('WSCTW_CompetitionTopics_Crawler.php');

    date_default_timezone_set("Asia/Taipei");

    ini_set('user_agent','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36');
    $topic_url = "https://www.wdasec.gov.tw/News_Content.aspx?n=12FE9C104388A457&s=3D42933DA696DA8C";

    $logs_file = file_get_contents(dirname(__FILE__) . "/log.json");
    $logs = json_decode($logs_file, true);

    $telegram_bot_token = "BOT_TOKEN";
    $telegram_bot_chat_id = "CHAT_ID";

    $telegram_api_url = "https://api.telegram.org/" . $telegram_bot_token;

    $message = "★全國技能競賽最新題目更新★" . "\n". date("[Y/m/d H:i:s]") . "\n\n";

    $topics = array();
    printT("正在從勞動部網站爬取最新的技能競賽試題...");
    $topics = getCompetitionTopics($topic_url);
    if($topics) {
        echo "\033[33m[成功]\033[0m" . PHP_EOL;
        $topics_count = count($topics);
        $message_count = 0;
        $n = 0;
        foreach($topics as $topic) {
            $topicInLogs = searchNameInArray($topic['name'], $logs);
            if($logs[$topicInLogs] != $topic or $logs[$topicInLogs]['files'] != $topic['files']) {
                println("獲取到最新題目：" . $topic['name']);
                $files_count = count($topic['files']);
                $i = 1;
                $message .= "名稱: " . $topic['name'] . "\n";
                foreach($topic['files'] as $file) {
                    println("→檔案附件[" . $i . "/" . $files_count . "]：(" . $file['type'] . ") " . $file['link']);
                    $message .= "附件檔案: " . "\n";
                    $message .= "[" . $i . "/" . $files_count . "]" . "(" . $file['type'] . ") " . $file['link'] . "\n";
                    $i++;
                }
                $message .= "\n";
                $message_count++;
            }
            if($message_count >= 5 or $n >= $topics_count-1) {
                printT("已累計達" . $message_count . "則新題目，發送Telegram公告訊息...");
                $message .= "題目來源網站: " . $topic_url;
                if(sendTelegramMessage($telegram_bot_chat_id, $message)) {
                    echo "\033[33m[成功]\033[0m" . PHP_EOL;
                    println("為了避免Telegram訊息發送過快，系統暫停1秒...");
                    sleep(1);
                    println("");
                }else{
                    echo "\033[31m[失敗]\033[0m" . PHP_EOL;
                }
                $message = "★全國技能競賽最新題目更新★" . "\n". date("[Y/m/d H:i:s]") . "\n\n";
                $message_count = 0;
            }
            $n++;
        }

    //var_dump($topics); //DEBUG

    //保存該次爬取紀錄
    if($fp = fopen(dirname(__FILE__) . '/log.json','w+')) {  
        $rc = fwrite($fp, json_encode($topics)); 
        fclose($fp); 
    }

    }else{
        echo "\033[31m[失敗]\033[0m" . PHP_EOL;
    }

    function println($string) {
        printT( $string . PHP_EOL );
    }

    function printT($string) {
        echo "\033[33m" . date("[Y/m/d H:i:s]") . "\033[0m " . $string;
    }

    function searchNameInArray($name, $array) {
        if(is_array($array)) {
            $i = 0;
            foreach($array as $array_data) {
                if($array_data['name'] == $name) return $i;
                $i++;
            }
        }
        return false;
    }

    function sendTelegramMessage($chat_id, $message) {
        global $telegram_api_url;

        $ch = curl_init($telegram_api_url . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($message) . "&disable_web_page_preview=true");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        $result = curl_exec($ch);
        curl_close($ch);
      
        $callback = json_decode($result, true);

        if($callback['ok'] == 'true'){
            return true;
        }else{
            return false;
        }
    }
?>