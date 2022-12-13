<?php
    function validateGetParam($get) {
        $validationErrors = [];
        if ($get['vegetarian'] != '') {
            if ($get['vegetarian'] != '1' || $get['vegetarian'] != '0'
                || $get['vegetarian'] != 'true' || $get['vegetarian'] != 'false') {
                $validationErrors[] = ["vegetarian" => "Invalid value for attribute vegetarian"];
            }
        }
        if ($get['sorting']) {
            $sorting = mb_strtolower($get['sorting']);
            if ($sorting != 'nameasc' || $sorting != 'namedesc'
                || $sorting != 'priceasc' || $sorting != 'pricedesc'
                || $sorting != 'ratingasc' || $sorting != 'ratingdesc') {
                $validationErrors[] = ["sorting" => "Invalid value for attribute sorting"];
            }
        }
        if ($get['categories']) {
            $categoriesList = ['Wok', 'Pizza', 'Soup', 'Dessert', 'Drink'];
            foreach ($get['categories'] as $category) {
                $currentCategory = array_search($category, $categoriesList);
                if (!$currentCategory) {
                    $validationErrors[] = ["categories" => "Invalid value for attribute categories"];
                    break;
                }
            }
        }
        if ($get['page']) {
            if ($get['page'] <= 0) {
                $validationErrors[] = ["page" => "Invalid value for attribute page"];
            }
        }
        if ($validationErrors) {
            $messageResult = [
                'status' => 'Error',
                'message' => []
            ];
            $messageResult['message'] = $validationErrors;
            setHTTPStatus('400', $messageResult);
            return false;
        }
        return true;
    }