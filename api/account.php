<?php
    require_once "modules/headers.php";
    require_once "modules/validationRegistration.php";
    require_once "modules/jwt.php";
    require_once "modules/check_birth_date.php";
    require_once "modules/check_email_duplicate.php";

    function route($method, $urlList, $requestData) {
        global $link;
        if (count($urlList) == 3) {
            switch ($method) {
                case "POST":
                    switch ($urlList[2]) {
                        case "register":
                            $fullName = $requestData->body->fullName;
                            $password = $requestData->body->password;
                            $email = $requestData->body->email;
                            $address = $requestData->body->address;
                            $gender = $requestData->body->gender;
                            $phoneNumber = $requestData->body->phoneNumber;
                            $birthDate = str_replace(["T", "Z"], " ", trim($requestData->body->birthDate));

                            if (!isValidRegistration($fullName, $password, $email, $gender, $phoneNumber, $requestData->body->birthDate)) {
                                return;
                            }
                            $password = hash("sha1", $requestData->body->password);
                            $userInsertResult = $link->query("INSERT INTO user(user_id,  full_name, birth_date, gender, telephone_number, email, address, password)
                                                                VALUES(UUID(), '$fullName', '$birthDate', '$gender', '$phoneNumber', '$email', '$address', '$password')");
                            if ($link->error) {
                                setHTTPStatus('500', $link->error);
                                break;
                            }
                            if (!$userInsertResult) {
                                if (checkEmailDuplicates($email)) {
                                    return;
                                }
                            }
                            else {
                                echo json_encode(['token' => generateToken($email)]);
                            }
                            break;
                        case "login":
                            $email = $requestData->body->email;
                            $password = hash("sha1", $requestData->body->password);
                            $user = $link->query("SELECT email FROM user WHERE email='$email' AND password='$password'");
                            if ($link->error) {
                                setHTTPStatus('500', $link->error);
                                break;
                            }
                            $user = $user->fetch_assoc();
                            if ($user) {
                                echo json_encode(['token' => generateToken($user['email'])]);
                            }
                            else {
                                setHTTPStatus('400', 'Login failed');
                            }
                            break;
                        case "logout":
                            $userID = checkAuthorize();
                            if (!$userID) {
                                setHTTPStatus('401','Token not specified or not valid');
                                return;
                            }
                            $token = substr(getallheaders()['Authorization'], 7);
                            $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'");
                            if ($link->error) {
                                setHTTPStatus('500', $link->error);
                                break;
                            }
                            $checkToken = $checkToken->fetch_assoc();
                            if ($checkToken) {
                                setHTTPStatus('409', "From this '$token' token has already logged out");
                                return;
                            }
                            $tokenInsertResult = $link->query("INSERT INTO token(token_id, user_id, token) VALUES(UUID(),'$userID', '$token')");
                            if ($link->error) {
                                setHTTPStatus('500', $link->error);
                                break;
                            }
                            if ($tokenInsertResult) {
                                $messageResult = array('token' => $token, 'message' => 'Logged Out');
                                setHTTPStatus('200', $messageResult);
                            }
                            break;
                        default:
                            setHTTPStatus('404', 'Missing resource is requested');
                            break;
                    }
                    break;
                case "GET":
                    if ($urlList[2] != "profile") {
                        setHTTPStatus('404', 'Missing resource is requested');
                        break;
                    }
                    $token = substr(getallheaders()['Authorization'], 7);
                    $isLogoutToken = $link->query("SELECT token_id FROM token WHERE token.`token`='$token'");
                    if ($link->error) {
                        setHTTPStatus('500', $link->error);
                        break;
                    }
                    $isLogoutToken = $isLogoutToken->fetch_assoc();
                    $userID = checkAuthorize();
                    if (!$userID || $isLogoutToken) {
                        setHTTPStatus('401','Token not specified or not valid');
                        return;
                    }
                    $user = $link->query("SELECT * FROM user WHERE user_id='$userID'");
                    if ($link->error) {
                        setHTTPStatus('500', $link->error);
                        break;
                    }
                    $user = $user->fetch_assoc();
                    $result = array(
                        'id' => $user['user_id'],
                        'fullName' => $user['full_name'],
                        'birthDate' => $user['birth_date'],
                        'gender' => $user['gender'],
                        'address' => $user['address'],
                        'email' => $user['email'],
                        'phoneNumber' => $user['telephone_number']
                    );
                    echo json_encode($result);

                    break;
                case "PUT":
                    if ($urlList[2] != "profile") {
                        setHTTPStatus('404', 'Missing resource is requested');
                        break;
                    }
                    $token = substr(getallheaders()['Authorization'], 7);
                    $emailFromToken = getPayload($token)['email'];
                    $isLogoutToken = $link->query("SELECT token_id FROM token WHERE token.`token`='$token'");
                    if ($link->error) {
                        setHTTPStatus('500', $link->error);
                        break;
                    }
                    $isLogoutToken = $isLogoutToken->fetch_assoc();
                    $userID = checkAuthorize();
                    if (!$userID || $isLogoutToken) {
                        setHTTPStatus('401','Token not specified or not valid');
                        return;
                    }
                    $fullName = $requestData->body->fullName;
                    $birthDate = str_replace(["T", "Z"], " ", trim($requestData->body->birthDate));
                    $gender = $requestData->body->gender;
                    $address = $requestData->body->address;
                    $phoneNumber = $requestData->body->phoneNumber;
                    if (!isValidChangeProfile($fullName, $gender, $phoneNumber)) {
                        return;
                    }
                    $userUpdateResult = $link->query("UPDATE user SET full_name='$fullName', birth_date='$birthDate', gender='$gender', address='$address' WHERE user_id='$userID'");
                    if ($link->error) {
                        setHTTPStatus('500', $link->error);
                        break;
                    }
                    if (!$userUpdateResult) {
                        if (checkEmailDuplicates($emailFromToken) || checkBirthdate($birthDate)) {
                            return;
                        }
                    }
                    break;
                default:
                    setHTTPStatus('405', "Method '$method' not allowed");
                    break;
            }
        } else {
            setHTTPStatus('404', 'Missing resource is requested');
        }
    }