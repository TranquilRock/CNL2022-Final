<?php include_once("debug/header.php"); ?>
<?php

// $msg = res_controller($_GET['res']);

if (isset($_GET['reply'])) {
    $msg = $_GET['reply'];
}

// function redirect($url)
// {
//     $prama = "?uamip=" . $_GET['uamip'] . "&uamport=" . $_GET['uamport'];
//     header('Location: ' . $url . $prama, true, 302);
//     exit();
// }

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
            // redirect("index.php");
            return 'success';
    }
}
?>
<!-- ============================================================================= -->


<!DOCTYPE html>
<html lang="en">

<head>
    <?php include_once("html_comp/header.php"); ?>
    <?php include_once("debug/header.php"); ?>
</head>

<body>
    <?php require("html_comp/bar.php") ?>
    <div class="container-fluid">
        <div class="row">
            
            <!-- mean -->
            <!-- <?php require("html_comp/menu.php") ?> -->

            <main  role="main">
                <div class="buttonHeader">
                    <h2>Select Login Method</h2>
                </div>

                <?php if (isset($msg) && $msg != "") { ?>
                    <div class="alert alert-danger" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span class="oi oi-x"></span>
                        </button>
                        <span class="alert-msg"><?= $msg ?></span>
                    </div>
                <?php } ?>

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
                <button id="nfcButton"><img class="button" src="./lib/images/nfc.png"/></button>
                <button id="qrButton"><img class="button" src="./lib/images/qrcode.png"/></button>
            </div>
            <div class="row">
                <div class="col">
                    <div style="width:1000px;" id="reader"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <?php require("html_comp/footer.php") ?>
</body>

<script src="lib/js/html5-qrcode.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/blueimp-md5/2.10.0/js/md5.js"></script>
<script>
    var cameraId = null;
    Html5Qrcode.getCameras().then(devices => {
        console.log(devices);
        var flag = true;
        for (i in devices) {
            device = devices[i]
            if (device['label'].toLowerCase().includes('rear') || device['label'].toLowerCase().includes('back')) {
                cameraId = device.id;
                flag = false;
            }
        }
        if (flag) {
            cameraId = devices[0].id;
        }
    });

    Login = document.getElementById('Login');
    password = document.getElementById('password');
    username = document.getElementById('username');

    nfcButton = document.getElementById('nfcButton');
    nfcButton.addEventListener("click", async () => {
        try {
            const ndef = new NDEFReader();
            await ndef.scan();
            ndef.addEventListener("reading", ({
                message,
                serialNumber
            }) => {
                alert(`> Serial Number: ${serialNumber} md5 value: ${md5Value}`);
                username.value = serialNumber;
                password.value = serialNumber;
                Login.click();
            });
        } catch (error) {
            console.log(error);
        }
    });

    qrButton = document.getElementById('qrButton');
    qrButton.addEventListener("click", async () => {
        if (cameraId == null) {
            setTimeout(() => {
                qrButton.click();
            }, 1000);
            return;
        }
        const html5QrCode = new Html5Qrcode("reader", true);
        html5QrCode.start(
                cameraId, {
                    fps: 10,
                },
                (decodedText, decodedResult) => {
                    html5QrCode.stop().then((ignore) => {
                        alert(decodedText);
                        username.value = decodedText;
                        password.value = decodedText;
                        Login.click();
                    }).catch((err) => {
                        console.log(err);
                    });
                },
                (errorMessage) => {
                    console.log(errorMessage);
                })
            .catch((err) => {
                console.log(err);
            });
    });
</script>

</html>