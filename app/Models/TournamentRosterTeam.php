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
    public function selectByColumn(array $params)
    {
        $tableName = $this->tableName;
        $key = [];
        foreach ($params as $k => $value) {
            $key[] = $k;
        }

        $query = '';
        for($i = 0; $i < (count($params)-1); $i++ ) {
            $query = "SELECT * FROM `{$tableName}` WHERE {$key[$i]} = :{$key[$i]}";
            if (count($params) > 1) {
                $query .= " AND {$key[$i+1]} = :{$key[$i+1]}";
            }
        }

        return DB::selectOne($query, $params);
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
}
