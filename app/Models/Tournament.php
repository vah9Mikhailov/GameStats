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
    private $name = 'tournaments';


    /**
     * @return array
     */
    public function select(): array
    {
        $tableName = $this->name;

        $query = "SELECT * FROM `{$tableName}`";
        $result = DB::select($query);

        return $result;
    }


    /**
     * @param array|null $data
     * @return void
     */
    public function insert(?array $data): void
    {

        $tableName = $this->name;

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
     * @return object|null
     */
    public function selectByColumn(string $key, string $value): ?object
    {
        $tableName = $this->name;
        $params = [
            $key => $value
        ];

        $query = "SELECT * FROM `{$tableName}` WHERE {$key} = :{$key}";

        $result = DB::selectOne($query, $params);

        return $result;
    }

    public function updateData(array $data, array $attribute): void
    {

        foreach ($data as $field => $value) {
            $fields[] = "$field =:$field";
        }

        $tableName = $this->name;

        $arrayMerge = array_merge($data, $attribute);

        $query = "UPDATE `{$tableName}` SET " . implode(",", $fields) .
                " WHERE " . array_key_first($attribute) . " =:" . array_key_first($attribute);

        DB::update($query, $arrayMerge);
    }

}
