<?php
    require_once "modules/headers.php";
    require_once "modules/jwt.php";

    function route($method, $urlList, $requestData) {
        global $link;
        $token = substr(getallheaders()['Authorization'], 7);
        $emailFromToken = getPayload($token)['email'];
        $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
        $userID = $user['user_id'];
        $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
        if (!$checkToken) {
            setHTTPStatus('401','Token not specified or not valid');
            return;
        }
        switch ($method) {
            case "GET":

                $basketList = $link->query(-"SELECT dish.dish_id, name, price, amount, image FROM dish 
                                                    JOIN dish_basket ON dish_basket.dish_id = dish.dish_id 
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

                //$checkDishInBasket = $link->query("SELECT amount FROM dish_basket WHERE user_id='$userID' AND dish_id='$urlList[3]'");
                $updateDishInBasket = $link->query("UPDATE dish_basket SET amount+=1 WHERE user_id='$userID' AND dish_id='$urlList[3]'");
                if (!$updateDishInBasket) {
                    $addDish = $link->query("INSERT INTO dish_basket(user_id, dish_id, amount)
                                                                VALUES('$userID', '$urlList[3]', 1)");
                    if (!$addDish) {
                        echo "post error" . $link->error;
                    }
                }
            break;
            case "DELETE":

                $deleteDishFromBasket = $link->query("DELETE FROM dish_basket WHERE user_id='$userID' AND dish_id='$urlList[3]'");
                if (!$deleteDishFromBasket) {
                    echo "delete dish from basket error" . $link->error;
                }
            break;
        }

    }