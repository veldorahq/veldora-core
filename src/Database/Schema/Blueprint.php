<?php

declare(strict_types=1);

namespace Veldora\Framework\Database\Schema;

class Blueprint
{
    /**
     * The column definitions.
     *
     * @var array<array{name: string, type: string, length?: int, nullable: bool, default: mixed, auto_increment: bool, primary: bool, unique?: bool, unsigned?: bool, precision?: int, scale?: int, allowed?: array<string>}>
     */
    protected array $columns = [];

    /**
     * The index definitions.
     *
     * @var array<array{type: string, name: string, columns: array<string>}>
     */
    protected array $indexes = [];

    /**
     * The foreign key definitions.
     *
     * @var array<array{column: string, references: string, on: ?string, onDelete: string, onUpdate: string}>
     */
    protected array $foreignKeys = [];

    /**
     * Additional table commands.
     *
     * @var array<array{type: string, name: string}>
     */
    protected array $commands = [];

    /**
     * Create a new Blueprint instance.
     */
    public function __construct(protected string $table)
    {
    }

    /**
     * Add auto-increment primary key ID.
     */
    public function id(string $name = 'id'): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'id',
            'nullable' => false,
            'default' => null,
            'auto_increment' => true,
            'primary' => true,
        ];
        return $this;
    }

    /**
     * Add a VARCHAR column.
     */
    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'string',
            'length' => $length,
            'nullable' => false,
            'default' => null,
            'auto_increment' => false,
            'primary' => false,
        ];
        return $this;
    }

    /**
     * Add a TEXT column.
     */
    public function text(string $name): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'text',
            'nullable' => false,
            'default' => null,
            'auto_increment' => false,
            'primary' => false,
        ];
        return $this;
    }

    /**
     * Add an INTEGER column.
     */
    public function integer(string $name): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'integer',
            'nullable' => false,
            'default' => null,
            'auto_increment' => false,
            'primary' => false,
        ];
        return $this;
    }

    /**
     * Add a TIMESTAMP column.
     */
    public function timestamp(string $name, bool $nullable = true): self
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'timestamp',
            'nullable' => $nullable,
            'default' => null,
            'auto_increment' => false,
            'primary' => false,
        ];
        return $this;
    }

    /**
     * Add standard created_at and updated_at timestamps.
     */
    public function timestamps(): self
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
        return $this;
    }

    /**
     * Add a BOOLEAN column (stored as TINYINT/INTEGER).
     */
    public function boolean(string $name): self
    {
        $this->columns[] = [
            'name'          => $name,
            'type'          => 'boolean',
            'nullable'      => false,
            'default'       => null,
            'auto_increment'=> false,
            'primary'       => false,
        ];
        return $this;
    }

    /**
     * Add a softDeletes deleted_at timestamp.
     */
    public function softDeletes(): self
    {
        $this->timestamp('deleted_at');
        return $this;
    }

    /**
     * Mark the last added column as nullable.
     */
    public function nullable(): self
    {
        $last = array_key_last($this->columns);
        if ($last !== null) {
            $this->columns[$last]['nullable'] = true;
        }
        return $this;
    }

    /**
     * Set a default value on the last added column.
     */
    public function default(mixed $value): self
    {
        $last = array_key_last($this->columns);
        if ($last !== null) {
            $this->columns[$last]['default'] = $value;
        }
        return $this;
    }

    /**
     * Mark the last added column as unique.
     */
    public function unique(): self
    {
        $last = array_key_last($this->columns);
        if ($last !== null) {
            $this->columns[$last]['unique'] = true;
        }
        return $this;
    }

    /**
     * Add a BIGINT column.
     */
    public function bigInteger(string $name, bool $autoIncrement = false, bool $unsigned = false): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'bigInteger',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => $autoIncrement,
            'primary'        => false,
            'unsigned'       => $unsigned,
        ];
        return $this;
    }

    /**
     * Add an UNSIGNED INTEGER column.
     */
    public function unsignedInteger(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'unsignedInteger',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a foreign key ID column (unsigned big integer / integer).
     */
    public function foreignId(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'foreignId',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a DATE column.
     */
    public function date(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'date',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a DATETIME column.
     */
    public function dateTime(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'dateTime',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a DECIMAL column with precision and scale.
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'decimal',
            'precision'      => $precision,
            'scale'          => $scale,
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a FLOAT column.
     */
    public function float(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'float',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a JSON column.
     */
    public function json(string $name): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'json',
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add an ENUM column with allowed values.
     *
     * @param array<string> $allowed
     */
    public function enum(string $name, array $allowed): self
    {
        $this->columns[] = [
            'name'           => $name,
            'type'           => 'enum',
            'allowed'        => $allowed,
            'nullable'       => false,
            'default'        => null,
            'auto_increment' => false,
            'primary'        => false,
        ];
        return $this;
    }

    /**
     * Add a remember_token string column (nullable, 100 chars).
     */
    public function rememberToken(): self
    {
        return $this->string('remember_token', 100)->nullable();
    }

    /**
     * Add an index to one or more columns.
     *
     * @param string|array<string> $columns
     */
    public function index(string|array $columns, ?string $name = null): self
    {
        $columns = (array) $columns;
        $name = $name ?? ($this->table . '_' . implode('_', $columns) . '_index');
        $this->indexes[] = [
            'type' => 'index',
            'name' => $name,
            'columns' => $columns,
        ];
        return $this;
    }

    /**
     * Define a foreign key constraint.
     */
    public function foreign(string $column): self
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => 'id',
            'on' => null,
            'onDelete' => 'CASCADE',
            'onUpdate' => 'CASCADE',
        ];
        return $this;
    }

    /**
     * Specify the referenced column for the foreign key.
     */
    public function references(string $column): self
    {
        $last = array_key_last($this->foreignKeys);
        if ($last !== null) {
            $this->foreignKeys[$last]['references'] = $column;
        }
        return $this;
    }

    /**
     * Specify the referenced table for the foreign key.
     */
    public function on(string $table): self
    {
        $last = array_key_last($this->foreignKeys);
        if ($last !== null) {
            $this->foreignKeys[$last]['on'] = $table;
        }
        return $this;
    }

    /**
     * Specify the ON DELETE action for the foreign key.
     */
    public function onDelete(string $action): self
    {
        $last = array_key_last($this->foreignKeys);
        if ($last !== null) {
            $this->foreignKeys[$last]['onDelete'] = strtoupper($action);
        }
        return $this;
    }

    /**
     * Specify the ON UPDATE action for the foreign key.
     */
    public function onUpdate(string $action): self
    {
        $last = array_key_last($this->foreignKeys);
        if ($last !== null) {
            $this->foreignKeys[$last]['onUpdate'] = strtoupper($action);
        }
        return $this;
    }

    /**
     * Constrain the previous foreignId column.
     */
    public function constrained(?string $table = null, string $column = 'id'): self
    {
        $lastCol = array_key_last($this->columns);
        $colName = $lastCol !== null ? $this->columns[$lastCol]['name'] : 'id';
        
        if ($table === null) {
            // Infer table name: e.g. user_id -> users
            $base = preg_replace('/_id$/', '', $colName) ?? $colName;
            $table = $base . 's';
        }

        $this->foreignKeys[] = [
            'column' => $colName,
            'references' => $column,
            'on' => $table,
            'onDelete' => 'CASCADE',
            'onUpdate' => 'CASCADE',
        ];

        return $this;
    }

    /**
     * Drop one or more columns from the table.
     *
     * @param string|array<string> $columns
     */
    public function dropColumn(string|array $columns): self
    {
        foreach ((array) $columns as $column) {
            $this->commands[] = ['type' => 'dropColumn', 'name' => $column];
        }
        return $this;
    }

    /**
     * Compile table schema creation to SQL.
     */
    public function toSql(string $driver): string
    {
        $columnStatements = [];
        $uniqueConstraints = [];
        $foreignStatements = [];

        foreach ($this->columns as $column) {
            $columnStatements[] = $this->compileColumn($column, $driver);

            // Collect UNIQUE constraints (SQLite handles inline; MySQL uses separate KEY)
            if (!empty($column['unique']) && $column['type'] !== 'id') {
                $uniqueConstraints[] = 'UNIQUE (`' . $column['name'] . '`)';
            }
        }

        foreach ($this->foreignKeys as $fk) {
            if ($fk['on'] !== null) {
                $foreignStatements[] = "FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['on']}`(`{$fk['references']}`) ON DELETE {$fk['onDelete']} ON UPDATE {$fk['onUpdate']}";
            }
        }

        $indexStatements = [];
        if ($driver === 'mysql') {
            foreach ($this->indexes as $idx) {
                $cols = implode(', ', array_map(fn($c) => "`{$c}`", $idx['columns']));
                $indexStatements[] = "INDEX `{$idx['name']}` ({$cols})";
            }
        }

        $all = array_merge($columnStatements, $uniqueConstraints, $foreignStatements, $indexStatements);

        $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->table . '` (' . implode(', ', $all) . ');';

        if ($driver === 'sqlite' && !empty($this->indexes)) {
            foreach ($this->indexes as $idx) {
                $cols = implode(', ', array_map(fn($c) => "`{$c}`", $idx['columns']));
                $sql .= " CREATE INDEX IF NOT EXISTS `{$idx['name']}` ON `{$this->table}` ({$cols});";
            }
        }

        return $sql;
    }

    /**
     * Compile table alterations into SQL statements.
     *
     * @return array<string>
     */
    public function toAlterSql(string $driver): array
    {
        $statements = [];

        foreach ($this->columns as $column) {
            $colSql = $this->compileColumn($column, $driver);
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN {$colSql};";
        }

        foreach ($this->commands as $command) {
            if ($command['type'] === 'dropColumn') {
                $statements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$command['name']}`;";
            }
        }

        foreach ($this->indexes as $idx) {
            $cols = implode(', ', array_map(fn($c) => "`{$c}`", $idx['columns']));
            $statements[] = "CREATE INDEX IF NOT EXISTS `{$idx['name']}` ON `{$this->table}` ({$cols});";
        }

        return $statements;
    }

    /**
     * Compile a single column structure based on driver.
     *
     * @param array{name: string, type: string, length?: int, nullable: bool, default: mixed, auto_increment: bool, primary: bool, unique?: bool} $column
     */
    protected function compileColumn(array $column, string $driver): string
    {
        $name    = '`' . $column['name'] . '`';
        $typeSql = '';

        if ($driver === 'sqlite') {
            if ($column['type'] === 'id') {
                return "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
            }

            $typeSql = match ($column['type']) {
                'string'          => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
                'text'            => 'TEXT',
                'integer'         => 'INTEGER',
                'bigInteger'      => 'INTEGER',
                'unsignedInteger' => 'INTEGER',
                'foreignId'       => 'INTEGER',
                'boolean'         => 'INTEGER',
                'timestamp'       => 'DATETIME',
                'dateTime'        => 'DATETIME',
                'date'            => 'DATE',
                'decimal'         => 'NUMERIC',
                'float'           => 'REAL',
                'json'            => 'TEXT',
                'enum'            => 'TEXT',
                default           => 'TEXT',
            };
        } else {
            // MySQL
            if ($column['type'] === 'id') {
                return "{$name} BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
            }

            $typeSql = match ($column['type']) {
                'string'          => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
                'text'            => 'TEXT',
                'integer'         => 'INT',
                'bigInteger'      => (!empty($column['unsigned']) ? 'BIGINT UNSIGNED' : 'BIGINT'),
                'unsignedInteger' => 'INT UNSIGNED',
                'foreignId'       => 'BIGINT UNSIGNED',
                'boolean'         => 'TINYINT(1)',
                'timestamp'       => 'TIMESTAMP',
                'dateTime'        => 'DATETIME',
                'date'            => 'DATE',
                'decimal'         => 'DECIMAL(' . ($column['precision'] ?? 8) . ', ' . ($column['scale'] ?? 2) . ')',
                'float'           => 'DOUBLE',
                'json'            => 'JSON',
                'enum'            => 'ENUM(' . implode(', ', array_map(fn($val) => "'" . addslashes((string) $val) . "'", $column['allowed'] ?? [])) . ')',
                default           => 'TEXT',
            };
        }

        $nullSql = $column['nullable'] ? ' NULL' : ' NOT NULL';

        $defaultSql = '';
        if ($column['default'] !== null) {
            if (is_bool($column['default'])) {
                $defaultSql = ' DEFAULT ' . ($column['default'] ? '1' : '0');
            } elseif (is_string($column['default'])) {
                $defaultSql = " DEFAULT '{$column['default']}'";
            } else {
                $defaultSql = ' DEFAULT ' . $column['default'];
            }
        } elseif ($column['nullable'] && in_array($column['type'], ['timestamp', 'dateTime', 'date', 'string', 'text', 'boolean', 'integer', 'bigInteger', 'unsignedInteger', 'foreignId', 'decimal', 'float', 'json'], true)) {
            $defaultSql = ' DEFAULT NULL';
        }

        return "{$name} {$typeSql}{$nullSql}{$defaultSql}";
    }
}
