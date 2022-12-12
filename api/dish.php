<?php
    require_once "modules/headers.php";
    require_once "modules/jwt.php";
    function route($method, $urlList, $requestData) {
        global $link;
        switch (count($urlList)) {
            case 2:
                $url = strstr($_SERVER['REQUEST_URI'], '?');
                $url = substr($url, 1);
                $url = str_replace("categories", "categories[]", $url);
                parse_str($url, $get);

                $pageInfo = array('size' => 6);
                $pageNumber = $get['page'];
                if (!$pageNumber) {
                    $pageNumber = 1;
                } else if ($pageNumber <= 0) {
                    setHTTPStatus('404', "Page '$pageNumber' does not exist");
                    return;
                }
                $numberOfRecords = $pageInfo['size'];
                $from = $pageInfo['size']*($pageInfo['currentPage'] - 1);

                $categoriesList = [];
                if ($get['categories'] == '') {
                    $categoriesList = ['Wok', 'Pizza', 'Soup', 'Dessert', 'Drink'];
                } else {
                    foreach ($get['categories'] as $category) {
                        $categoriesList[] = $category;
                    }
                }
                $categories = '\'' . implode('\', \'', $categoriesList) . '\'';

                if ($get['vegetarian'] == '') {
                    $vegetarian = '1, 0';
                } else {
                    $vegetarian = boolval($get['vegetarian']);
                }

                $sorting = ltrim(preg_replace( '/[A-Z]/', ' $0', $get['sorting']));
                $sorting = mb_strtolower($sorting);
                $sorting = ltrim(preg_replace('/asc/', 'ASC', $sorting));
                $sorting = ltrim(preg_replace('/desc/', 'DESC', $sorting));

                $listDishes = $link->query("SELECT * FROM dish WHERE (category IN ($categories) AND vegetarian IN ($vegetarian)) ORDER BY $sorting");

                foreach ($listDishes as $dish) {
                    $result['dishes'][] = $dish;
                }
                $countPages = $link->query("SELECT COUNT(*) AS countPages FROM dish WHERE (category IN ($categories) AND vegetarian IN ($vegetarian)) ORDER BY $sorting")->fetch_assoc();
                $pageInfo['count'] = intval($countPages['countPages']);
                $pageInfo['current'] = $pageNumber;
                $result['pagination'] = $pageInfo;
                echo json_encode($result);
                break;

            case 3:
                if ($method != "GET") {
                    setHTTPStatus('403',"Method '$method' not allowed");
                    break;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found");
                    break;
                }
                $dishInfo = $link->query("SELECT * FROM dish WHERE dish_id='$urlList[2]'")->fetch_assoc();
                $dishRating = $link->query("SELECT * FROM dish_rating WHERE dish_id='$urlList[2]'");
                $sumRating = 0;
                $countRating = 0;
                foreach ($dishRating as $rating) {
                    $sumRating += $rating['rating'];
                    $countRating += 1;
                }
                $result = [
                    'id' => $dishInfo['dish_id'],
                    'name' => $dishInfo['name'],
                    'description' => $dishInfo['description'],
                    'price' => $dishInfo['price'],
                    'image' => $dishInfo['image'],
                    'vegetarian' => boolval($dishInfo['vegetarian']),
                    'rating' => $sumRating/$countRating,
                    'category' => $dishInfo['category']
                ];
                echo json_encode($result);
                break;
            case 5:
                if ($method != "GET" || $urlList[3]!="rating" || $urlList[4]!="check") {
                    setHTTPStatus('403',"Method '$method' not allowed");
                    break;
                }
                $token = substr(getallheaders()['Authorization'], 7);
                $emailFromToken = getPayload($token)['email'];
                $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
                $userID = $user['user_id'];
                $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
                if ($checkToken) {
                    setHTTPStatus('401','Token not specified or not valid');
                    return;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found");
                    break;
                }
                $dishRatingCheck = $link->query("SELECT * FROM dish_rating WHERE dish_id = '$urlList[2]' AND user_id='$userID'")->fetch_assoc();
                if ($dishRatingCheck){
                    echo "true";
                } else {
                    echo "false";
                }
                break;
            case 4:
                if ($method != "POST" || $urlList[3]!="rating") {
                    setHTTPStatus('403',"Method '$method' not allowed");
                    break;
                }
                $token = substr(getallheaders()['Authorization'], 7);
                $emailFromToken = getPayload($token)['email'];
                $user = $link->query("SELECT user_id FROM user WHERE email='$emailFromToken'")->fetch_assoc();
                $userID = $user['user_id'];
                $checkToken = $link->query("SELECT token_id FROM token WHERE token='$token'")->fetch_assoc();
                if ($checkToken) {
                    setHTTPStatus('401','Token not specified or not valid');
                    return;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[3]' not found");
                    break;
                }
                $ratingScore = $_GET['ratingScore'];
                if ($ratingScore>10 || $ratingScore<0 || !$ratingScore) {
                    setHTTPStatus('400', "Invalid value for rating");
                    break;
                }

                //TODO сделать проверку на то, что user заказывал это блюдо (403)

                $checkRating = $link->query("SELECT rating FROM dish_rating WHERE user_id='$userID' AND dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkRating) {
                    $addRating = $link->query("INSERT INTO dish_rating(user_id, dish_id, rating)
                                                                VALUES('$userID', '$urlList[2]', '$ratingScore')");
                } else {
                    $updateRating = $link->query("UPDATE dish_rating SET rating='$ratingScore' WHERE user_id='$userID AND dish_id='$urlList[2]");
                }

        }
    }