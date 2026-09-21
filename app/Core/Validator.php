<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return count($this->errors) > 0;
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    public function required(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field][] = "$label is required.";
        }
        return $this;
    }

    public function string(string $field, string $label, int $min = 0, int $max = 255): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '') {
            $len = mb_strlen((string) $value);
            if ($len < $min || $len > $max) {
                $this->errors[$field][] = "$label must be between $min and $max characters.";
            }
        }
        return $this;
    }

    public function email(string $field, string $label = 'Email'): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "$label must be a valid email address.";
        }
        return $this;
    }

    public function numeric(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "$label must be a number.";
        }
        return $this;
    }

    public function money(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !preg_match('/^\d+(\.\d{1,2})?$/', (string) $value)) {
            $this->errors[$field][] = "$label must be a valid monetary amount (up to two decimals).";
        }
        return $this;
    }

    public function min(string $field, string $label, float $min): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && (float) $value < $min) {
            $this->errors[$field][] = "$label must be at least $min.";
        }
        return $this;
    }

    public function in(string $field, string $label, array $allowed): self
    {
        $value = $this->value($field);
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "$label is invalid.";
        }
        return $this;
    }

    public function same(string $field, string $other, string $label): self
    {
        if ($this->value($field) !== $this->value($other)) {
            $this->errors[$field][] = "$label does not match.";
        }
        return $this;
    }

    public function minLength(string $field, string $label, int $min): self
    {
        $value = (string) $this->value($field);
        if (mb_strlen($value) < $min) {
            $this->errors[$field][] = "$label must be at least $min characters.";
        }
        return $this;
    }
}
