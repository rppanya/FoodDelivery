<?php
    require_once "modules/headers.php";
    require_once "modules/jwt.php";
    require_once "modules/check_authorize.php";

    function route($method, $urlList, $requestData) {
        global $link;
        $userID = checkAuthorize();
        if (!$userID) {
            setHTTPStatus('401','Token not specified or not valid');
            return;
        }
        switch ($method) {
            case "GET":
                if (count($urlList) != 2) {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    break;
                }
                $basketList = $link->query("SELECT dish.dish_id, name, price, amount, image FROM dish 
                                                    JOIN dish_basket ON dish_basket.dish_id = dish.dish_id AND dish_basket.order_id is null
                                                    JOIN user ON user.user_id = '$userID'");
                $result = [];
                foreach ($basketList as $dish) {
                    $result[] = [
                        'id' => $dish['dish_id'],
                        'name' => $dish['name'],
                        'price' => $dish['price'],
                        'totalPrice' => $dish['price']*$dish['amount'],
                        'amount' => $dish['amount'],
                        'image' => $dish['image']
                    ];
                }
                echo json_encode($result);
            break;
            case "POST":
                if (count($urlList) != 4 || $urlList[2] != 'dish') {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    break;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[3]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found");
                }
                $checkDishInBasket = $link->query("SELECT amount FROM dish_basket WHERE user_id='$userID' AND dish_id='$urlList[3]' AND order_id is null")->fetch_assoc();
                if ($checkDishInBasket) {
                    $updateDishInBasket = $link->query("UPDATE dish_basket SET amount=amount+1 WHERE user_id='$userID' AND dish_id='$urlList[3]' AND order_id is null");
                } else {
                    $addDish = $link->query("INSERT INTO dish_basket(user_id, dish_id, amount)
                                                                VALUES('$userID', '$urlList[3]', 1)");
                }
            break;
            case "DELETE":
                if (count($urlList) != 4 || $urlList[2] != 'dish') {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    break;
                }
                if (!$_GET['increase']) {
                    setHTTPStatus('400',"Value 'Increase' not specified");
                    break;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[3]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found");
                    break;
                }
                $checkDishInBasket = $link->query("SELECT amount FROM dish_basket WHERE dish_id='$urlList[3]' AND order_id is null")->fetch_assoc();
                if (!$checkDishInBasket) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found in a basket");
                    break;
                }
                $increaseBoolVal = filter_var($_GET['increase'], FILTER_VALIDATE_BOOLEAN);
                if ($increaseBoolVal && $checkDishInBasket['amount']-1 > 0) {
                    $decreaseDishInBasket = $link->query("UPDATE dish_basket SET amount=amount-1 WHERE user_id='$userID' AND dish_id='$urlList[3]' AND order_id is null");
                } else {
                    $deleteDishFromBasket = $link->query("DELETE FROM dish_basket WHERE user_id='$userID' AND dish_id='$urlList[3]' AND order_id is null");
                }
            break;
        }

    }