<?php
    include_once('simple_html_dom/simple_html_dom.php');

    ini_set('user_agent','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36');

    function getCompetitionTopics($topic_url) {
        $topics = array();
        $html = file_get_html($topic_url);
        if($html) {
            $topic_div = $html->find('div.group-list.file-download-multiple .in .ct .in ul li');
            foreach($topic_div as $element) {
                $name = $element->find('.hd .in div span a[title]', 0)->plaintext;
                if($name != "" and isset($name)) {
                    $files = $element->find('.ct .in ul li span a[target=_blank]');
                    $index = array_push($topics, array("name"=>$name, "files"=>array())) - 1;
                    foreach($files as $file) {
                        $file_type = $file->plaintext;
                        $file_link = $file->href;
                        array_push($topics[$index]['files'], array("type" => $file_type, "link" => $file_link));
                    }
                }
            }
            return $topics;
        }
        return false;
    }
?>