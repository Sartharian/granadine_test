
<?php 
class QueryBuilder{
    protected string $escapeChar = '`';   // puedes cambiarlo según el driver
    protected string $escapeCharEnd = '`'; // algunos usan [] -> '[' y ']'

    protected PDO $pdo;

    // partes del builder
    protected array $qbSelect = [];
    protected array $qbFrom = [];
    protected array $qbWhere = [];
    protected array $qbOrder = [];
    protected ?int $qbLimit = null;
    protected ?int $qbOffset = null;
    protected array $qbJoin = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function select(array|string $columns): static {
        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $this->qbSelect = array_merge(
            $this->qbSelect,
            $this->protectIdentifiers($columns)
        );
        return $this;
    }

    public function join(string $table, string $cond, string $type = 'INNER'): static {
        $this->qbJoin[] = strtoupper($type) . ' JOIN ' .
            $this->protectIdentifier($table) .
            ' ON ' . $cond;

        return $this;
    }

    public function from(string $table): static {
        $this->qbFrom[] = $this->protectIdentifier($table);
        return $this;
    }

    public function delete(string $table): bool{
        if (!$this->qbWhere) {
            throw new Exception("DELETE requires WHERE() for safety.");
        }

        $sql = "DELETE FROM " . $this->protectIdentifier($table);

        $whereSql = implode(' AND ', array_map(fn($w) => $w['sql'], $this->qbWhere));
        $sql .= " WHERE " . $whereSql;

        $stmt = $this->pdo->prepare($sql);

        foreach ($this->qbWhere as $w) {
            $stmt->bindValue($w['param'], $w['value']);
        }

        return $stmt->execute();
    }

    public function insert(string $table, array $data): bool {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':p_' . $c, $cols);

        $sql = "INSERT INTO "
            . $this->protectIdentifier($table)
            . " (" . implode(', ', $this->protectIdentifiers($cols)) . ") "
            . "VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $col => $val) {
            $stmt->bindValue(':p_' . $col, $val);
        }

        return $stmt->execute();
    }

    public function update(string $table, array $data): bool{
        if (!$this->qbWhere) {
            throw new Exception("UPDATE requires WHERE() for safety.");
        }

        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $param = ':u_' . $col;
            $sets[] = $this->protectIdentifier($col) . " = {$param}";
            $params[$param] = $val;
        }

        $sql = "UPDATE " . $this->protectIdentifier($table)
            . " SET " . implode(', ', $sets);

        $whereSql = implode(' AND ', array_map(fn($w) => $w['sql'], $this->qbWhere));
        $sql .= " WHERE " . $whereSql;

        $stmt = $this->pdo->prepare($sql);

        // bind set params
        foreach ($params as $p => $v) {
            $stmt->bindValue($p, $v);
        }

        // bind WHERE params
        foreach ($this->qbWhere as $w) {
            $stmt->bindValue($w['param'], $w['value']);
        }

        return $stmt->execute();
    }

    public function where(string $column, $value, string $op = '='): static {
        $param = ':p_' . count($this->qbWhere);

        $this->qbWhere[] = [
            'sql' => $this->protectIdentifier($column) . " {$op} {$param}",
            'value' => $value,
            'param' => $param
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static {
        $this->qbOrder[] = $this->protectIdentifier($column) . ' ' . strtoupper($direction);
        return $this;
    }

    public function like(string $column, string $match, string $side = 'both'): static {
        $column = $this->protectIdentifier($column);

        switch ($side) {
            case 'before':
                $value = '%' . $match;
                break;
            case 'after':
                $value = $match . '%';
                break;
            case 'none':
                $value = $match;
                break;
            default:
                $value = '%' . $match . '%';
                break;
        }

        $param = ':like_' . count($this->qbWhere);

        $this->qbWhere[] = [
            'sql'   => "{$column} LIKE {$param}",
            'param' => $param,
            'value' => $value,
            'or'    => false
        ];

        return $this;
    }

    public function limit(int $limit, int $offset = 0): static {
        $this->qbLimit = $limit;
        $this->qbOffset = $offset;
        return $this;
    }

    public function get(): array {
        $sql = $this->compileSelect();
        $stmt = $this->pdo->prepare($sql);

        foreach ($this->qbWhere as $w) {
            $stmt->bindValue($w['param'], $w['value']);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getData($col) {
        $row = $this->getRow();
        return $row[$col] ?? null;
    }

    public function getRow(): array {
        $sql = $this->compileSelect();
        $stmt = $this->pdo->prepare($sql);

        foreach ($this->qbWhere as $w) {
            $stmt->bindValue($w['param'], $w['value']);
        }

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function begin(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    protected function compileSelect(): string {
        $sql = 'SELECT ';

        $sql .= $this->qbSelect
            ? implode(', ', $this->qbSelect)
            : '*';

        if ($this->qbFrom) {
            $sql .= ' FROM ' . implode(', ', $this->qbFrom);
        }

        if ($this->qbJoin) {
            $sql .= ' ' . implode(' ', $this->qbJoin);
        }

        if ($this->qbWhere) {
            $whereParts = array_map(fn($w) => $w['sql'], $this->qbWhere);
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        if ($this->qbOrder) {
            $sql .= ' ORDER BY ' . implode(', ', $this->qbOrder);
        }

        if ($this->qbLimit !== null) {
            $sql .= ' LIMIT ' . $this->qbLimit;
            if ($this->qbOffset !== null) {
                $sql .= ' OFFSET ' . $this->qbOffset;
            }
        }

        return $sql;
    }

    protected function protectIdentifier(string $item): string {
        // si ya contiene funciones o alias, no tocar
        if (preg_match('/\(|\)|\s+AS\s+/i', $item)) {
            return $item;
        }

        // tabla.columna
        if (strpos($item, '.') !== false) {
            return implode('.', array_map(fn($part) => $this->escapeChar.$part.$this->escapeCharEnd, explode('.', $item)));
        }
        return $this->escapeChar . $item . $this->escapeCharEnd;
    }

    protected function protectIdentifiers(array $items): array {
        return array_map(fn($i) => $this->protectIdentifier($i), $items);
    }
}?>
