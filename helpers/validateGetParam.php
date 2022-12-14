<?php
function validateGetParam($get)
{
    $validationErrors = [];
    if ($get['vegetarian'] != '') {
        $vegetarianList = ['1', '0', 'true', 'false'];
        $inVegetarianList = in_array($get['vegetarian'], $vegetarianList);
        if (!$inVegetarianList) {
            $validationErrors[] = ["vegetarian" => "Invalid value for attribute vegetarian"];
        }
    }
    if ($get['sorting']) {
        $sortingList = ['nameasc', 'namedesc', 'priceasc', 'pricedesc', 'ratingasc', 'ratingdesc'];
        $sorting = mb_strtolower($get['sorting']);
        $inSortingList = in_array($sorting, $sortingList);
        if (!$inSortingList) {
            $validationErrors[] = ["sorting" => "Invalid value for attribute sorting"];
        }
    }
    if ($get['categories']) {
        $categoriesList = ['Wok', 'Pizza', 'Soup', 'Dessert', 'Drink'];
        foreach ($get['categories'] as $category) {
            $currentCategory = in_array($category, $categoriesList);
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