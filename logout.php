<!DOCTYPE html>
<html>

<?php $logout_url = "http://" . $_GET['uamip'] . ":" . $_GET['uamport'] . "/logoff"; ?>
<style>
<?php
include 'lib/css/w3.css';
include 'lib/css/font-awesome.min.css';
include 'lib/css/Raleway.css';
?>
</style>

<head>
<title>Logout</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body, h1 {font-family: "Raleway", Arial, sans-serif}
h1 {letter-spacing: 6px}
.w3-row-padding img {margin-bottom: 12px}
footer {
    clear: both;
    position: relative;
    height: 50px;
    margin-top: 200px;
}
.center {
  text-align: center;
}
</style>
</head>
<body>

<!-- !PAGE CONTENT! -->
<div class="w3-content" style="max-width:1500px">

<!-- Header -->
<header class="w3-panel w3-center w3-opacity" style="padding:128px 16px">
  <h1 class="w3-xlarge">Free WiFi</h1>
  
  <div class="w3-padding-32">
    <div class="w3-bar w3-border">
      <a href="<?= $logout_url ?>" class="w3-bar-item w3-button w3-light-grey"> Logout </a>
    </div>
  </div>
</header>
<!-- End Page Content -->
</div>

<!-- Footer -->
<footer class="w3-container w3-padding-64 w3-light-grey w3-center w3-large">
  <p>Powered by <a href="https://www.w3schools.com/w3css/default.asp" target="_blank" class="w3-hover-text-red">w3.css</a></p>
</footer>

</body>
</html>
