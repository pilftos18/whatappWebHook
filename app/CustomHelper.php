<?php
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($minLength = 8, $maxLength = 14) {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        $characters = $uppercase . $lowercase . $numbers . $specialChars;

        $password = '';
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];
        $password .= $specialChars[rand(0, strlen($specialChars) - 1)];

        $remainingLength = rand($minLength - 4, $maxLength - 4);

        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        $password = str_shuffle($password);

        return $password;
    }
}