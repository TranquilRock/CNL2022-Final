<?php

$msg = res_controller($_GET['res']);

if ( isset($_GET['reply']) ) { $msg=$_GET['reply']; }

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
                <h2>Please login</h2>
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
            <form name="radius-login" method="get" action="radius/login.php">
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
                    <label for="challenge">Challenge</label>
                    <input type="text" class="form-control"
                           name="chal" id="chal" readonly
                           value="<?=$_GET['challenge']?>">
                </div>
                <div class="form-group">
                    <label for="uamip">IP address</label>
                    <input type="text"  class="form-control"
                           name="uamip"  id="uamip" readonly
                           value="<?=$_GET['uamip']?>">
                </div>
                <div class="form-group">
                    <label for="uamport">UAM Port</label>
                    <input type="text"  class="form-control"
                           name="uamport"  id="uamport" readonly
                           value="<?=$_GET['uamport']?>">
                </div>
                <div class="form-group">
                    <label for="userurl">URL</label>
                    <input type="text"  class="form-control"
                           name="userurl"  id="userurl" readonly
                           value="<?=$_GET['userurl']?>">
                </div>
                <button type="submit" class="btn btn-primary">Log in</button>
                <a class="btn btn-outline-primary" role="button"
                   href="register.php<?="?uamip=".$_GET['uamip']."&uamport=".$_GET['uamport']?>" >Register</a>
            </form>
        </main>

    </div></div>
    <!-- footer -->
    <?php require ("html_comp/footer.php") ?>
</body>

</html>

<?php
function redirect($url) {
    $prama = "?uamip=".$_GET['uamip']."&uamport=".$_GET['uamport'];
    header('Location: ' . $url.$prama, true, 302);
    exit();
}

function res_controller($res) {
    switch ($res) {
        case 'notyet':
            return "Please login.";
        case 'logoff':
            return "Logout successful.";
        case 'failed':
            return "Login failed.";
        case 'timeout':
            return "Login request timeout. Try again.";
        case 'already':
            return "This IP address already login.";
        case 'success':
            redirect("index.php");
    }
}
?>