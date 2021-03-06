<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Base extends Model
{
    // 软删除
    use SoftDeletes;
    /**
     * 禁止被批量赋值的字段
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * 添加数据
     *
     * @param  array $data 需要添加的数据
     * @return bool        是否成功
     */
    public function addData($data)
    {
        //添加数据
        $result=$this
            ->create($data)
            ->id;
        if ($result) {
            session()->flash('alert-message','添加成功');
            session()->flash('alert-class','alert-success');
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 修改数据
     *
     * @param  array $map  where条件
     * @param  array $data 需要修改的数据
     * @return bool        是否成功
     */
    public function editData($map, $data)
    {
        //查找需要修改的数据
        $model = $this->where($map)->first();
        //修改
        foreach ($data as $k => $v) {
            $model->{$k} = $v;
        }
        $result = $model->save();
        if ($result) {
            session()->flash('alert-message','修改成功');
            session()->flash('alert-class','alert-success');
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 删除数据
     *
     * @param  array $map   where 条件数组形式
     * @return bool         是否成功
     */
    public function deleteData($map)
    {
        //软删除
        $result=$this
            ->where($map)
            ->delete();
        if ($result) {
            session()->flash('alert-message','操作成功');
            session()->flash('alert-class','alert-success');
            return $result;
        }else{
            return false;
        }
    }

    /**
     * 使用作用域扩展 Builder 链式操作
     *
     * 示例:
     * $map = [
     *     'id' => ['in', [1,2,3]],
     *     'category_id' => ['<>', 9],
     *     'tag_id' => 10
     * ]
     *
     * @param $query
     * @param $map
     * @return mixed
     */
    public function scopeWhereMap($query, $map)
    {
        // 如果是空直接返回
        if (empty($map)) {
            return $query;
        }

        // 判断各种方法
        foreach ($map as $k => $v) {
            if (is_array($v)) {
                $sign = strtolower($v[0]);
                switch ($sign) {
                    case 'in':
                        $query->whereIn($k, $v[1]);
                        break;
                    case 'notin':
                        $query->whereNotIn($k, $v[1]);
                        break;
                    case 'between':
                        $query->whereBetween($k, $v[1]);
                        break;
                    case 'notbetween':
                        $query->whereNotBetween($k, $v[1]);
                        break;
                    case 'null':
                        $query->whereNull($k);
                        break;
                    case 'notnull':
                        $query->whereNotNull($k);
                        break;
                    case '=':
                    case '>':
                    case '<':
                    case '<>':
                    case 'like':
                        $query->where($k, $sign, $v[1]);
                        break;
                }
            } else {
                $query->where($k, $v);
            }

        }
        return $query;
    }

    /**
     * 批量更新的方法
     *
     * $multipleData = [
     *    [
     *        'title' => 'My title' ,
     *        'name' => 'My Name 2' ,
     *        'date' => 'My date 2'
     *     ],
     *     [
     *        'title' => 'Another title' ,
     *        'name' => 'Another Name 2' ,
     *        'date' => 'Another date 2'
     *      ]
     *   ]
     *
     * @param array $multipleData
     * @return bool|int
     */
    function updateBatch($multipleData = []){
        // 获取表名
        $tableName = config('database.connections.mysql.prefix').$this->getTable();
        if( $tableName && !empty($multipleData) ) {
            $updateColumn = array_keys($multipleData[0]);
            $referenceColumn = $updateColumn[0];
            unset($updateColumn[0]);
            $whereIn = "";
            // 组合sql语句
            $sql = "UPDATE ".$tableName." SET ";
            foreach ( $updateColumn as $uColumn ) {
                $sql .=  $uColumn." = CASE ";
                foreach( $multipleData as $data ) {
                    $sql .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN '".$data[$uColumn]."' ";
                }
                $sql .= "ELSE ".$uColumn." END, ";
            }
            foreach( $multipleData as $data ) {
                $whereIn .= "'".$data[$referenceColumn]."', ";
            }
            $sql = rtrim($sql, ", ")." WHERE ".$referenceColumn." IN (".  rtrim($whereIn, ', ').")";
            // 更新
            return DB::update(DB::raw($sql));
        } else {
            return false;
        }
    }
}
