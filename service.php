<?php

include("_@configs.php");

$_SERVER['HTTP_HOST'] = strtok($_SERVER['HTTP_HOST'], ':'); 
$_SERVER['HTTP_HOST'] = str_ireplace('localhost', '127.0.0.1', $_SERVER['HTTP_HOST']);
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") { $streamenvproto = "https"; } 
elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") { $streamenvproto = "https"; } 
else { $streamenvproto = "http"; } 
$plhoth = ($_SERVER['SERVER_ADDR'] !== "127.0.0.1") ? $_SERVER['HTTP_HOST'] : getHostByName(php_uname('n'));


$action = "";
if(isset($_REQUEST['action'])) { $action = trim($_REQUEST['action']); }


$tvlist = get_channels(); 

if($action == "getChannels")
{
    $outlist = array();
    if(!isset($tvlist[0])) { response("error", 404, "No Channels Found", ""); }
    foreach($tvlist as $dhdl) {
    
        $outlist[] = $dhdl;
    }
    response("success", 200, "Channels List", array("count" => count($tvlist), "list" => $outlist));
}
elseif($action == "get_detail")
{
    $id = ""; $strmServers = array();
    if(isset($_REQUEST['id'])) { $id = trim($_REQUEST['id']); }
    if(empty($id)) {
        response("error", 400, "Error: Stream or Channel Identifier Missing", "");
    }
    
    $x = getChannelServers($id); 
    
    if(empty($x)) {
        response("error", 404, "Error: Streaming Servers are Unavailable", "");
    }
    foreach($x as $rsv)
    {
        $serverSrl = implode('', array_filter(preg_split('/\D+/', $rsv['name'])));
        
        $strmServers[] = array("id" => "Server ".$serverSrl, "url" => "stream.php?id=".$id."&srv=".$serverSrl);
    }
    response("success", 200, "Streaming Servers List", array("id" => $id, "title" => get_channel_name($id), "servers" => $strmServers));
}
elseif($action == "searchChannels")
{
    $query = ""; $resdata = array();
    if(isset($_REQUEST['query'])){ $query = trim($_REQUEST['query']); }
    if(empty($query)){ response("error", 400, "Please Enter Channel Name To Search", ""); }
    if(!isset($tvlist[0])) { response("error", 404, "No Channels Exist", ""); }
    foreach($tvlist as $vtl) {
        
        if(stripos($vtl['title'], $query) !== false) { $resdata[] = $vtl; }
    }
    if(!isset($resdata[0])){ response("error", 404, "No Matching Results Found", ""); }
    response("success", 200, "Total ".count($resdata)." Results Found", array("count" => count($resdata), "query" => $query, "list" => $resdata));
}
elseif($action == "m3u_playlist")
{
    if(isset($tvlist[0]))
    {
        if($_SERVER['SERVER_PORT'] !== "80" && $_SERVER['SERVER_PORT'] !== "443") { 
            $playUrlBase = $streamenvproto."://".$plhoth.":".$_SERVER['SERVER_PORT'].str_replace(" ", "%20", str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF'])); 
        } else { 
            $playUrlBase = $streamenvproto."://".$plhoth.str_replace(" ", "%20", str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF'])); 
        }
        
        $logoURL = $playUrlBase."images/rZ_app-logo.png"; 
        $playlistData = "#EXTM3U\n";
        $c = 0;
        foreach($tvlist as $otv)
        {
            $c++;
            $name = $otv['title'];
            $logo = !empty($otv['logo']) ? $otv['logo'] : $logoURL;
            $id = $otv['id'];
            
            $playlistData .= '#EXTINF:-1 tvg-id="'.$c.'" tvg-name="'.$name.'" tvg-logo="'.$logo.'" group-title="General",'.$name."\n";
        
            $playlistData .= $playUrlBase."stream.m3u8?id=".$id."\n";
        }
        $file = str_replace(" ", "", $APP_CONFIGS['APP_NAME'])."_Playlist.m3u";
        header('Content-Disposition: attachment; filename="'.$file.'"');
        header("Content-Type: application/vnd.apple.mpegurl");
        exit($playlistData);
    }
    http_response_code(404); exit();
}
else
{
    response("error", 400, "Bad Request: Action Missing", "");
}

?>