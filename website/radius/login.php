<?php
$uamsecret = "cnlab2018";

$hexchal = pack ("H32", $_GET['chal']);
$newchal = pack("H*", md5($hexchal . $uamsecret));

$response = md5("\0" . $_GET['password'] . $newchal);

print implode('', array(
    '<meta http-equiv="refresh" content="0;',
    'url=http://', $_GET['uamip'], ':', $_GET['uamport'], '/logon',
    '?username=', $_GET['username'], '&response=', $response,
    '&userurl=', $_GET['userurl'], '">'
));

?>