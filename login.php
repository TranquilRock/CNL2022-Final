<!DOCTYPE html>
<html lang="en">

<head>
    <?php include_once("debug/header.php"); ?>
    <?php include_once("html_comp/header.php"); ?>
    <?php include_once("debug/header.php"); ?>
</head>

<body>
    <?php require("html_comp/bar.php") ?>
    <div class="container-fluid">
        <div class="row">

            <?php require("html_comp/menu.php") ?>

            <main role="main">
                <div class="buttonHeader">
                    <h2>Select Login Method</h2>
                </div>

                <form name="radius-login" method="get" action="process_login.php" hidden>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" name="username" id="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password">
                    </div>

                    <div class="form-group">
                        <label for="challenge">Challenge</label>
                        <input type="text" class="form-control" name="chal" id="chal" readonly value="<?= $_GET['challenge'] ?>">
                    </div>
                    <div class="form-group">
                        <label for="uamip">IP address</label>
                        <input type="text" class="form-control" name="uamip" id="uamip" readonly value="<?= $_GET['uamip'] ?>">
                        <div class="form-group">
                            <label for="uamport">UAM Port</label>
                            <input type="text" class="form-control" name="uamport" id="uamport" readonly value="<?= $_GET['uamport'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="userurl">URL</label>
                            <input type="text" class="form-control" name="userurl" id="userurl" readonly value="<?= $_GET['userurl'] ?>">
                        </div> -->
                        <button type="submit" class="btn btn-primary" id="Login">Log in</button>
                </form>

            </main>
            <img>

            <div class="buttons">
                <button id="nfcButton"><img class="button" src="./lib/images/nfc.png" /></button>
                <button id="qrButton"><img class="button" src="./lib/images/qrcode.png" /></button>
            </div>

            <div class="row">
                <div class="col">
                    <div style="width:1000px;" id="reader"></div>
                </div>
            </div>
        </div>
    </div>

    <?php require("html_comp/footer.php") ?>
</body>

<script src="lib/js/html5-qrcode.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.10.0/js/md5.js"></script>
<script src="login.js"></script>

</html>