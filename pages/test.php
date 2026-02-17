<?php
// login-and-toggle.php

$router_ip   = '192.168.100.104';
$router_port = 8080;
$router_pass = '12serya';
$router = "http://$router_ip:$router_port";

$cookie = tempnam(sys_get_temp_dir(), 'cookie_');

function curl_post($url, $data, $cookie, $referer = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_USERAGENT      => "Mozilla/5.0",
        CURLOPT_REFERER        => $referer ?? $url,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function curl_get($url, $cookie, $referer = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_USERAGENT      => "Mozilla/5.0",
        CURLOPT_REFERER        => $referer ?? $url,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// LOGIN
$login_res = curl_post("$router/login/Auth", ["password"=>base64_encode($router_pass)], $cookie);

// GET MAC FILTER MODE
$nat_json = curl_get("$router/goform/getNAT?random=".microtime(true)."&modules=macFilter", $cookie);
$nat_config = json_decode($nat_json,true);
$filterMode = $nat_config['macFilterMode'] ?? 'whitelist';

// AJAX TOGGLE HANDLER
if(isset($_GET['toggle'])) {
    $newMode = $_GET['toggle']==='whitelist'?'whitelist':'blacklist';
    $nat_config['macFilterMode'] = $newMode;
    curl_post("$router/goform/setNAT",$nat_config,$cookie);
    echo json_encode(['status'=>'ok','mode'=>$newMode]);
    exit;
}

// GET DEVICES
$qos_json = curl_get("$router/goform/getQos?random=".microtime(true)."&modules=onlineList,blackList", $cookie);
$qos = json_decode($qos_json,true);
$online = $qos['onlineList']??[];
$black  = $qos['blackList']??[];

$devices = [];
$mac_seen = [];
foreach(array_merge($online,$black) as $dev){
    $mac = strtolower($dev['qosListMac']??'');
    if(!$mac || isset($mac_seen[$mac])) continue;
    $mac_seen[$mac]=true;
    $devices[] = [
        'hostname'=>$dev['qosListHostname']??'unknown',
        'ip'=>$dev['qosListIP']??'',
        'mac'=>$mac,
        'type'=>$dev['qosListConnectType']??'',
        'internet'=>($dev['qosListConnectType']??'')==='wired'
    ];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Router MAC Filter Dashboard</title>
    <style>
        body{font-family:Arial,sans-serif;margin:20px;}
        table{border-collapse:collapse;width:100%;margin-top:20px;}
        th,td{border:1px solid #ccc;padding:8px;text-align:left;}
        .btn{padding:6px 12px;border-radius:4px;cursor:pointer;border:none;margin-right:5px;transition:0.2s;}
        .blue{background:#007bff;color:#fff;}
        .white{background:#fff;color:#007bff;border:1px solid #007bff;}
        .btn:hover{opacity:0.8;}
        .active{font-weight:bold;}
    </style>
    <script src="https://cdn.jsdelivr.net/gh/your-reasy.js"></script>
</head>
<body>
<h2>Router Devices (MAC Filter: <span id="filterMode"><?php echo ucfirst($filterMode);?></span>)</h2>
<div>
    <button class="btn <?php echo $filterMode==='whitelist'?'blue':'white';?>" id="btnWhitelist">Whitelist</button>
    <button class="btn <?php echo $filterMode==='blacklist'?'blue':'white';?>" id="btnBlacklist">Blacklist</button>
</div>

<table>
    <thead>
        <tr>
            <th>Hostname</th>
            <th>IP</th>
            <th>MAC</th>
            <th>Connection</th>
            <th>Internet</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($devices as $d):?>
        <tr>
            <td><?php echo htmlspecialchars($d['hostname']);?></td>
            <td><?php echo htmlspecialchars($d['ip']);?></td>
            <td><?php echo htmlspecialchars($d['mac']);?></td>
            <td><?php echo htmlspecialchars($d['type']);?></td>
            <td><?php echo $d['internet']?'✅':'❌';?></td>
        </tr>
        <?php endforeach;?>
    </tbody>
</table>

<script>
REasy(function(){
    function toggleMode(mode){
        REasy.get("<?php echo basename(__FILE__);?>?toggle="+mode,function(res){
            try{
                var data = JSON.parse(res);
                if(data.status==='ok'){
                    REasy("#filterMode").text(data.mode.charAt(0).toUpperCase()+data.mode.slice(1));
                    REasy("#btnWhitelist").removeClass("blue").addClass("white");
                    REasy("#btnBlacklist").removeClass("blue").addClass("white");
                    if(data.mode==='whitelist') REasy("#btnWhitelist").removeClass("white").addClass("blue");
                    else REasy("#btnBlacklist").removeClass("white").addClass("blue");
                }
            }catch(e){console.error(e);}
        });
    }
    REasy("#btnWhitelist").click(function(){toggleMode("whitelist");});
    REasy("#btnBlacklist").click(function(){toggleMode("blacklist");});
});
</script>
</body>
</html>
