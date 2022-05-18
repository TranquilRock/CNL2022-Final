<?php

$page = substr(
            $_SERVER['REQUEST_URI'],
            0, strrpos($_SERVER['REQUEST_URI'], "?") );
switch ($page) {
    case '/': case '/index.php':
        $current = 'Online User'; break;
    case '/edit_limit.php':
        $current = 'edit_limit'; break;
    default:
        $current = ''; break;
}

$nav_link = array(
    'Online User' => 'index.php?uamip='.$_GET["uamip"].'&uamport='.$_GET["uamport"],
    'Edit Limit' => 'edit_limit.php?uamip='.$_GET["uamip"].'&uamport='.$_GET["uamport"]
);

?>

<nav class="col-md-2 d-none d-md-block bg-light sidebar">
    <div class="sidebar-sticky">
        <ul class="nav flex-column">
            <?php if ($current != "") { ?>
            <?php foreach ($nav_link as $page => $url) {
                $active = ($current == $page)? 'active':'';
            ?>
            <li class="nav-item">
                <a  class="nav-link <?=$active?>"
                    href="<?=$url?>">
                    <?=$page?>
                </a>
            </li>
            <?php } ?><?php } ?>
        </ul>
    </div>
</nav>
