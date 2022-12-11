<?php

namespace App\Models;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Tournament extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    private $tableName = 'tournaments';


    /**
     * @return array
     */
    public function select(): array
    {
        $tableName = $this->tableName;

        $query = "SELECT * FROM `{$tableName}`";

        return DB::select($query);
    }


    /**
     * @param array|null $data
     * @return void
     */
    public function insert(?array $data): void
    {

        $tableName = $this->tableName;

        $prepared = [];
        foreach ($data as $field => $value) {
            $fields[] = "`$field`";
            $values[] = "?";
            $prepared[] = $value;
        }

        $query = "INSERT INTO `{$tableName}`(" . implode(",", $fields) . ")
                    VALUES (" . implode(",", $values) . ")";

        DB::insert($query, $prepared);
    }


    /**
     * @param string $key
     * @param string $value
     * @param bool $amount
     * @param int $limit
     * @param int $offset
     * @return array|mixed
     */
    public function selectByColumn(string $key, string $value, bool $amount = false, int $limit = 0, int $offset = 0)
    {
        $tableName = $this->tableName;
        $params = [
            $key => $value
        ];

        $query = "SELECT * FROM `{$tableName}` WHERE {$key} = :{$key}";

        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }

        return ($amount) ?  DB::select($query, $params) : DB::selectOne($query, $params);
    }

    /**
     * @param array $data
     * @param array $attribute
     * @return void
     */
    public function updateData(array $data, array $attribute): void
    {

        foreach ($data as $field => $value) {
            $fields[] = "$field =:$field";
        }

        $tableName = $this->tableName;

        $arrayMerge = array_merge($data, $attribute);

        $query = "UPDATE `{$tableName}` SET " . implode(",", $fields) .
                " WHERE " . array_key_first($attribute) . " =:" . array_key_first($attribute);

        DB::update($query, $arrayMerge);
    }

    public function countData(string $key, string $value)
    {
        $tableName = $this->tableName;
        $params = [
            $key => $value
        ];

        $query = "SELECT COUNT(*) as count FROM `{$tableName}` WHERE {$key} = :{$key}";
        $select = DB::select($query, $params);

        foreach ($select as $value) {
            return $value->count;
        }
    }

}
