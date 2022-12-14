<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TournamentRosterTeam extends Model
{
    use HasFactory;

    private $tableName = 'tournament_teams';


    /**
     * @param array $params
     * @return mixed
     */
    public function selectByColumn(array $params, bool $amount = false, int $limit = 0, int $offset = 0)
    {
        $tableName = $this->tableName;
        $key = [];
        foreach ($params as $k => $value) {
            $key[] = $k;
        }

        $query = "SELECT * FROM `{$tableName}` WHERE {$key[0]} = :{$key[0]}";
        for($i = 1; $i < count($key); $i++) {
            $query .= " AND {$key[$i]} = :{$key[$i]}";
        }

        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }

        return ($amount) ?  DB::select($query, $params) : DB::selectOne($query, $params);
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

        $dataAttribute = array_merge($data, $attribute);

        $query = "UPDATE `{$tableName}` SET " . implode(",", $fields) .
            " WHERE " . array_key_first($attribute) . " =:" . array_key_first($attribute);

        DB::update($query, $dataAttribute);
    }

    /**
     * @param string $key
     * @param string $value
     * @return int
     */
    public function countData(string $key, string $value): int
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
