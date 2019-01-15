<?php
//post传输
function postUrlForCalling($url, $reqParams){
    $ch=curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$reqParams);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
//拼接模拟传输数据
function curlparams($params){
    $curlparams = '';
    foreach ($params as $key => $value) {
        $curlparams .= $key."=".$value."&";
    }
    return trim($curlparams,'&');
}
