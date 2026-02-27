<?php
error_reporting(E_ALL); 
ini_set('display_errors', 0); 
ini_set('log_errors', 1);       
ini_set('memory_limit', '512M');

function _x_auth_token_gen($s, $d)
{
    $o = ""; $k = $i = "VzoOJbjoszSmAy9i"; 
    if($s == 0x01) {
        $e = openssl_encrypt($d, "AES-128-CBC", $k, OPENSSL_RAW_DATA, $i);
        if(!empty($e)) { $o = bin2hex($e); }
    }
    if($s == 0x02) {
        if(strlen($d) % 2 == 0 && ctype_xdigit($d)) {
            $b = hex2bin($d);
            $dc = openssl_decrypt($b, "AES-128-CBC", $k, OPENSSL_RAW_DATA, $i);
            if(!empty($dc)) { $o = $dc; }
        }
    }
    return $o;
}

$APP_CONFIGS = array();
$APP_CONFIGS['APP_NAME'] = "Sliv";
$APP_CONFIGS['APP_POWEREDBY'] = "CodeCrafter";
$APP_CONFIGS['APP_FAVICON'] = "https://www.sonyliv.com/assets/favicon.png";
$APP_CONFIGS['APP_LOGO'] = "https://www.sonyliv.com/favicon.ico";
$APP_CONFIGS['CHANNEL_LOGO'] = "https://www.sonyliv.com/favicon.ico"; 

$APP_CONFIGS['__CACHE_PTR'] = "1078b1d5bcc1ced86624a1279aaa031a48f58969fe6116eaaccbc5ce89080ddd"; 

$APP_CONFIGS['APP_DATA_FOLDER'] = "_AppData_";
$APP_CONFIGS['USE_PROXY'] = "OFF"; 
$APP_CONFIGS['PROXY_CONF'] = array("HOST" => "", "PORT" => "", "USER" => "", "PASS" => "");

if(!is_dir($APP_CONFIGS['APP_DATA_FOLDER'])) { @mkdir($APP_CONFIGS['APP_DATA_FOLDER'], 0777, true); }
if(!file_exists($APP_CONFIGS['APP_DATA_FOLDER']."/.htaccess")) { @file_put_contents($APP_CONFIGS['APP_DATA_FOLDER']."/.htaccess", "deny from all"); }
header("Access-Control-Allow-Origin: *");
header("X-Powered-By: " . $APP_CONFIGS['APP_POWEREDBY']);

function response($status, $code, $message, $data)
{
    header("Content-Type: application/json");
    exit(json_encode(array("status" => $status, "code" => $code, "message" => $message, "data" => $data)));
}

function getRequest($url)
{
    global $APP_CONFIGS;
    $headers = array("User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if($APP_CONFIGS['USE_PROXY'] == "ON") {
        curl_setopt($ch, CURLOPT_PROXY, $APP_CONFIGS['PROXY_CONF']['HOST']);
        curl_setopt($ch, CURLOPT_PROXYPORT, $APP_CONFIGS['PROXY_CONF']['PORT']);
        $auth = $APP_CONFIGS['PROXY_CONF']['USER'].":".$APP_CONFIGS['PROXY_CONF']['PASS'];
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
    }

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function resolve_url($base, $rel) {
    if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
    if ($rel[0] == '#' || $rel[0] == '?') return $base . $rel;
    extract(parse_url($base));
    $path = preg_replace('#/[^/]*$#', '', $path);
    if ($rel[0] == '/') $path = '';
    $abs = "$host$path/$rel";
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for($n=1; $n>0; $abs=preg_replace($re, '/', $abs, -1, $n)) {}
    return $scheme . '://' . $abs;
}

function get_main_stream($id) {
    global $APP_CONFIGS;
    
    $safe_id = str_replace(array('/', '\\'), '_', $id);
    $cache_file = $APP_CONFIGS['APP_DATA_FOLDER'] . "/Slive_StreamUrl_{$safe_id}.cr";
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['expire']) && time() < $data['expire']) { 
            return _x_auth_token_gen(0x02, $data['url']); 
        }
    }

    $api_endpoint = _x_auth_token_gen(0x02, $APP_CONFIGS['__CACHE_PTR']) . "/api.php?id=" . $id;
    $json = getRequest($api_endpoint);
    $res = json_decode($json, true);

    if (isset($res['url']) && !empty($res['url'])) {
        $encrypted_url = _x_auth_token_gen(0x01, $res['url']);
        
        $saveData = array(
            "url" => $encrypted_url, 
            "expire" => time() + (5 * 60) 
        );
        
        file_put_contents($cache_file, json_encode($saveData));
        return $res['url'];
    }
    return false;
}

function get_channels() {
    global $APP_CONFIGS;
    $cache_file = $APP_CONFIGS['APP_DATA_FOLDER'] . "/Slive_ChannelList.cr";

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && isset($data['expire']) && time() < $data['expire']) {
            $decrypted_list = _x_auth_token_gen(0x02, $data['list']);
            return json_decode($decrypted_list, true);
        }
    }

    $json = getRequest(_x_auth_token_gen(0x02, $APP_CONFIGS['__CACHE_PTR']) . "/api.php?action=channels");
    $res = json_decode($json, true);
    
    $formatted_list = array();

    if (isset($res['channels']) && !empty($res['channels'])) {
        foreach($res['channels'] as $ch) {
            $formatted_list[] = array(
                "id"    => $ch['id'],
                "title" => $ch['name'],
                "logo"  => $ch['logo'],
                "group" => "General"
            );
        }
        
        $encrypted_list = _x_auth_token_gen(0x01, json_encode($formatted_list));
        $saveData = array("list" => $encrypted_list, "expire" => time() + (15 * 60));
        file_put_contents($cache_file, json_encode($saveData));
        
        return $formatted_list;
    }
    return array();
}

function get_channel_name($id) {
    $channels = get_channels();
    foreach($channels as $ch) {
        if($ch['id'] == $id) { return $ch['title']; }
    }
    return "Unknown Channel";
}

function getChannelServers($id) {
    return array(array("name" => "Server 1"));
}

function get_special_event_servers($id) { return null; }

?>