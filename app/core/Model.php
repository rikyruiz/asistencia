<?php
/**
 * Base Model Class
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1";
        return $this->db->selectOne($sql, ['id' => $id]);
    }

    /**
     * Find record by field
     */
    public function findBy($field, $value) {
        $sql = "SELECT * FROM `{$this->table}` WHERE `$field` = :value LIMIT 1";
        return $this->db->selectOne($sql, ['value' => $value]);
    }

    /**
     * Get all records
     */
    public function all($orderBy = null, $order = 'ASC') {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($orderBy) {
            $sql .= " ORDER BY `$orderBy` $order";
        }
        return $this->db->select($sql);
    }

    /**
     * Get records with conditions
     */
    public function where($conditions = [], $orderBy = null, $order = 'ASC', $limit = null) {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                // Handle operators like ['>', 5] or ['BETWEEN', [1, 10]]
                $operator = $value[0];
                $val = $value[1];

                if ($operator === 'IN') {
                    $placeholders = array_map(function($i) use ($field) {
                        return ":in_{$field}_$i";
                    }, array_keys($val));
                    $where[] = "`$field` IN (" . implode(',', $placeholders) . ")";
                    foreach ($val as $i => $v) {
                        $params["in_{$field}_$i"] = $v;
                    }
                } elseif ($operator === 'BETWEEN') {
                    $where[] = "`$field` BETWEEN :between_{$field}_1 AND :between_{$field}_2";
                    $params["between_{$field}_1"] = $val[0];
                    $params["between_{$field}_2"] = $val[1];
                } else {
                    $where[] = "`$field` $operator :$field";
                    $params[$field] = $val;
                }
            } else {
                $where[] = "`$field` = :$field";
                $params[$field] = $value;
            }
        }

        $sql = "SELECT * FROM `{$this->table}`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        if ($orderBy) {
            $sql .= " ORDER BY `$orderBy` $order";
        }
        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        return $this->db->select($sql, $params);
    }

    /**
     * Create new record
     */
    public function create($data) {
        // Filter only fillable fields
        $filtered = $this->filterFillable($data);

        // Add timestamps if enabled
        if ($this->timestamps) {
            $filtered['creado_en'] = date('Y-m-d H:i:s');
            $filtered['actualizado_en'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert($this->table, $filtered);
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        // Filter only fillable fields
        $filtered = $this->filterFillable($data);

        // Update timestamp if enabled
        if ($this->timestamps) {
            $filtered['actualizado_en'] = date('Y-m-d H:i:s');
        }

        $where = "`{$this->primaryKey}` = :pk_id";
        return $this->db->update($this->table, $filtered, $where, ['pk_id' => $id]);
    }

    /**
     * Delete record
     */
    public function delete($id) {
        $where = "`{$this->primaryKey}` = :id";
        return $this->db->delete($this->table, $where, ['id' => $id]);
    }

    /**
     * Count records
     */
    public function count($conditions = []) {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "`$field` = :$field";
            $params[$field] = $value;
        }

        $sql = "SELECT COUNT(*) as count FROM `{$this->table}`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $result = $this->db->selectOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Check if record exists
     */
    public function exists($field, $value, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM `{$this->table}` WHERE `$field` = :value";
        $params = ['value' => $value];

        if ($excludeId !== null) {
            $sql .= " AND `{$this->primaryKey}` != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $result = $this->db->selectOne($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }

        $filtered = [];
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }
        return $filtered;
    }

    /**
     * Remove hidden fields from result
     */
    protected function filterHidden($data) {
        if (empty($this->hidden)) {
            return $data;
        }

        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = null, $order = 'ASC') {
        $offset = ($page - 1) * $perPage;

        // Get total count
        $total = $this->count($conditions);

        // Get paginated data
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $where[] = "`$field` = :$field";
            $params[$field] = $value;
        }

        $sql = "SELECT * FROM `{$this->table}`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        if ($orderBy) {
            $sql .= " ORDER BY `$orderBy` $order";
        }
        $sql .= " LIMIT $perPage OFFSET $offset";

        $data = $this->db->select($sql, $params);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Execute raw query
     */
    public function raw($sql, $params = []) {
        return $this->db->select($sql, $params);
    }

    /**
     * Get database instance
     */
    protected function getDb() {
        return $this->db;
    }
}