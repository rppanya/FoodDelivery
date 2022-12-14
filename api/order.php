<?php
    require_once "modules/headers.php";
    require_once "modules/check_authorize.php";
    require_once "modules/generateUUID.php";
    require_once "modules/check_birth_date.php";

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
                        $orderInfo = $link->query("SELECT * FROM `_order` WHERE order_id='$urlList[2]' AND user_id='$userID'");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
                        $orderInfo = $orderInfo->fetch_assoc();
                        if (!$orderInfo) {
                            setHTTPStatus('404', "Order with id = '$urlList[2]' not found");
                            return;
                        }
                        $dishInOrder = $link->query("SELECT * FROM dish_basket WHERE user_id='$userID' AND order_id='$urlList[2]'");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
                        $dishesInOrder = [];
                        foreach ($dishInOrder as $dish) {
                            $dishID = $dish['dish_id'];
                            $dishInfo = $link->query("SELECT * FROM dish WHERE dish_id='$dishID'");
                            if ($link->error) {
                                setHTTPStatus('500', $link->error);
                                break;
                            }
                            $dishInfo = $dishInfo->fetch_assoc();
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
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
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
                        if (!checkDateTime($deliveryTime)) {
                            setHTTPStatus('400', 'Invalid deliveryTime value');
                            return;
                        } else if (checkDateTime($deliveryTime)-3600<time()){
                            setHTTPStatus('400', 'Invalid delivery time. Delivery time must be more than current datetime on 60 minutes');
                            return;
                        }
                        $address = $requestData->body->address;
                        $orderTime = date(DATE_ATOM);
                        $dishesFromBasket = $link->query("SELECT dish.price, dish_basket.amount FROM dish_basket
                                                                JOIN dish ON dish_basket.dish_id = dish.dish_id AND dish_basket.order_id is null AND dish_basket.user_id = '$userID'");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
                        if ($dishesFromBasket->fetch_assoc() == []) {
                            setHTTPStatus('400', "Empty basket for user with id='$userID'");
                        }
                        $orderPrice = 0;
                        foreach ($dishesFromBasket as $dish) {
                            $orderPrice += $dish['price'] * $dish['amount'];
                        }
                        $orderID = generate_uuid();
                        $status = "InProcess";
                        $addOrder = $link->query("INSERT INTO `_order`(order_id, delivery_time, order_time, status, price, address, user_id)
                                                        VALUES('$orderID', '$deliveryTime', '$orderTime', '$status', '$orderPrice', '$address', '$userID')");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
                        $dishesFromBasketUpdate = $link->query("UPDATE dish_basket SET order_id='$orderID' WHERE user_id='$userID' AND order_id is null");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
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
                        $orderInfo = $link->query("SELECT * FROM `_order` WHERE order_id='$urlList[2]' AND user_id='$userID'");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
                        $orderInfo = $orderInfo->fetch_assoc();
                        if (!$orderInfo) {
                            setHTTPStatus('404', "Order with id = '$urlList[2]' not found");
                            return;
                        }
                        $confirmDeliveryOrder = $link->query("UPDATE `_order` SET status = 'Delivered' WHERE user_id='$userID' AND order_id='$urlList[2]'");
                        if ($link->error) {
                            setHTTPStatus('500', $link->error);
                            break;
                        }
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