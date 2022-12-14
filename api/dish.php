<?php
    require_once "modules/headers.php";
    require_once "modules/jwt.php";
    require_once "modules/validateGetParam.php";
    require_once "modules/check_authorize.php";

    function route($method, $urlList, $requestData) {
        global $link;
        switch (count($urlList)) {
            case 2:
                if ($method != "GET") {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    return;
                }
                $url = strstr($_SERVER['REQUEST_URI'], '?');
                $url = substr($url, 1);
                $url = str_replace("categories", "categories[]", $url);
                parse_str($url, $get);

                if (!validateGetParam($get)) {
                    return;
                }

                $pageInfo = ['size' => 6];
                $pageNumber = $get['page'];
                if ($pageNumber == '') {
                    $pageNumber = 1;
                }
                $pageSize = $pageInfo['size'];
                $from = $pageSize*($pageNumber - 1);

                $categoriesList = [];
                if ($get['categories'] == '') {
                    $categoriesList = ['Wok', 'Pizza', 'Soup', 'Dessert', 'Drink'];
                } else {
                    foreach ($get['categories'] as $category) {
                        $categoriesList[] = $category;
                    }
                }
                $categories = '\'' . implode('\', \'', $categoriesList) . '\'';

                if ($get['vegetarian'] == '' || $get['vegetarian'] == 0) {
                    $vegetarian = '1, 0';
                } else {
                    $vegetarian = 1;
                }
                if ($get['sorting']) {
                    $sorting = ltrim(preg_replace( '/[A-Z]/', ' $0', $get['sorting']));
                    $sorting = mb_strtolower($sorting);
                    $sorting = ltrim(preg_replace('/asc/', 'ASC', $sorting));
                    $sorting = ltrim(preg_replace('/desc/', 'DESC', $sorting));
                } else {
                    $sorting = 'dish_id ASC';
                }

                $listDishes = $link->query("SELECT * FROM dish WHERE (category IN ($categories) AND vegetarian IN ($vegetarian)) ORDER BY $sorting LIMIT $pageSize OFFSET $from");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                foreach ($listDishes as $dish) {
                    $result['dishes'][] = $dish;
                }

                $countPages = $link->query("SELECT COUNT(*) AS countPages FROM dish WHERE (category IN ($categories) AND vegetarian IN ($vegetarian))");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $countPages = $countPages->fetch_assoc();
                $pageInfo['count'] = ceil($countPages['countPages'] / $pageSize);
                $pageInfo['current'] = $pageNumber;
                $result['pagination'] = $pageInfo;
                if ($get['page'] > $pageInfo['count']) {
                    $messageResult = [
                        'status' => 'Error',
                        'message' => []
                    ];
                    $messageResult['message'] = ["page" => "Invalid value for attribute page"];
                    setHTTPStatus('400', $messageResult);
                    return;
                }
                echo json_encode($result);
                break;
            case 3:
                if ($method != "GET") {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    return;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $checkDish = $checkDish->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[2]' not found");
                    return;
                }
                $dishInfo = $link->query("SELECT * FROM dish WHERE dish_id='$urlList[2]'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $dishInfo = $dishInfo->fetch_assoc();
                $dishRating = $link->query("SELECT * FROM dish_rating WHERE dish_id='$urlList[2]'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
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
                    'rating' => $countRating == 0? 0: $sumRating/$countRating,
                    'category' => $dishInfo['category']
                ];
                echo json_encode($result);
                break;
            case 5:
                if ($method != "GET" && $urlList[3] == "rating" && $urlList[4] == "check") {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    return;
                }
                if ($urlList[3] != "rating" || $urlList[4] != "check") {
                    setHTTPStatus('404','Missing resource is requested');
                    return;
                }
                $userID = checkAuthorize();
                if (!$userID) {
                    setHTTPStatus('401','Token not specified or not valid');
                    return;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $checkDish = $checkDish->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[2]' not found");
                    return;
                }
                $dishRatingCheck = $link->query("SELECT * FROM dish_rating WHERE dish_id = '$urlList[2]' AND user_id='$userID'");
                if ($link->error) {
                    setHTTPStatus('500', $link->error);
                    break;
                }
                $dishRatingCheck = $dishRatingCheck->fetch_assoc();
                if ($dishRatingCheck){
                    echo "true";
                } else {
                    echo "false";
                }
                break;
            case 4:
                if ($method != "POST" && $urlList[3]=="rating") {
                    setHTTPStatus('405',"Method '$method' not allowed");
                    return;
                }
                if ($urlList[3]!="rating") {
                    setHTTPStatus('404','Missing resource is requested');
                    return;
                }
                $userID = checkAuthorize();
                if (!$userID) {
                    setHTTPStatus('401','Token not specified or not valid');
                    return;
                }
                $checkDish = $link->query("SELECT dish_id FROM dish WHERE dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkDish) {
                    setHTTPStatus('404', "Dish with id = '$urlList[2]' not found");
                    return;
                }
                $checkOrder = $link->query("SELECT order_id FROM dish_basket WHERE user_id='$userID' 
                                                  AND dish_id='$urlList[2]' AND order_id is not null")->fetch_assoc();
                if (!$checkOrder) {
                    setHTTPStatus('403', "User can't set rating on dish that wasn't ordered");
                    return;
                }
                $ratingScore = $_GET['ratingScore'];
                if ($ratingScore > 10 || $ratingScore < 0 || $ratingScore == '') {
                    setHTTPStatus('400', "Invalid value for rating");
                    return;
                }

                $checkRating = $link->query("SELECT rating FROM dish_rating WHERE user_id='$userID' AND dish_id='$urlList[2]'")->fetch_assoc();
                if (!$checkRating) {
                    $addRating = $link->query("INSERT INTO dish_rating(user_id, dish_id, rating)
                                                                VALUES('$userID', '$urlList[2]', '$ratingScore')");
                } else {
                    $updateRating = $link->query("UPDATE dish_rating SET rating='$ratingScore' WHERE user_id='$userID' AND dish_id='$urlList[2]'");
                }
                break;
            default:
                setHTTPStatus('404', 'Missing resource is requested');
                break;
        }
    }