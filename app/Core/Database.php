<?php

declare(strict_types=1);

namespace PetCare\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

        try {
            self::$pdo = new PDO($config['dsn'], $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
            self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        } catch (PDOException $e) {
            throw new PDOException('Não foi possível conectar ao banco de dados PetCare. Verifique config/database.php e se o MySQL está ativo.', 0, $e);
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $name = str_starts_with((string) $key, ':') ? (string) $key : ':' . $key;
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::identifier($table),
            implode(', ', array_map([self::class, 'identifier'], $columns)),
            implode(', ', $placeholders)
        );
        self::query($sql, $data);
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        if ($data === []) {
            return 0;
        }
        $sets = [];
        $bind = [];
        foreach ($data as $column => $value) {
            $sets[] = self::identifier($column) . ' = :set_' . $column;
            $bind['set_' . $column] = $value;
        }
        foreach ($params as $key => $value) {
            $bind['where_' . ltrim((string) $key, ':')] = $value;
        }
        $where = preg_replace_callback('/:([a-zA-Z_][a-zA-Z0-9_]*)/', static fn($m) => ':where_' . $m[1], $where) ?? $where;
        $sql = sprintf('UPDATE %s SET %s WHERE %s', self::identifier($table), implode(', ', $sets), $where);
        return self::query($sql, $bind)->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', self::identifier($table), $where);
        return self::query($sql, $params)->rowCount();
    }

    public static function identifier(string $name): string
    {
        $name = trim($name);
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Identificador SQL inválido.');
        }
        return '`' . $name . '`';
    }

    public static function begin(): void
    {
        self::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->commit();
        }
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }

    public static function transaction(callable $callback): mixed
    {
        self::begin();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollBack();
            throw $e;
        }
    }
}
