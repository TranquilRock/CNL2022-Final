<?php
require_once ("radius/dblink.php"); //set $db variable

if (
    isset($_POST["username"]) &&
    isset($_POST["password"]) &&
    isset($_POST["re-password"])
) {
    $msg = radius_register(
        $_POST["username"], $_POST["password"], $_POST["re-password"], $db
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
            <div class="flex-wrap flex-md-nowrap align-items-center">
                <h2>Register your account</h2>
            </div>
            <?php if (isset($msg) && $msg != "") { ?>
            <div class="alert alert-danger" role="alert">
                <button type="button" class="close"
                        data-dismiss="alert" aria-label="Close">
                    <span class="oi oi-x"></span>
                </button>
                <span class="alert-msg"><?=$msg?></span>
            </div>
            <?php } ?>
            <form name="radius-login" method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control"
                           name="username" id="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control"
                           name="password" id="password"
                           placeholder="Password">
                </div>
                <div class="form-group">
                    <label for="re-password">Re-type password</label>
                    <input type="password" class="form-control"
                           name="re-password" id="re-password"
                           placeholder="Re-type password">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
                <a class="btn btn-outline-primary" role="button"
                   href="login.php<?="?uamip=".$_GET['uamip']."&uamport=".$_GET['uamport']?>" >Back</a>
            </form>
        </main>

    </div></div>
    <!-- footer -->
    <?php require ("html_comp/footer.php") ?>
</body>

</html>

<?php
function radius_register (
    string $username, string $password, string $re_password, $db
    ) {
    if ($username == "" ||
        $password == "" ||
        $re_password == ""
    ) {
        return "Can't empty.";
    }

    if ($password != $re_password) {
        return "Password not match re-type";
    }

    $sql_cmd = "SELECT count(*)
                FROM `radcheck` WHERE `username` = :username;";
    $sql_result = $db->prepare($sql_cmd) or die();
    $sql_result->execute(array(':username' => $username));
    if ($sql_result->fetchColumn() != 0) {
        return "User exists, Please use other username.";
    }

    $sql_cmd = "SELECT count(*)
                FROM `radusergroup` WHERE `username` = :username;";
    $sql_result = $db->prepare($sql_cmd) or die();
    $sql_result->execute(array(':username' => $username));
    if ($sql_result->fetchColumn() != 0) {
        return "User exists, Please use other username.";
    }

    $sql_cmd = "INSERT INTO `radcheck` (`username`,`attribute`,`op`, `value`)
                VALUES (:username,'Cleartext-Password',':=',:password);
                INSERT INTO `radusergroup` (`username`,`groupname`)
                VALUES (:username,'user');
                INSERT INTO `radcheck` (`username`, `attribute`, `op`, `value`)
                VALUES (:username, 'Max-Hourly-Session', ':=', 3600);
                INSERT INTO `radcheck` (`username`, `attribute`, `op`, `value`)
                VALUES (:username, 'Max-Hourly-Traffic', ':=', 10485760);";
    $sql_result = $db->prepare($sql_cmd) or die();
    if ($sql_result->execute(array(
        ':username' => $username,
        ':password' => $password,
    ))) {
        header('Location: http://192.168.182.1:3990/prelogin', true, 302);
    }
}
?>