<?php

declare(strict_types=1);

namespace PetCare\Core;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function all(string $columns = '*', ?string $order = null): array
    {
        $sql = sprintf('SELECT %s FROM %s', $columns, Database::identifier($this->table));
        if ($order !== null) {
            $sql .= ' ORDER BY ' . $order;
        }
        return Database::fetchAll($sql);
    }

    public function find(int|string $id): ?array
    {
        return Database::fetch(
            sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', Database::identifier($this->table), Database::identifier($this->primaryKey)),
            ['id' => $id]
        );
    }

    public function findOrFail(int|string $id): array
    {
        $row = $this->find($id);
        if ($row === null) {
            \abort(404, 'Registro não encontrado.');
        }
        return $row;
    }

    public function where(array $filters, string $columns = '*', ?string $order = null, ?int $limit = null): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = sprintf('SELECT %s FROM %s WHERE %s', $columns, Database::identifier($this->table), $where);
        if ($order !== null) {
            $sql .= ' ORDER BY ' . $order;
        }
        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }
        return Database::fetchAll($sql, $params);
    }

    public function first(array $filters): ?array
    {
        $rows = $this->where($filters, '*', null, 1);
        return $rows[0] ?? null;
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $filters === [] ? ['1=1', []] : $this->buildWhere($filters);
        return (int) Database::scalar(sprintf('SELECT COUNT(*) FROM %s WHERE %s', Database::identifier($this->table), $where), $params);
    }

    public function paginate(array $filters = [], int $perPage = 15, int $page = 1, string $order = 'id DESC', string $columns = '*'): array
    {
        $perPage = max(1, min(100, $perPage));
        $page = max(1, $page);
        [$where, $params] = $filters === [] ? ['1=1', []] : $this->buildWhere($filters);
        $total = (int) Database::scalar(sprintf('SELECT COUNT(*) FROM %s WHERE %s', Database::identifier($this->table), $where), $params);
        $offset = ($page - 1) * $perPage;
        $sql = sprintf('SELECT %s FROM %s WHERE %s ORDER BY %s LIMIT %d OFFSET %d', $columns, Database::identifier($this->table), $where, $order, $perPage, $offset);
        return [
            'rows' => Database::fetchAll($sql, $params),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function create(array $data): int
    {
        return Database::insert($this->table, $this->filterFillable($data));
    }

    public function update(int|string $id, array $data): int
    {
        return Database::update(
            $this->table,
            $this->filterFillable($data),
            Database::identifier($this->primaryKey) . ' = :id',
            ['id' => $id]
        );
    }

    public function delete(int|string $id): int
    {
        return Database::delete($this->table, Database::identifier($this->primaryKey) . ' = :id', ['id' => $id]);
    }

    public function exists(string $column, mixed $value, ?int $ignoreId = null): bool
    {
        $where = Database::identifier($column) . ' = :value';
        $params = ['value' => $value];
        if ($ignoreId !== null) {
            $where .= ' AND ' . Database::identifier($this->primaryKey) . ' <> :ignore';
            $params['ignore'] = $ignoreId;
        }
        return Database::scalar(sprintf('SELECT 1 FROM %s WHERE %s LIMIT 1', Database::identifier($this->table), $where), $params) !== false;
    }

    private function filterFillable(array $data): array
    {
        if ($this->fillable === []) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    private function buildWhere(array $filters): array
    {
        $parts = [];
        $params = [];
        foreach ($filters as $column => $value) {
            Database::identifier((string) $column);
            $key = 'f_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $column);
            if (is_array($value) && count($value) === 2) {
                $operator = strtoupper((string) $value[0]);
                if (!in_array($operator, ['=', '<>', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS NULL', 'IS NOT NULL'], true)) {
                    throw new \InvalidArgumentException('Operador de filtro inválido.');
                }
                $parts[] = Database::identifier((string) $column) . ' ' . $operator . ' :' . $key;
                $params[$key] = $value[1];
            } elseif ($value === null) {
                $parts[] = Database::identifier((string) $column) . ' IS NULL';
            } else {
                $parts[] = Database::identifier((string) $column) . ' = :' . $key;
                $params[$key] = $value;
            }
        }
        return [implode(' AND ', $parts), $params];
    }
}
