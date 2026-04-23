<?php
require_once __DIR__ . '/csrf_helper.php';

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_required($data, $fields) {
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "El campo $field es obligatorio";
        }
    }
    return $errors;
}

function validate_min_length($data, $field, $min) {
    if (isset($data[$field]) && strlen($data[$field]) < $min) {
        return "El campo $field debe tener al menos $min caracteres";
    }
    return null;
}

function validate_max_length($data, $field, $max) {
    if (isset($data[$field]) && strlen($data[$field]) > $max) {
        return "El campo $field no puede exceder $max caracteres";
    }
    return null;
}

function validate_numeric($data, $field) {
    if (isset($data[$field]) && !is_numeric($data[$field])) {
        return "El campo $field debe ser numérico";
    }
    return null;
}

function validate_positive($data, $field) {
    if (isset($data[$field]) && $data[$field] < 0) {
        return "El campo $field debe ser positivo";
    }
    return null;
}

function get_validation_errors($data, $rules) {
    $errors = [];
    foreach ($rules as $field => $rule_set) {
        foreach ($rule_set as $rule => $param) {
            switch ($rule) {
                case 'required':
                    if (!isset($data[$field]) || trim($data[$field]) === '') {
                        $errors[] = "El campo " . ucfirst($field) . " es obligatorio";
                    }
                    break;
                case 'email':
                    if (isset($data[$field]) && !validate_email($data[$field])) {
                        $errors[] = "El email no es válido";
                    }
                    break;
                case 'min':
                    if (isset($data[$field]) && strlen($data[$field]) < $param) {
                        $errors[] = ucfirst($field) . " debe tener al menos $param caracteres";
                    }
                    break;
                case 'max':
                    if (isset($data[$field]) && strlen($data[$field]) > $param) {
                        $errors[] = ucfirst($field) . " no puede exceder $param caracteres";
                    }
                    break;
                case 'numeric':
                    if (isset($data[$field]) && !is_numeric($data[$field])) {
                        $errors[] = ucfirst($field) . " debe ser numérico";
                    }
                    break;
                case 'positive':
                    if (isset($data[$field]) && $data[$field] < 0) {
                        $errors[] = ucfirst($field) . " debe ser positivo";
                    }
                    break;
            }
        }
    }
    return $errors;
}