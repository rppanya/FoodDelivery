<?php
//    global $link;
//
//    $get_data = file_get_contents('https://food-delivery.kreosoft.ru/api/dish?page=3');
//    $get_data = json_decode($get_data, true);
//    foreach ($get_data['dishes'] as $item) {
//        $id = $item['id'];
//        $name = $item['name'];
//        $description = $item['description'];
//        $price = $item['price'];
//        $image = $item['image'];
//        $vegetarian = boolval($item['vegetarian']);
//        $rating = $item['rating'];
//        $category = $item['category'];
//        $addDish = $link->query("INSERT INTO dish(dish_id, name, description, price, image, vegetarian, rating, category)
//                                        VALUES('$id', '$name', '$description', '$price', '$image', '$vegetarian', '$rating', '$category') ");
//    }