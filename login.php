<?php

$msg = res_controller($_GET['res']);

if (isset($_GET['reply'])) {
    $msg = $_GET['reply'];
}
function redirect($url)
{
    $prama = "?uamip=" . $_GET['uamip'] . "&uamport=" . $_GET['uamport'];
    header('Location: ' . $url . $prama, true, 302);
    exit();
}

function res_controller($res)
{
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
            redirect("logout.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php include_once("debug/header.php"); ?>
    <?php include_once("html_comp/header.php"); ?>
</head>

<body>
    <?php require("html_comp/bar.php") ?>
    <div class="container-fluid">
        <div class="row">

            <?php require("html_comp/menu.php") ?>

            <main role="main">
                <div class="buttonHeader">
                    <h2 id='ggh2'>Select Login Method</h2>
                </div>
                <form name="radius-login" id="loginframe" method="get" action="process_login.php" hidden>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" name="username" id="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                    </div>

                    <div class="form-group" hidden>
                        <label for="challenge">Challenge</label>
                        <input type="text" class="form-control" name="chal" id="chal" readonly value="<?= $_GET['challenge'] ?>">

                        <label for="uamip">IP address</label>
                        <input type="text" class="form-control" name="uamip" id="uamip" readonly value="<?= $_GET['uamip'] ?>">

                        <div class="form-group">
                            <label for="uamport">UAM Port</label>
                            <input type="text" class="form-control" name="uamport" id="uamport" readonly value="<?= $_GET['uamport'] ?>">
                        </div>

                        <div class="form-group">
                            <label for="userurl">URL</label>
                            <input type="text" class="form-control" name="userurl" id="userurl" readonly value="<?= $_GET['userurl'] ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="Login">Log in</button>
                </form>

            </main>
            <img id="loading" />
            <div class="row1">
                <div class="col1">
                    <div id="reader"></div>
                </div>
            </div>
            <div class="buttons">
                <button id="scanning" hidden><img src="./lib/images/scanning.gif" /></button>
                <button id="nfcButton" hidden><img class="button" src="./lib/images/nfc.png" /></button>
                <button id="qrButton" hidden><img class="button" src="./lib/images/qrcode.png" /></button>
            </div>
            <button id="typeinButton" hidden><img class="button" src="./lib/images/typing.png" /></button>

        </div>
    </div>

    <?php require("html_comp/footer.php") ?>
</body>

<script src="lib/js/html5-qrcode.min.js"></script>
<script src="lib/js/md5.js"></script>
<script src="login.js"></script>

</html>