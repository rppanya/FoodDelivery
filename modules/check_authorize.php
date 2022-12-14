<?php

    require_once "modules/jwt.php";

function checkAuthorize() {
    global $link;
    $token = substr(getallheaders()['Authorization'], 7);
    if (!isValid($token) || isExpired($token)) {
        return null;
    }
    $emailFromToken = getPayload($token)['email'];
    $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'");
    if ($link->error) {
        setHTTPStatus('500', $link->error);
        exit;
    }
    $user = $user->fetch_assoc();
    $userID = $user['user_id'];
    $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'");
    if ($link->error) {
        setHTTPStatus('500', $link->error);
        exit;
    }
    $checkToken = $checkToken->fetch_assoc();
    if (!$checkToken) {
        return $userID;
    }
    return null;
}
