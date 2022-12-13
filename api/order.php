<?php
    require_once "modules/headers.php";
    require_once "modules/check_authorize.php";

    function route($method, $urlList, $requestData) {
        global $link;
        switch ($method) {
            case "GET":
                switch (count($urlList)) {
                    case 3:
                        $userID = checkAuthorize();
                        if (!$userID) {
                            setHTTPStatus('401','Token not specified or not valid');
                            return;
                        }
                        $orderInfo = $link->query("SELECT * FROM `_order` WHERE order_id='$urlList[2]' AND user_id='$userID'")->fetch_assoc();
                        if (!$orderInfo) {
                            setHTTPStatus('404', "Order with id = '$urlList[2]' not found");
                            return;
                        }
                        $dishInOrder = $link->query("SELECT * FROM dish_basket WHERE user_id='$userID' AND order_id='$urlList[2]'");
                        $dishesInOrder = [];
                        foreach ($dishInOrder as $dish) {
                            $dishID = $dish['dish_id'];
                            $dishInfo = $link->query("SELECT * FROM dish WHERE dish_id='$dishID'")->fetch_assoc();
                            $dishesInOrder[] = [
                                'id' => $dishInfo['dish_id'],
                                'name' => $dishInfo['name'],
                                'price' => $dishInfo['price'],
                                'totalPrice' => $dishInfo['price']*$dish['amount'],
                                'amount' => $dish['amount'],
                                'image' => $dishInfo['image']
                            ];
                        }
                        $result = [
                            'id' => $orderInfo['order_id'],
                            'deliveryTime' => $orderInfo['delivery_time'],
                            'orderTime' => $orderInfo['order_time'],
                            'status' => $orderInfo['status'],
                            'price' => $orderInfo['price'],
                            'dishes' => $dishesInOrder,
                            'address' => $orderInfo['address']
                        ];

                        echo json_encode($result);
                        break;
                    case 2:
                        $userID = checkAuthorize();
                        if (!$userID) {
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
                    default:
                        setHTTPStatus('404', 'Missing resource is requested');
                        break;
                }
                break;
            case "POST":
                switch (count($urlList)) {
                    case 2:
                        $userID = checkAuthorize();
                        if (!$userID) {
                            setHTTPStatus('401','Token not specified or not valid');
                            return;
                        }
                        $deliveryTime = $requestData->body->deliveryTime;
                        $address = $requestData->body->address;
                        date_default_timezone_set('Asia/Tomsk');
                        $orderTime = date(DATE_ATOM);
                        $addOrder = $link->query("INSERT INTO `_order`(order_id, delivery_time, order_time, status, price, address, user_id)
                                                        VALUES(UUID(), $deliveryTime, $orderTime, 'In process',  )");

                        //$dishesFromBasket = $link->query("UPDATE dish_basket SET order_id=UUID() WHERE user_id='$userID' AND order_id=null");

                        break;
                    case 4:
                        $userID = checkAuthorize();
                        if (!$userID) {
                            setHTTPStatus('401','Token not specified or not valid');
                            return;
                        }
                        if ($urlList[3] != 'status') {
                            setHTTPStatus('404', 'Missing resource is requested');
                            return;
                        }
                        $orderInfo = $link->query("SELECT * FROM `_order` WHERE order_id='$urlList[2]' AND user_id='$userID'")->fetch_assoc();
                        if (!$orderInfo) {
                            setHTTPStatus('404', "Order with id = '$urlList[2]' not found");
                            return;
                        }
                        $confirmDeliveryOrder = $link->query("UPDATE `_order` SET status = 'Delivered' WHERE user_id='$userID' AND order_id='$urlList[2]'");
                        break;
                    default:
                        setHTTPStatus('404', 'Missing resource is requested');
                        break;
                }
                break;
            default:
                setHTTPStatus('405', "Method '$method' not allowed");
                break;
        }
    }