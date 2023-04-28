<?php

class DB
{
    private static $pdo;
    private        $table;
    private        $select = '*';
    private        $joins  = '';
    private        $where  = '';
    private        $params = [];


    public static function connect($host, $user, $password, $dbname)
    {
        self::$pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function table($table)
    {
        return new static(self::$pdo, $table);
    }

    public function __construct(PDO $pdo, $table)
    {
        self::$pdo   = $pdo;
        $this->table = $table;
    }

    public function select($columns)
    {
        $this->select = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    public function join($table, $on, $type = '')
    {
        $this->joins .= " $type JOIN $table ON $on";
        return $this;
    }

    public function leftJoin($table, $on)
    {
        return $this->join($table, $on, 'LEFT');
    }

    public function where($column, $operator, $value)
    {
        $this->where    = " WHERE $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function whereOr($column, $operator, $value)
    {
        $this->where    .= " OR $column $operator ?";
        $this->params[] = $value;
        return $this;
    }

    public function like($column, $value)
    {
        $this->where    = " WHERE $column LIKE ?";
        $this->params[] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = " ORDER BY $column $direction";
        return $this;
    }

    public function limit($count)
    {
        $this->limit = " LIMIT $count";
        return $this;
    }

    public function offset($count)
    {
        $this->offset = " OFFSET $count";
        return $this;
    }

    public function paginate($perPage, $page)
    {
        $this->limit = " LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        return $this;
    }

    public function count()
    {
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM $this->table $this->joins $this->where");
        $stmt->execute($this->params);
        return $stmt->fetchColumn();
    }

    public function pages($perPage)
    {
        $count = $this->count();
        return ceil($count / $perPage);
    }

    public function insert(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $values  = implode(', ', array_fill(0, count($data), '?'));
        $sql     = "INSERT INTO $this->table ($columns) VALUES ($values)";
        $stmt    = self::$pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return $stmt->rowCount();
    }

    public function update(array $data)
    {
        $set = '';
        foreach ($data as $column => $value) {
            $set            .= "$column=?, ";
            $this->params[] = $value;
        }
        $set  = rtrim($set, ', ');
        $sql  = "UPDATE $this->table SET $set $this->where";
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    public function delete()
    {
        $sql  = "DELETE FROM $this->table $this->where";
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($this->params);
        return $stmt->rowCount();
    }

    public function toSql()
    {
        return "SELECT $this->select FROM $this->table $this->joins $this->where $this->orderBy $this->limit $this->offset";
    }

    public function get()
    {
        $stmt = self::$pdo->prepare($this->toSql());
        $stmt->execute($this->params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first()
    {
        $stmt = self::$pdo->prepare($this->toSql() . " LIMIT 1");
        $stmt->execute($this->params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
