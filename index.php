<?php
include("_@configs.php");

$channelID = "";
if(isset($_GET['watch'])) { $channelID = trim($_GET['watch']); }


if(!empty($channelID)) {

    $tvplayurl = "stream.m3u8?id=".$channelID."";
    $tvname = "Player"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tvname ?> - <?= $APP_CONFIGS['APP_NAME']; ?> | <?= $APP_CONFIGS['APP_POWEREDBY']; ?></title>
    <link rel="icon" href="<?= $APP_CONFIGS['APP_FAVICON']; ?>"/>
    <link rel="stylesheet" type="text/css" href="assets/clap.css?v=1"/>
    <script src="https://cdn.jsdelivr.net/clappr/latest/clappr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/clappr.level-selector/latest/level-selector.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@clappr/hlsjs-playback@1.0.1/dist/hlsjs-playback.min.js"></script>
    <style>body { margin:0; background-color:#000; }</style>
</head>
<body>
<div id="player" style="height: 100vh; width: 100%;"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
var player = new Clappr.Player({
    source: '<?= $tvplayurl; ?>',
    width: '100%',
    height: '100%',
    autoPlay: true,
    plugins: [HlsjsPlayback, LevelSelector],
    mimeType: "application/x-mpegURL",
    mediacontrol: { seekbar: "#ff0000", buttons: "#eee" },
    parentId: "#player",
});
</script>
</body>
</html>
<?php exit; } ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Home - <?= $APP_CONFIGS['APP_NAME']; ?> | <?= $APP_CONFIGS['APP_POWEREDBY']; ?></title>
<link rel="icon" href="<?= $APP_CONFIGS['APP_FAVICON']; ?>"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" />

<style>
body { font-family: "Montserrat", sans-serif; background-color: black; }
.card { color: #fff; border-radius:2rem .3rem; background-color: #202020; text-align:center; border:2px solid black; transition:0.2s;}
.card:hover { background-color: rgba(165,42,42,0.5); border-color:white; }
.card a { text-decoration:none; color:#fff; }
.tvimage { border-radius: 28px; }
.boldbtn { font-weight:bold; }
.prvselect { user-select:none; }
.toast-body { font-weight:bold; color:#067964; }
</style>
</head>
<body>

<nav class="navbar bg-body-dark">
  <div class="container-fluid">
    <a class="navbar-brand mt-4 mb-2 px-5">
      <img class="navbar-brand-logo" src="<?= $APP_CONFIGS['APP_LOGO']; ?>" 
           alt="<?= $APP_CONFIGS['APP_NAME']; ?>" style="width:80px;height:auto;">
    </a>
    <form class="d-flex" role="search"></form>
  </div>
</nav>

<div class="card bg-body-secondary mt-2 mb-4 px-4 pt-3 pb-3">
  <div class="input-group">
    <input type="text" class="form-control" placeholder="Type Channel Name To Search ..." id="inpSearchTV" autocomplete="off"/>
    <button class="btn btn-success boldbtn" type="button" id="btnInitTVSearch" title="Search Channels"><i class="fa-solid fa-magnifying-glass"></i></button>
    
  </div>
</div>

<div class="container">
  <div align="center" class="mt-4" id="tvsGrid">
    <div style="margin-top:150px;">
      <div class="spinner-border text-light" style="width:3rem;height:3rem;" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>
  </div>
</div>

<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
$(document).ready(function(){
    loadTVlist();
});


$("#btnIPTVlist").on("click", function(){
    window.location = "service.php?action=getPlaylist";
});


$("#btnInitTVSearch").on("click", function() {
    searchTVlist();
});
$("#inpSearchTV").on('keydown', function(e) {
    if(e.key==='Enter' || e.keyCode===13){ e.preventDefault(); searchTVlist(); }
});


function toaster(text){
    $(".toast-container").html(`<div class="toast" role="alert" aria-live="assertive" aria-atomic="true"><div class="toast-body">`+text+`</div></div>`);
    $(".toast").toast("show");
}


function loadTVlist(){
    toaster("Loading Channels. Please Wait...");
    $.ajax({
        url:"service.php",
        type:"POST",
        data:"action=getChannels",
        success:function(data){
            try{ data = JSON.parse(data); }catch(err){}
            if(data.status=="success"){
                renderChannelGrid(data.data.list);
            } else {
                toaster("Error: "+data.message);
                $("#tvsGrid").html('<div class="text-white" style="margin:100px;">No Channels Found</div>');
            }
        },
        error:function(){
            toaster("Server Error or Network Failed");
            $("#tvsGrid").html('<div class="text-white" style="margin:100px;">Server Error</div>');
        }
    });
}


function renderChannelGrid(channels){
    if(channels.length==0){
        $("#tvsGrid").html('<div class="text-white" style="margin:100px;">No Channels Available</div>');
        return;
    }
    let lmtl='<div class="row mt-3">';
    $.each(channels,function(k,v){
        lmtl+='<div class="col-lg-2 col-md-6 col-sm-6 col-6 mb-4" data-tvid="'+v.id+'" onclick="playlivetv(this)" title="Watch '+v.title+' Live">';
        lmtl+='<div class="card"><div class="card-body">';
        lmtl+='<img class="card-img-top tvimage" src="'+v.logo+'" onerror="this.onerror=null;this.src=\'https://aynaott.com/assets/images/logo/logo-2.png\';" width="160" height="90" alt="Logo"/>';
        lmtl+='<div class="mt-2 prvselect"><b>'+v.title+'</b></div>';
        lmtl+='</div></div></div>';
    });
    lmtl+='</div>';
    $("#tvsGrid").html(lmtl);
}

function playlivetv(e){
    let tv_id = $(e).attr("data-tvid");
    window.location="?watch="+tv_id;
}


function searchTVlist(){
    let query=$("#inpSearchTV").val().trim().toLowerCase();
    if(query.length<1){ loadTVlist(); return; }
    $.ajax({
        url:"service.php",
        type:"POST",
        data:{action:"searchChannels",query:query},
        success:function(data){
            try{ data = JSON.parse(data); }catch(err){}
            if(data.status=="success"){
                renderChannelGrid(data.data.list);
                toaster("Displaying Search Results: "+data.data.query);
            } else {
                toaster("No Results Found");
                loadTVlist();
            }
        },
        error:function(){
            toaster("Server Error or Network Failed");
            loadTVlist();
        }
    });
}
</script>

</body>
</html>
