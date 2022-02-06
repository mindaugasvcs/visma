<?php

namespace Core;

use Core\Exceptions\ValidationException;

class Validator
{
    /**
     * Error messages
     */
    private static array $errors = [];

    private static string $language = 'en';

    private static array $messages = [
        'en' => [
            'extra_items'       => 'Extra items not allowed.',
            'required'          => 'This item is required.',
            'string'            => 'Item must be a string',
            'string_min'        => 'Item has too few characters. Please provide at least %d',
            'string_max'        => 'Item has too many characters. Up to %d allowed',
            'string_range'      => 'Item must contain at least %1$d and no more than %2$d characters',
            'email'             => 'Invalid email address',
            'alpha'             => 'Item may only contain letters',
            'digit'             => 'Item may only contain digits',
            'alpha_min'         => 'Item must contain at least %d letter(s)',
            'alpha_max'         => 'Item must contain no more than %d letters',
            'alpha_range'       => 'Item must contain at least %1$d and no more than %2$d letters',
            'digit_min'         => 'Item must contain at least %d digit(s)',
            'digit_max'         => 'Item must contain no more than %d digits',
            'digit_range'       => 'Item must contain at least %1$d and no more than %2$d digits',
            'date'              => 'Invalid date format',
            'datetime'          => 'Invalid datetime format',
            'date_min'          => 'Item must be equal to or after %s',
            'date_max'          => 'Item must be equal to or before %s',
            'date_range'        => 'Item must be from %1$s to %2$s',
            'in_array'          => 'Item was not found in the list of valid values',
            'confirm'           => 'Item must be same as %s'
        ]
    ];

    private static function required($field, $value, $params): bool
    {
        if (empty($value)) {
            self::$errors[$field] = self::$messages[self::$language]['required'];
            return false;
        }
        return true;
    }

    private static function string($field, $value, $params): bool
    {
        if (is_string($value)) {
            if ($params) {
                $l = mb_strlen($value, 'UTF-8');
                if (isset($params['min']) && isset($params['max'])) {
                    if ($l < $params['min'] || $l > $params['max']) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['string_range'], $params['min'], $params['max']);
                        return false;
                    }
                } elseif (isset($params['min'])) {
                    if ($l < $params['min']) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['string_min'], $params['min']);
                        return false;
                    }
                } elseif (isset($params['max'])) {
                    if ($l > $params['max']) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['string_max'], $params['max']);
                        return false;
                    }
                } else {
                    throw new \DomainException('Missing validator parameter(s).');
                }
            }
            return true;
        }
        self::$errors[$field] = self::$messages[self::$language]['string'];
        return false;
    }

    private static function email($field, $value, $params): bool
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false) {
            return true;
        }
        self::$errors[$field] = self::$messages[self::$language]['email'];
        return false;
    }

    private static function alpha($field, $value, $params): bool
    {
        if (preg_match('/[^[:alpha:]]/u', $value)) {
            self::$errors[$field] = self::$messages[self::$language]['alpha'];
            return false;
        }
        return true;
    }

    private static function digit($field, $value, $params): bool
    {
        if (preg_match('/[^[:digit:]]/u', $value)) {
            self::$errors[$field] = self::$messages[self::$language]['digit'];
            return false;
        }
        return true;
    }

    private static function contain_alpha($field, $value, array $params): bool
    {
        $count = preg_match_all('/[[:alpha:]]/u', $value);
        if (isset($params['min']) && isset($params['max'])) {
            if ($count < $params['min'] || $count > $params['max']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['alpha_range'], $params['min'], $params['max']);
                return false;
            }
        } elseif (isset($params['min'])) {
            if ($count < $params['min']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['alpha_min'], $params['min']);
                return false;
            }
        } elseif (isset($params['max'])) {
            if ($count > $params['max']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['alpha_max'], $params['max']);
                return false;
            }
        } else {
            throw new \DomainException('Missing validator parameter(s).');
        }
        return true;
    }

    private static function contain_digit($field, $value, array $params): bool
    {
        $count = preg_match_all('/\d/', $value);
        if (isset($params['min']) && isset($params['max'])) {
            if ($count < $params['min'] || $count > $params['max']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['digit_range'], $params['min'], $params['max']);
                return false;
            }
        } elseif (isset($params['min'])) {
            if ($count < $params['min']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['digit_min'], $params['min']);
                return false;
            }
        } elseif (isset($params['max'])) {
            if ($count > $params['max']) {
                self::$errors[$field] = sprintf(self::$messages[self::$language]['digit_max'], $params['max']);
                return false;
            }
        } else {
            throw new \DomainException('Missing validator parameter(s).');
        }
        return true;
    }

    private static function date($field, $value, $params): bool
    {
        if (is_string($value) && preg_match('/^[1-2]\d{3}-(?:0??[1-9]|1[0-2])-(?:0??[1-9]|[1-2][0-9]|3[0-1])$/', $value)) {
            if ($params) {
                $inputDt = new \DateTime($value);
                if (isset($params['min']) && isset($params['max'])) {
                    $ruleDtMin = new \DateTime($params['min']);
                    $ruleDtMax = new \DateTime($params['max']);
                    if ($inputDt < $ruleDtMin || $inputDt > $ruleDtMax) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_range'], $ruleDtMin->format('Y-m-d'), $ruleDtMax->format('Y-m-d'));
                        return false;
                    }
                } elseif (isset($params['min'])) {
                    $ruleDt = new \DateTime($params['min']); // e.g. '-10 years'
                    if ($inputDt < $ruleDt) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_min'], $ruleDt->format('Y-m-d'));
                        return false;
                    }
                } elseif (isset($params['max'])) {
                    $ruleDt = new \DateTime($params['max']); // e.g. '+10 days'
                    if ($inputDt > $ruleDt) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_max'], $ruleDt->format('Y-m-d'));
                        return false;
                    }
                } else {
                    throw new \DomainException('Missing validator parameter(s).');
                }
            }
            return true;
        }
        self::$errors[$field] = self::$messages[self::$language]['date'];
        return false;
    }
    private static function datetime($field, $value, $params): bool
    {
        if (is_string($value) && preg_match('/^[1-2]\d{3}-(?:0??[1-9]|1[0-2])-(?:0??[1-9]|[1-2][0-9]|3[0-1]) (?:0??[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
            if ($params) {
                $inputDt = new \DateTime($value);
                if (isset($params['min']) && isset($params['max'])) {
                    $ruleDtMin = new \DateTime($params['min']);
                    $ruleDtMax = new \DateTime($params['max']);
                    if ($inputDt < $ruleDtMin || $inputDt > $ruleDtMax) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_range'], $ruleDtMin->format('Y-m-d H:i'), $ruleDtMax->format('Y-m-d H:i'));
                        return false;
                    }
                } elseif (isset($params['min'])) {
                    $ruleDt = new \DateTime($params['min']); // e.g. '-10 years'
                    if ($inputDt < $ruleDt) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_min'], $ruleDt->format('Y-m-d H:i'));
                        return false;
                    }
                } elseif (isset($params['max'])) {
                    $ruleDt = new \DateTime($params['max']); // e.g. '+10 days'
                    if ($inputDt > $ruleDt) {
                        self::$errors[$field] = sprintf(self::$messages[self::$language]['date_max'], $ruleDt->format('Y-m-d H:i'));
                        return false;
                    }
                } else {
                    throw new \DomainException('Missing validator parameter(s).');
                }
            }
            return true;
        }
        self::$errors[$field] = self::$messages[self::$language]['datetime'];
        return false;
    }

    private static function in_array($field, $value, array $params): bool
    {
        if (in_array($value, $params)) {
            return true;
        }
        self::$errors[$field] = self::$messages[self::$language]['in_array'];
        return false;
    }

    /**
     * Validate property values, adding validation error messages to the errors array property
     */
    public static function validate(array $data, array $rules): array
    {
        if (array_diff_key($data, $rules)) {
            // throw an exception if extra properties present
            throw new ValidationException([self::$messages[self::$language]['extra_items']]);
        }

        foreach ($rules as $property => $validationRules) {
            if (!isset($data[$property])) { // null or undefined
                if (array_key_exists('nullable', $validationRules)) {
                    continue;
                } elseif (array_key_exists('required_if', $validationRules)) {
                    foreach ($validationRules['required_if'] as $k => $v) {
                        if (isset($data[$k]) && $data[$k] === $v) {
                            // get an error message if a required property is absent
                            self::required($property, null, null);
                            break;
                        }
                    }
                    continue;
                }
                // get an error message if a non-nullable property is absent
                self::required($property, null, null);
                continue;
            }
            foreach ($validationRules as $validator => $params) {
                if ($validator === 'nullable') {
                    continue;
                } elseif ($validator === 'confirm') {
                    if (!isset($data[$params]) || $data[$property] !== $data[$params]) {
                        self::$errors[$property] = sprintf(self::$messages[self::$language]['confirm'], $params);
                        break;
                    }
                    continue;
                } elseif ($validator === 'required_if') {
                    foreach ($params as $k => $v) {
                        if (isset($data[$k]) && $data[$k] === $v) {
                            continue 2;
                        }
                    }
                    unset($data[$property]);
                    break;
                }

                if (!self::$validator($property, $data[$property], $params)) {
                    break;
                }
            }
        }

        if (self::$errors) {
            throw new ValidationException(self::$errors);
        }
        return $data;
    }
}
