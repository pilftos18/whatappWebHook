<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordPolicy implements Rule
{
    /**
    * Create a new rule instance.
    *'required': Ensures that the password field is not empty.
    *'string': Ensures that the password value is a string.
    *'min:8': Specifies a minimum length of 8 characters for the password.
    *'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/': Uses a regular expression to enforce the following rules:
    * At least one lowercase letter ((?=.*[a-z])).
    * At least one uppercase letter ((?=.*[A-Z])).
    * At least one digit ((?=.*\d)).
    * At least one special character from !@#$%^&*.
    * Allows any combination of letters, digits, and the specified special characters ([A-Za-z\d!@#$%^&*]+).
    * @return void
    */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // At least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }

        // At least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }

        // At least one number
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }

        // At least one special character
        if (!preg_match('/[!@#$%^&*]/', $value)) {
            return false;
        }

        // Additional custom rules, if needed
        // ...

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must contain at least one capital letter, one special character {@$!%*?&}, be at least 8 characters long.';

    }
}
