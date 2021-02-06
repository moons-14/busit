<?php
$api_secret='yCwfdstfVtzQiZ7KXW4kxarxDM8jfLXcmVB4sTf5';

require_once "library/simple_html_dom.php";
$start = microtime(true);
if(isset($_GET['api_secret'])){
    if($_GET['api_secret']==$api_secret){
        if(isset($_GET['bus_table_id'])){
            $arr["status"] = "true";
            $bus_table_id_s = mb_strstr( $_GET['bus_table_id'], ':', true);
            $bus_table_id_l = str_replace($bus_table_id_s.':', '', $_GET['bus_table_id']);

            $URL_2 = 'https://busit.jp/trip/'.$bus_table_id_s.'?seq='.$bus_table_id_l.'#stay&lang=ja';
            $html_2 = file_get_html($URL_2);
            $arr['terminal']=$html_2->find(".terminal",0)->plaintext;
            for($m = 0; $m < 500; $m++){
                $n=$m+1;
                $iss_n=$html_2->find(".StopName",$m)->plaintext;
                if(isset($iss_n)&&$iss_n!=''){
                $arr[$n]['station_name']=$html_2->find(".StopName",$m)->plaintext;
                $arr[$n]['arrival_time']=str_replace(PHP_EOL, '', str_replace("\t", "", $html_2->find(".arv_time",$m)->plaintext));
                $str_S = str_replace('/stop/', '', $html_2->find(".bstop_name a",$m)->href);
                $arr[$n]['station_id']=$str_S;
                }else{
                    goto finish;
                }

            }
            finish:

        }else{
        if(isset($_GET['station_id'])){
            $URL = 'https://busit.jp/stop/'.$_GET['station_id'].'?lang=ja';
            $ret = @file_get_contents($URL);
            if($http_response_header[0]=='HTTP/1.1 404 Not Found'){
                $arr["status"] = "no station_id";
            }else{
                $html = file_get_html($URL);


            $arr["status"] = "true";
            
            $arr["station_name"] = str_replace(PHP_EOL, '', $html->find(".bstop_name",0)->plaintext);
            $arr["station_furi"] = str_replace(PHP_EOL, '', $html->find(".bstop_furi",0)->plaintext);
            $arr["station_mark"]= str_replace(PHP_EOL, '', $html->find(".mark",0)->plaintext);
                if(isset($_GET['count'])){
                    if($_GET['count']<11&&$_GET['count']>0){
                        $c=$_GET['count'];
                    }else{
                        $arr["count_error"]='Please specify the number of acquisitions up to 10.';
                        $c=10;
                    }
                }else{
                    $c=10;
                }
            for($i = 0; $i < $c; $i++){
                $a=$i+1; 
                if(empty($html->find(".company",$i)->find("img",0)->alt)){

                }else{
                    $arr[$a]["bus_name"]=str_replace(PHP_EOL, '', $html->find(".company",$i)->find("img",0)->alt);
                    if(strpos($html->find(".iconGroup",$i)->plaintext,'バスロケ未受信') !== false){
                        $arr[$a]["bls"]='false';

                    }else{
                        $arr[$a]["bls"]='true';
                        if(strpos($html->find(".iconGroup",$i)->plaintext,'スロープ') !== false){
                            $arr[$a]["slope"]='true';
                        }else{
                            $arr[$a]["slope"]='false';
                        }
                        if(strpos($html->find(".iconGroup",$i)->plaintext,'ノンステップ') !== false){
                            $arr[$a]["non-step"]='true';
                        }else{
                            $arr[$a]["non-step"]='false';
                        }
                        $until_arrival=str_replace(PHP_EOL, '', str_replace("\t", "", $html->find(".status",$i)->plaintext));
                        if($until_arrival=='まもなく到着'){
                        $arr[$a]["until_arrival"]='now';
                        }else{
                            
                            $arr[$a]["until_arrival"]=$until_arrival;
                        }
                    }
                    $arr[$a]["arrival_time"]=str_replace(PHP_EOL, '', str_replace("\t", "", $html->find(".time",$i)->plaintext));
                    $arr[$a]["bus_id"]=$html->find(".num",$i)->plaintext;
                    $arr[$a]["terminal"]=$html->find(".terminal",$i)->plaintext;
                    $str = str_replace('/trip/', '', $html->find(".destination a",$i)->href);
                    $trimed = substr($str, 0, strcspn($str,'?seq='));

                    $str_2=stristr($str, '?seq=');
                    $str_2 = str_replace('?seq=', '', $str_2);
                    $str_2 = substr($str_2, 0, strcspn($str_2,'#stay'));
                    
                    
                    $arr[$a]["bus_table_id"]=$trimed.':'.$str_2;
                    
                
                    
                }

            }
            }

        }else{
        $arr["status"] = "ep station_id";
        }
    }

    }else{
        $arr["status"] = "no api_secret";
    }
}else{
    $arr["status"] = "ep api_secret";
}
$end = microtime(true);
if($_GET['debug']=='password'){
    $arr["processing_time"] = ($end - $start);
}
print json_encode($arr,  JSON_UNESCAPED_UNICODE);
