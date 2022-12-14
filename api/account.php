<?php
require_once "helpers/headers.php";
require_once "helpers/validation_user_data.php";
require_once "helpers/jwt.php";
require_once "helpers/check_date_time.php";
require_once "helpers/check_email_duplicate.php";
require_once "helpers/check_authorize.php";

function route($method, $urlList, $requestData)
{
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
                        if (!$userInsertResult) {
                            if (checkEmailDuplicates($email)) {
                                return;
                            }
                        }
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        } else {
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
                        } else {
                            setHTTPStatus('400', 'Login failed');
                        }
                        break;
                    case "logout":
                        $userID = checkAuthorize();
                        if (!$userID) {
                            setHTTPStatus('401', 'Token not specified or not valid');
                            return;
                        }
                        $token = substr(getallheaders()['Authorization'], 7);

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
                if ($urlList[2] == "register" || $urlList[2] == "login" || $urlList[2] == "logout") {
                    setHTTPStatus('405', "Method '$method' not allowed");
                    break;
                }
                if ($urlList[2] != "profile") {
                    setHTTPStatus('404', 'Missing resource is requested');
                    break;
                }
                $userID = checkAuthorize();
                if (!$userID) {
                    setHTTPStatus('401', 'Token not specified or not valid');
                    return;
                }
                $user = $link->query("SELECT * FROM user WHERE user_id='$userID'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $user = $user->fetch_assoc();
                $birthDate = str_replace(' ', '-', $user['birth_date']);
                $birthDate = $birthDate . "T00:00:00.000Z";
                $result = array(
                    'id' => $user['user_id'],
                    'fullName' => $user['full_name'],
                    'birthDate' => $birthDate,
                    'gender' => $user['gender'],
                    'address' => $user['address'],
                    'email' => $user['email'],
                    'phoneNumber' => $user['telephone_number']
                );
                echo json_encode($result);

                break;
            case "PUT":
                if ($urlList[2] == "register" || $urlList[2] == "login" || $urlList[2] == "logout") {
                    setHTTPStatus('405', "Method '$method' not allowed");
                    break;
                }
                if ($urlList[2] != "profile") {
                    setHTTPStatus('404', 'Missing resource is requested');
                    break;
                }
                $userID = checkAuthorize();
                if (!$userID) {
                    setHTTPStatus('401', 'Token not specified or not valid');
                    return;
                }

                $token = substr(getallheaders()['Authorization'], 7);
                $emailFromToken = getPayload($token)['email'];
                $fullName = $requestData->body->fullName;
                $birthDate = str_replace(["T", "Z"], " ", trim($requestData->body->birthDate));
                $gender = $requestData->body->gender;
                $address = $requestData->body->address;
                $phoneNumber = $requestData->body->phoneNumber;
                if (!isValidChangeProfile($fullName, $gender, $phoneNumber, $requestData->body->birthDate)) {
                    return;
                }
                $userUpdateResult = $link->query("UPDATE user SET full_name='$fullName', birth_date='$birthDate', gender='$gender', address='$address', telephone_number='$phoneNumber' WHERE user_id='$userID'");
                if (!$userUpdateResult) {
                    if (checkEmailDuplicates($emailFromToken)) {
                        return;
                    }
                }
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
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