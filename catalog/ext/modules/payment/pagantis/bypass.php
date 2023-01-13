<?php

if ($_POST['formUrl']) {
    $destUrl = $_POST['formUrl'];
} elseif ($_POST['cancelUrl']) {
    $destUrl = $_POST['cancelUrl'];
} else {
    $destUrl = $_SERVER['HTTP_REFERER'];
}

header("Location: $destUrl");
exit;
