<?php 
require_once ("radius/dblink.php"); //set $db variable

$users = Get_User_Limit($db);

if ( isset($_POST["username"]) ) {
    if ( (!isset($_POST["time"])) || $_POST["time"] == "" ) {
        $_POST["time"] = 3600;
    }
    echo $_POST["traffic"];
    if ( (!isset($_POST["traffic"])) || $_POST["traffic"] == "" ) {
        $_POST["traffic"] = 10485760;
    }
    $msg = radius_edit_limit(
        $_POST["username"], $_POST["time"], $_POST["traffic"], $db
    );
}

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
            <header class="flex-wrap flex-md-nowrap align-items-center">
                <h2>Users</h2>
            </header>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Group</th>
                        <th>Time limit</th>
                        <th>Traffic limit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $name => $user) { 
                    ?>
                    <tr>
                        <td><?=$name?></td>
                        <td><?=$user["group"]?></td>
                        <td><?=sec_to_time($user["time"])?></td>
                        <td><?=formatBytes($user["traf"])?></td>
                    </td>
                    <?php } ?>
                </tbody>
                </table>
            </div>
            <header class="flex-wrap flex-md-nowrap align-items-center">
                <h2>Edit</h2>
            </header>
            <form name="edit_limit" method="post"
                action="admin.php?uamip=<?=$_GET['uamip']?>&uamport=<?=$_GET['uamport']?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control"
                           name="username" id="username">
                </div>
                <div class="form-group">
                    <label for="time">Time limit</label>
                    <input type="text" class="form-control"
                           name="time" id="time" >
                </div>
                <div class="form-group">
                    <label for="traffic">Traffic limit</label>
                    <input type="text" class="form-control"
                           name="traffic" id="traffic">
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </main>

    </div></div>
    <!-- footer -->
    <?php require ("html_comp/footer.php") ?>
</body>

</html>


<?php
function sec_to_time($sec){
    $day = floor($sec/86400);
    $text = ($day == 0)? '':$day.':';
    return $text.gmdate("H:i:s", $sec);
}

function formatBytes($bytes, $precision = 2) { 
    $units = array( 'KB', 'MB', 'GB', 'TB', 'PB'); 
    
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

function radius_edit_limit (
    string $username, $time, $traffic, $db
    ) {
    if ($username == "") { return "Username can't empty."; }

    radius_update_limit( $username, 'Max-Hourly-Session', $time, $db);
    radius_update_limit( $username, 'Max-Hourly-Traffic', $traffic, $db);
    header('Location: admin.php?uamip='.$_GET["uamip"].'&uamport='.$_GET["uamport"], true, 302);
}

function radius_update_limit (
    string $username, $attribute, $value, $db
    ) {
    $sql_cmd = "SELECT count(*)
                FROM `radcheck`
                WHERE `username`  = :username
                  AND `attribute` = :attribute;";
    $sql_result = $db->prepare($sql_cmd) or die();
    $sql_result->execute(array(
        ':username' => $username,
        ':attribute' => $attribute
    ));
    if ($sql_result->fetchColumn() == 0) {
        $sql_cmd = "INSERT INTO `radcheck` (`username`, `attribute`, `op`, `value`)
                    VALUES (:username, :attribute, ':=', :traffic);";
    } else {
        $sql_cmd = "UPDATE `radcheck`
                    SET `value` = :traffic
                    WHERE `username`  = :username
                      AND `attribute` = :attribute;";
    }
    $sql_result = $db->prepare($sql_cmd) or die();
    $sql_result->execute( array(
        ':username' => $username,
        ':attribute' => $attribute,
        ':traffic'=> $value
    ));
}
?>