<?php

declare(strict_types=1);

namespace PetCare\Validators;

use PetCare\Core\Database;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $rules)
    {
    }

    public function validate(array $data): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            $rules = is_array($rules) ? $rules : explode('|', (string) $rules);
            $nullable = in_array('nullable', $rules, true);

            if ($nullable && $this->isEmpty($value)) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                switch ($name) {
                    case 'required':
                        if ($this->isEmpty($value)) {
                            $this->add($field, 'Este campo é obrigatório.');
                        }
                        break;
                    case 'email':
                        if (!$this->isEmpty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->add($field, 'Informe um e-mail válido.');
                        }
                        break;
                    case 'cpf':
                        if (!$this->isEmpty($value) && !$this->validCpf((string) $value)) {
                            $this->add($field, 'CPF inválido.');
                        }
                        break;
                    case 'cnpj':
                        if (!$this->isEmpty($value) && !$this->validCnpj((string) $value)) {
                            $this->add($field, 'CNPJ/CPF inválido.');
                        }
                        break;
                    case 'numeric':
                        if (!$this->isEmpty($value) && !is_numeric($value)) {
                            $this->add($field, 'Informe um valor numérico.');
                        }
                        break;
                    case 'integer':
                        if (!$this->isEmpty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->add($field, 'Informe um número inteiro.');
                        }
                        break;
                    case 'date':
                        if (!$this->isEmpty($value) && !\DateTime::createFromFormat('Y-m-d', (string) $value)) {
                            $this->add($field, 'Informe uma data válida.');
                        }
                        break;
                    case 'datetime':
                        if (!$this->isEmpty($value) && !\DateTime::createFromFormat('Y-m-d H:i:s', (string) $value)) {
                            $this->add($field, 'Informe data e hora válidas.');
                        }
                        break;
                    case 'min':
                        if (!$this->isEmpty($value) && mb_strlen((string) $value) < (int) $arg) {
                            $this->add($field, "Deve ter no mínimo {$arg} caracteres.");
                        }
                        break;
                    case 'max':
                        if (!$this->isEmpty($value) && mb_strlen((string) $value) > (int) $arg) {
                            $this->add($field, "Deve ter no máximo {$arg} caracteres.");
                        }
                        break;
                    case 'in':
                        if (!$this->isEmpty($value) && !in_array((string) $value, explode(',', (string) $arg), true)) {
                            $this->add($field, 'Valor informado não é permitido.');
                        }
                        break;
                    case 'confirmed':
                        if (($data[$field] ?? null) !== ($data[(string) $arg] ?? null)) {
                            $this->add($field, 'Os valores não conferem.');
                        }
                        break;
                    case 'unique':
                        if (!$this->isEmpty($value)) {
                            $parts = explode(',', (string) $arg);
                            $table = $parts[0] ?? '';
                            $column = $parts[1] ?? $field;
                            $ignore = isset($parts[2]) && $parts[2] !== '' ? (int) $data[$parts[2]] : null;
                            Database::identifier($table);
                            Database::identifier($column);
                            $sql = sprintf('SELECT 1 FROM %s WHERE %s = :value', Database::identifier($table), Database::identifier($column));
                            $params = ['value' => $value];
                            if ($ignore !== null && $ignore > 0) {
                                $sql .= ' AND id <> :ignore';
                                $params['ignore'] = $ignore;
                            }
                            if (Database::scalar($sql . ' LIMIT 1', $params) !== false) {
                                $this->add($field, 'Este valor já está cadastrado.');
                            }
                        }
                        break;
                }
            }
        }

        return $this->errors;
    }

    private function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function onlyDigits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function validCpf(string $cpf): bool
    {
        $cpf = $this->onlyDigits($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $digit = ($sum * 10) % 11;
            $digit = $digit === 10 ? 0 : $digit;
            if ($digit !== (int) $cpf[$t]) {
                return false;
            }
        }
        return true;
    }

    private function validCnpj(string $document): bool
    {
        $value = $this->onlyDigits($document);
        if (in_array(strlen($value), [11, 14], true) && $this->validCpf($value)) {
            return true;
        }
        if (strlen($value) !== 14 || preg_match('/^(\d)\1{13}$/', $value)) {
            return false;
        }
        $weightsA = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weightsB = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($w = 0; $w < 2; $w++) {
            $weights = $w === 0 ? $weightsA : $weightsB;
            $sum = 0;
            foreach ($weights as $i => $weight) {
                $sum += (int) $value[$i] * $weight;
            }
            $digit = $sum % 11;
            $digit = $digit < 2 ? 0 : 11 - $digit;
            if ($digit !== (int) $value[12 + $w]) {
                return false;
            }
        }
        return true;
    }
}
