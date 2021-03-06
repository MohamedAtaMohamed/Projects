<?php
namespace MVC\LIB;
trait Validate
{
    private $_regexPatterns = [
        'num'           => '/^[0-9]+(?:\.[0-9]+)?$/',
        'int'           => '/^[0-9]+$/',
        'float'         => '/^[0-9]+\.[0-9]+$/',
        'alpha'         => '/^[a-zA-Z\p{Arabic} ]+$/u',
        'alphanum'      => '/^[a-zA-Z0-9-\p{Arabic} ]+$/u',
        'vdate'         => '/^[1-2][0-9][0-9][0-9]-(?:(?:0[1-9])|(?:1[0-2]))-(?:(?:0[1-9])|(?:(?:1|2)[0-9])|(?:3[0-1]))$/',
        'email'         => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        'url'           => '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/'
    ];
    public function req ($value)
    {
        return '' != $value || !empty($value);
    }
    public function num($value)
    {
        return (bool) preg_match($this->_regexPatterns['num'], $value);
    }
    public function int($value)
    {
        return (bool) preg_match($this->_regexPatterns['int'], $value);
    }
    public function float($value)
    {
        return (bool) preg_match($this->_regexPatterns['float'], $value);
    }
    public function alpha($value)
    {
        return (bool) preg_match($this->_regexPatterns['alpha'], $value);
    }
    public function alphanum($value)
    {
        return (bool) preg_match($this->_regexPatterns['alphanum'], $value);
    }
    public function eq($value, $matchAgainst)
    {
        return $value == $matchAgainst;
    }
    public function eq_field($value, $otherFieldValue)
    {
        return $value == $otherFieldValue;
    }
    public function lt($value, $matchAgainst)
    {
        if(is_string($value)) {
            return mb_strlen($value) < $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value < $matchAgainst;
        }
    }
    public function gt($value, $matchAgainst)
    {
        if(is_string($value)) {
            return mb_strlen($value) > $matchAgainst;
        } elseif (is_numeric($value)) {
            return $value > $matchAgainst;
        }
    }
    public function min($value, $min)
    {
        if(is_string($value)) {
            return mb_strlen($value) >= $min;
        } elseif (is_numeric($value)) {
            return $value >= $min;
        }
    }
    public function max($value, $max)
    {
        if(is_string($value)) {
            return mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value <= $max;
        }
    }
    public function between($value, $min, $max)
    {
        if(is_string($value)) {
            return mb_strlen($value) >= $min && mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }
    }
    public function floatlike($value, $beforeDP, $afterDP)
    {
        if(!$this->float($value))
        {
            return false;
        }
        $pattern = '/^[0-9]{' . $beforeDP . '}\.[0-9]{' . $afterDP . '}$/';
        return (bool) preg_match($pattern, $value);
    }
    public function vdate($value)
    {
        return (bool) preg_match($this->_regexPatterns['vdate'], $value);
    }
    public function email($value)
    {
        return (bool) preg_match($this->_regexPatterns['email'], $value);
    }
    public function url($value)
    {
        return (bool) preg_match($this->_regexPatterns['url'], $value);
    }
    public function isValid($roles, $inputType)
    {
        $errors = [];
        if(!empty($roles)) {
            foreach ($roles as $fieldName => $validationRoles) {
                $value = $inputType[$fieldName];
                $validationRoles = explode('|', $validationRoles);
                foreach ($validationRoles as $validationRole) {
                    if(array_key_exists($fieldName, $errors))
                        continue;
                    if(preg_match_all('/(min)\((\d+)\)/', $validationRole, $m)) {
                        if($this->min($value, $m[2][0]) === false) {
                            $this->_messenger->add("القيمه لابد ان تكون اكبر من $m[2][0])" ,Messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif (preg_match_all('/(max)\((\d+)\)/', $validationRole, $m)) {
                        if($this->max($value, $m[2][0]) === false) {
                            $this->_messenger->add("القيمه لابد ان تكون اقل من $m[2][0])", Messenger::APP_MESSAGE_ERROR);
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(lt)\((\d+)\)/', $validationRole, $m)) {
                        if($this->lt($value, $m[2][0]) === false) {
                            $this->_messenger->add("القيمه لابد ان تكون اكبر من $m[2][0])" ,Messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;

                        }
                    } elseif(preg_match_all('/(gt)\((\d+)\)/', $validationRole, $m)) {
                        if($this->gt($value, $m[2][0]) === false) {
                            $this->_messenger->add("القيمه لابد ان تكون اكبر من $m[2][0])" ,Messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } elseif(preg_match_all('/(eq_field)\((\w+)\)/', $validationRole, $m)) {
                        $otherFieldValue = $inputType[$m[2][0]];
                        if($this->eq_field($value, $otherFieldValue) === false) {
                            $this->_messenger->add(" القيمه لابد ان تكون مساويه لقيمه لبحقل$otherFieldValue  ",Messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    } else {
                        if($this->$validationRole($value) === false) {
                            $this->_messenger->add(" قيمه الحقل$fieldName مخالفه للشروط ",
                                Messenger::APP_MESSAGE_ERROR
                            );
                            $errors[$fieldName] = true;
                        }
                    }
                }
            }
        }
        return empty($errors) ? true : false;
    }
}