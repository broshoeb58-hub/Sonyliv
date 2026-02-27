<?php

include("_@configs.php");

function build_absolute_url($sLb, $relative) {
    if (strpos($relative, 'http://') === 0 || strpos($relative, 'https://') === 0) {
        return $relative;
    }
    $sLpb = parse_url($sLb);
    $sLb_url = $sLpb['scheme'] . '://' . $sLpb['host'];
    if (isset($sLpb['port'])) {
        $sLb_url .= ':' . $sLpb['port'];
    }
    if (strpos($relative, '/') === 0) {
        return $sLb_url . $relative;
    }
    $path = isset($sLpb['path']) ? $sLpb['path'] : '/';
    $dir = str_replace('\\', '/', dirname($path));
    if ($dir === '/') {
        $dir = '';
    }
    return $sLb_url . $dir . '/' . $relative;
}

if(stripos($_SERVER['REQUEST_URI'], ".php?") !== false){ 
    $HLS_EXT = ".php"; $TS_EXT = ".php"; 
} else { 
    $HLS_EXT = ".m3u8"; $TS_EXT = ".ts"; 
}
$CSTRMFILE = str_replace(".php", "", basename($_SERVER['SCRIPT_NAME']));

$id = isset($_REQUEST['id']) ? trim($_REQUEST['id']) : '';
$chunks = isset($_REQUEST['chunks']) ? _x_auth_token_gen(0x02, $_REQUEST['chunks']) : '';
$segment = isset($_REQUEST['segment']) ? _x_auth_token_gen(0x02, $_REQUEST['segment']) : '';

if(!empty($segment))
{
    if(filter_var($segment, FILTER_VALIDATE_URL)) {
        header("Content-Type: video/mp2t");
        echo getRequest($segment);
    } else {
        http_response_code(404);
        echo "Segment Error";
    }
    exit();
}

if(!empty($id))
{
    $sL_hlsUrl = (!empty($chunks)) ? $chunks : get_main_stream($id);
    
    if(!$sL_hlsUrl) { 
        response("error", 404, "Stream Not Found or API Invalid", null); 
    }

    $sL_codecrafter = getRequest($sL_hlsUrl);
    $sLbUrl = $sL_hlsUrl;

    if(stripos($sL_codecrafter, "#EXTM3U") !== false)
    {
        header("Content-Type: application/vnd.apple.mpegurl");
        header("Content-Disposition: inline; filename=stream.m3u8");

        $lines = explode("\n", $sL_codecrafter);
        foreach($lines as $line)
        {
            $line = trim($line);
            if(empty($line)) continue;

            if(strpos($line, "#") === 0) {
                if(preg_match('/URI="([^"]+)"/', $line, $matches)) {
                    $sL_ORGUrl = $matches[1];
                    $sL_FLLUrl = build_absolute_url($sLbUrl, $sL_ORGUrl);
                    $sL_enCUrl = _x_auth_token_gen(0x01, $sL_FLLUrl);
                    
                    if(stripos($sL_ORGUrl, ".m3u8") !== false) {
                        $new_uri = "$CSTRMFILE$HLS_EXT?id=$id&chunks=$sL_enCUrl";
                    } else {
                        $new_uri = "$CSTRMFILE$TS_EXT?id=$id&segment=$sL_enCUrl";
                    }
                    $line = str_replace('URI="' . $sL_ORGUrl . '"', 'URI="' . $new_uri . '"', $line);
                }
                echo $line . "\n";
            } else {
        
                $sL_FLLUrl = build_absolute_url($sLbUrl, $line);
                $sL_enCUrl = _x_auth_token_gen(0x01, $sL_FLLUrl);
                
                if(stripos($line, ".m3u8") !== false) {
                    echo "$CSTRMFILE$HLS_EXT?id=$id&chunks=$sL_enCUrl\n";
                } elseif(stripos($line, ".ts") !== false) {
                    echo "$CSTRMFILE$TS_EXT?id=$id&segment=$sL_enCUrl\n";
                } else {
                    echo "$CSTRMFILE$TS_EXT?id=$id&segment=$sL_enCUrl\n";
                }
            }
        }
        exit();
    }
    else {
        echo $sL_codecrafter;
        exit();
    }
}

response("error", 400, "Bad Request", null);

?>