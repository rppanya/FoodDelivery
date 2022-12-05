<?php
    require_once "modules/headers.php";

    function route($method, $urlList, $requestData) {
        global $link;
        switch ($method) {
            case "GET":
                switch (count($urlList)) {
                    case 3:
                        $token = substr(getallheaders()['Authorization'], 7);
                        $emailFromToken = getPayload($token)['email'];
                        $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
                        $userID = $user['user_id'];
                        $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
                        if ($checkToken) {
                            setHTTPStatus('401','Token not specified or not valid');
                            return;
                        }
                        $orderInfo = $link->query("SELECT * FROM `_order` WHERE order_id='$urlList[2]'")->fetch_assoc();
                        if (!$orderInfo) {
                            setHTTPStatus('404', "Order with id = '$urlList[2]' not found");
                            return;
                        }
                        if ($userID != $orderInfo['user_id']) {
                            setHTTPStatus('403', 'No access to this order');
                            return;
                        }
                        $result = [
                            'id' => $orderInfo['order_id'],
                            'deliveryTime' => $orderInfo['delivery_time'],
                            'orderTime' => $orderInfo['order_time'],
                            'status' => $orderInfo['status'],
                            'price' => $orderInfo['price'],
                            'dishes' => [],
                            'address' => $orderInfo['address']
                        ];
                        echo json_encode($result);
                        break;
                    case 2:
                        switch($method) {
                            case 'GET':
                                $token = substr(getallheaders()['Authorization'], 7);
                                $emailFromToken = getPayload($token)['email'];
                                $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
                                $userID = $user['user_id'];
                                $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
                                if ($checkToken) {
                                    setHTTPStatus('401','Token not specified or not valid');
                                    return;
                                }
                                $orders = $link->query("SELECT * FROM `_order` WHERE user_id='$userID'");
                                $result = [];
                                foreach ($orders as $order) {
                                    $result[] = [
                                        'id' => $order['order_id'],
                                        'deliveryTime' => $order['delivery_time'],
                                        'orderTime' => $order['order_time'],
                                        'status' => $order['status'],
                                        'price' => $order['price']
                                    ];
                                }
                                echo json_encode($result);
                                break;
                            case 'POST':
                                $token = substr(getallheaders()['Authorization'], 7);
                                $emailFromToken = getPayload($token)['email'];
                                $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
                                $userID = $user['user_id'];
                                $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
                                if ($checkToken) {
                                    setHTTPStatus('401','Token not specified or not valid');
                                    return;
                                }
                                $dishesFromBasket = $link->query("SELECT * FROM dish_basket WHERE user_id='$userID'");

                                break;
                        }
                        break;
                }
            break;
            case "POST":

            break;
        }

    }