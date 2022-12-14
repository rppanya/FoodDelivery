<?php
    function isValidRegistration($fullName, $password, $email, $gender, $phoneNumber, $birthDate): bool
    {

        $validationErrors = [];
        if ($fullName == "") {
            $validationErrors[] = ["FullName" => "The FullName field is required."];
        }
        if (validateContainsDigit($fullName) || validateContainsSpecialSymbolsExceptDash($fullName)) {
            $validationErrors[] = ["FullName" => "The FullName field can only contain letters and a sign '-'"];
        }
        if ($password == "") {
            $validationErrors[] = ["Password" => "The Password field is required."];
        }
        if (strlen($password) < 6 && $password != "") {
            $validationErrors[] = ["Password" => "The field Password must be a string or array type with a minimum length of '6'."];
        }
        if (strlen($password) > 30) {
            $validationErrors[] = ["Password" => "The field Password must be a string or array type with a maximum length of '30'."];
        }
        if (!validateContainsDigit($password) && $password != "") {
            $validationErrors[] = ["Password" => "Password requires at least one digit"];
        }
        if ($email == "") {
            $validationErrors[] = ["Email" => "The Email field is required."];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !is_null($email) && $email != "") {
            $validationErrors[] = ["Email" => "Invalid Email address"];
        }
        if ($gender == "") {
            $validationErrors[] = ["Gender" => "The Gender field is required"];
        }
        if ($gender != "Male" && $gender != "Female") {
            $validationErrors[] = ["Gender" => "Invalid gender value"];
        }
        if(!ctype_digit($phoneNumber)) {
            $validationErrors[] = ["PhoneNumber" => "The PhoneNumber field is not a valid phone number"];
        }
        $minAge = 3600*24*365*10; //10 years
        if (!checkDateTime($birthDate)) {
            $validationErrors[] = ["BirthDate" => "Invalid birthDate value"];
        } else if (time() - checkDateTime($birthDate) < $minAge ) {
            $validationErrors[] = ["BirthDate" => "Invalid birthDate value. Age for registration cannot be less than 10 years"];
        }

        if ($validationErrors) {
            $messageResult = array(
                'message' => 'User Registration Failed',
                'errors' => []
            );
            $messageResult['errors'] = $validationErrors;
            setHTTPStatus('400', $messageResult);
            return false;
        }
        return true;
    }

    function validateContainsDigit($str) : bool
    {
        if (preg_match('/[1-9]/', $str)) {
            return true;
        }
        return false;
    }

    function validateContainsSpecialSymbols($str) : bool
    {
        if (preg_match('/[\'^£$%&*()}{@#~?><,|=_+¬-]/', $str) || strpos($str, '/') || strpos($str, ' ')) {
            return true;
        }
        return false;
    }

    function validateContainsSpecialSymbolsExceptDash($str) : bool
    {
        if (preg_match('/[\'^£$%&*()}{@#~?><,|=_+¬]/', $str) || strpos($str, '/') || strpos($str, ' ')) {
            return true;
        }
        return false;
    }

    function isValidChangeProfile($fullName, $gender, $phoneNumber) {
        $validationErrors = [];
        if ($fullName == "") {
            $validationErrors[] = ["FullName" => "The FullName field is required."];
        }
        if (validateContainsDigit($fullName) || validateContainsSpecialSymbolsExceptDash($fullName)) {
            $validationErrors[] = ["FullName" => "The FullName field can only contain letters and a sign '-'"];
        }
        if ($gender == "") {
            $validationErrors[] = ["Gender" => "The Gender field is required"];
        }
        if ($gender != "Male" && $gender != "Female") {
            $validationErrors[] = ["Gender" => "Invalid gender value"];
        }
        if(!ctype_digit($phoneNumber)) {
            $validationErrors[] = ["PhoneNumber" => "The PhoneNumber field is not a valid phone number"];
        }
        if ($validationErrors) {
            $messageResult = array(
                'message' => 'User Registration Failed',
                'errors' => []
            );
            $messageResult['errors'] = $validationErrors;
            setHTTPStatus('400', $messageResult);
            return false;
        }
        return true;
    }