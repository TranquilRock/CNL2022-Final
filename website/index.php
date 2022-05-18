<?php 
require_once ("radius/dblink.php"); //set $db variable

$current_reset_time = mktime(date("H"),0,0,date("n"),date("j"),date("Y"));

$sql_cmd =
    "SELECT
        ORIG.`username`,
        ORIG.`acctstarttime` start,
        TIME.time,
        TRAF.traffic,
        ORIG.`framedipaddress` ip
    FROM `radacct` ORIG
    INNER JOIN (
        SELECT
            `username`,
            SUM(`acctoutputoctets` + `acctinputoctets`) traffic
        FROM `radacct`
        WHERE UNIX_TIMESTAMP(`acctstarttime`) > ':time'
        GROUP BY `username`
    ) TRAF ON ORIG.`username` = TRAF.`username`
    INNER JOIN (
        SELECT
            `username`,
            SUM(acctsessiontime - GREATEST((:time - UNIX_TIMESTAMP(acctstarttime)), 0))  time
        FROM `radacct`
        WHERE UNIX_TIMESTAMP(`acctstarttime`) + `acctsessiontime` > ':time'
        GROUP BY `username`
    ) TIME ON ORIG.`username` = TIME.`username`
    WHERE ORIG.`acctstoptime` IS NULL;";
$sql_result = $db->prepare($sql_cmd) or die();
$sql_result->execute(array(':time' => $current_reset_time));
$online_users = $sql_result->fetchall(PDO::FETCH_ASSOC);

$warn_rate = (90/100);

$limits = Get_User_Limit($db);

$ipaddress = get_client_ip();

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php include_once ("html_comp/header.php"); ?>
</head>

<body>
    <?php require ("html_comp/bar.php") ?>

    <div class="container-fluid"><div class="row">
        <!-- mean -->
        <?php require ("html_comp/menu.php") ?>

        <main role="main" class="col-md-10 ml-sm-auto col-lg-10">
            <?php if ($online_users) { ?>
            <header class="flex-wrap flex-md-nowrap align-items-center">
                <h2>Online User</h2>
            </header>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Start time</th>
                        <th>Session time</th>
                        <th>traffic (Down+up)</th>
                        <th>IP address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($online_users as $user) {
                        // limits time    unit  : Seconds
                        // limits traffic unit  : kb;
                        // accts  time    unit  : Seconds
                        // accts  traffic unit  : bytes

                        $warn_time = $limits[$user['username']]['time']*$warn_rate;
                        $warn_traf = $limits[$user['username']]['traf']*$warn_rate;
                    ?>
                    <tr>
                        <td>
                            <?=($ipaddress == $user["ip"])?
                                '@':''?>
                        </td>
                        <td><?=$user["username"]?></td>
                        <td><?=$user["start"]?></td>
                        <td class="<?=(
                            $warn_time < ($user["time"])
                            )? 'bg-warning':''?>" >
                            <?=gmdate("H:i:s", $user["time"])?>
                        </td>
                        <td class="<?=(
                            $warn_traf < ($user["traffic"]/1024)
                            )? 'bg-warning':''?>" >
                            <?=formatBytes($user["traffic"])?>
                        </td>
                        <td><?=$user["ip"]?></td>
                    </td>
                    <?php } ?>
                </tbody>
                </table>
            </div>
            <?php } else { ?>
                <div id="background"></div>
                    <div class="top">
                        <h1 class="no">No Online User</h1>
                    </div>
                    <div class="ghost-container">
                        <div class="ghost-copy">
                            <div class="ghost-one"></div>
                            <div class="ghost-two"></div>
                            <div class="ghost-three"></div>
                            <div class="ghost-four"></div>
                        </div>
                        <div class="ghost">
                            <div class="ghost-face">
                            <div class="ghost-eye"></div>
                            <div class="ghost-eye-right"></div>
                            <div class="ghost-mouth"></div>
                            </div>
                        </div>
                        <div class="ghost-shadow"></div>
                    </div>
            <?php } ?>
        </main>

    </div></div>
    <!-- footer -->
    <?php require ("html_comp/footer.php") ?>
</body>

</html>

<?php
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
    $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
    $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
    $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
    $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
    $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB'); 
    
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    
    $bytes /= (1 << (10 * $pow)); 
    
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

function Get_User_Limit($db) {
    $sql_cmd = "SELECT
                    `username`,
                    `groupname`
                FROM `radusergroup`;";
    $sql_result = $db->query($sql_cmd) or die();
    $users = $sql_result->fetchall(PDO::FETCH_ASSOC);

    $sql_cmd = "SELECT
                    `groupname`, `attribute`, `value`
                FROM radgroupcheck
                WHERE `attribute` = 'Max-Hourly-Traffic'
                OR `attribute` = 'Max-Hourly-Session';";
    $sql_result = $db->query($sql_cmd) or die();
    $group_limits = $sql_result->fetchall(PDO::FETCH_ASSOC);

    foreach ($group_limits as $group_limit) {
        foreach ($users as $key => $user) {
            if ( $group_limit['groupname'] == $user['groupname']) {
                $users[$key][$group_limit['attribute']] = $group_limit['value'];
            }
        }
    }

    $sql_cmd = "SELECT
                    `username`, `attribute`, `value`
                FROM radcheck
                WHERE `attribute` = 'Max-Hourly-Traffic'
                OR `attribute` = 'Max-Hourly-Session';";
    $sql_result = $db->query($sql_cmd) or die();
    $user_limits = $sql_result->fetchall(PDO::FETCH_ASSOC);

    foreach ($user_limits as $user_limit) {
        foreach ($users as $key => $user) {
            if ( $user_limit['username'] == $user['username']) {
                $users[$key][$user_limit['attribute']] = $user_limit['value'];
            }
        }
    }

    $result = array();
    foreach ($users as $user) {
        $result[$user['username']]['group'] = $user['groupname'];
        $result[$user['username']]['time']  = $user['Max-Hourly-Session'];
        $result[$user['username']]['traf']  = $user['Max-Hourly-Traffic'];
    }
    return $result;
}
?>