<?php

namespace Base;

use Lib\Extend\Database\Adapter;

class BaseModel extends Adapter
{
    /**
     * 根据主键更新数据
     * @param int $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    final public function updateById(int $id, array $data)
    {
        $table = $this->getSource();
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = "`{$key}`=?";
            $params[] = $val;
        }
        $params[] = $id;
        $fieldsStr = implode(',', $fields);
        $sql = "UPDATE `{$table}` SET {$fieldsStr} WHERE `id`=?";
        $result = $this->exec($sql, $params);
        return $result;
    }

    /**
     * 根据主键删除数据
     * @param int $id
     * @return mixed
     * @throws \Exception
     */
    final public function deleteById(int $id)
    {
        $table = $this->getSource();
        $sql = "DELETE FROM `{$table}` WHERE `id`=?";
        $result = $this->exec($sql, [$id]);
        return $result;
    }

    /**
     * 根据主键批量(IN)更新数据
     * @param array $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    final public function updateInById(array $id, array $data)
    {
        $table = $this->getSource();
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = "`{$key}`=?";
            $params[] = $val;
        }
        for ($i = 0; $i < count($id); $i++) {
            $placeholder[] = '?';
        }
        $_params = array_merge($params, $id);
        $fieldsStr = implode(',', $fields);
        $placeholderStr = implode(',', $placeholder);
        $sql = "UPDATE `{$table}` SET {$fieldsStr} WHERE `id` IN({$placeholderStr})";
        $result = $this->exec($sql, $_params);
        return $result;
    }

    /**
     * 根据主键id查询数据
     * @param int $id
     * @param array $fields
     * @return array
     * @throws \Exception
     */
    final public function getById(int $id, array $fields): array
    {
        if (empty($fields)) {
            throw new \Exception("查询字段不能为空！");
        }
        $table = $this->getSource();
        $fieldsStr = implode(',', $fields);
        $sql = "SELECT {$fieldsStr} FROM `{$table}` WHERE `id`=? LIMIT 0,1";
        $data = $this->select($sql, [$id]);
        return !empty($data) ? $data[0] : [];
    }

    /**
     * 根据主键批量(IN)查询数据
     * @param array $id
     * @param array $fields
     * @return array
     * @throws \Exception
     */
    final public function getInById(array $id, array $fields): array
    {
        if (empty($fields)) {
            throw new \Exception("查询字段不能为空！");
        }
        $table = $this->getSource();
        for ($i = 0; $i < count($id); $i++) {
            $placeholder[] = '?';
        }
        $fieldsStr = implode(',', $fields);
        $placeholderStr = implode(',', $placeholder);
        $sql = "SELECT {$fieldsStr} FROM `{$table}` WHERE `id` IN({$placeholderStr})";
        $data = $this->select($sql, $id);
        return $data;
    }

    /**
     * 查询一条记录
     * @param array $where
     * @param array $fields
     * @param string $orderBy
     * @return array
     * @throws \Exception
     */
    final public function getOne(array $where, array $fields, string $orderBy=''):array
    {
        $table = $this->getSource();
        $whereData = $this->_where($where);
        $_where = $whereData['where'];
        $_params = $whereData['params'];
        $fields = $this->_fields($fields);
        if(!empty($orderBy)){
            $orderBy = "order by {$orderBy}";
        }
        $sql = "SELECT {$fields} FROM `{$table}` WHERE {$_where} {$orderBy} LIMIT 0,1";
        $data = $this->select($sql, $_params);
        return !empty($data) ? $data[0] : [];
    }

    /**
     * 查询多条记录
     * @param array $where
     * @param array $fields
     * @param string $orderBy
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    final public function getList(array $where, array $fields, string $orderBy='', int $offset=0,int $limit=0):array
    {
        $table = $this->getSource();
        $whereData = $this->_where($where);
        $_where = $whereData['where'];
        $_params = $whereData['params'];
        $fields = $this->_fields($fields);
        if(!empty($orderBy)){
            $orderBy = "order by {$orderBy}";
        }
        $limitStr = $limit !== 0 ? "LIMIT {$offset},{$limit}" : '';
        $sql = "SELECT {$fields} FROM `{$table}` WHERE {$_where} {$orderBy} {$limitStr}";
        $data = $this->select($sql, $_params);
        return $data;
    }

    /**
     * 查询统计(count)
     * @param array $where
     * @return int
     * @throws \Exception
     */
    final public function getCounts(array $where)
    {
        $table = $this->getSource();
        $whereData = $this->_where($where);
        $_where = $whereData['where'];
        $_params = $whereData['params'];
        $sql = "SELECT count(*) counts FROM `{$table}` WHERE {$_where}";
        $data = $this->select($sql, $_params);
        $count = !empty($data) ? $data[0]['counts'] : 0;
        return $count;
    }

    /**
     * 统计查询(sum)
     * @param array $where
     * @param array $fields
     * @return array
     * @throws \Exception
     */
    public function getSum(array $where,array $fields):array
    {
        $table = $this->getSource();
        $whereData = $this->_where($where);
        $_where = $whereData['where'];
        $_params = $whereData['params'];
        if(empty($fields)){
            throw new \Exception('sql异常:sum字段不能为空!');
        }
        foreach ($fields as $value) {
            $field[] = "sum({$value}) as {$value}_sum";
        }
        $fields = implode(',', $field);
        $sql = "SELECT {$fields} FROM `{$table}` WHERE {$_where}";
        $data = $this->select($sql, $_params);
        return !empty($data) ? $data[0] : [];
    }

    /**
     * 插入单条记录
     * @param array $data
     * @return int
     * @throws \Exception
     */
    final public function insert(array $data):int
    {
        $table = $this->getSource();
        $fields = [];
        $insertData = [];
        $placeholder = [];
        foreach ($data as $key => $value) {
            $fields[] = "`{$key}`";
            $placeholder[] = '?';
            $insertData[] = $value;
        }
        $fieldsStr = implode(',', $fields);
        $placeholder = implode(',', $placeholder);
        $sql = "INSERT INTO `{$table}` ({$fieldsStr}) VALUES ({$placeholder})";
        $result = $this->exec($sql, $insertData);
        if ($result) {
            $lastInsertId = $this->_connect->lastInsertId();
        }
        return $lastInsertId ? $lastInsertId : 0;
    }

    /**
     * 批量插入记录
     * @param array $datas
     * @return mixed
     * @throws \Exception
     */
    final public function batchInsert(array $datas)
    {
        $table = $this->getSource();
        $keys = array_keys(reset($datas));
        $keys = array_map(function ($value) {
            return "`{$value}`";
        }, $keys);
        $keys = implode(',', $keys);
        $sql = "INSERT INTO `{$table}` ({$keys}) VALUES ";
        foreach ($datas as $data) {
            $data = array_map(function ($value) {
                $addslashes = addslashes($value);
                return "'{$addslashes}'";
            }, $data);
            $values = implode(',', $data);
            $sql .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');
        return $this->exec($sql);
    }

    /**
     * 删除记录
     * @param array $where
     * @return bool
     * @throws \Exception
     */
    final public function deleteData(array $where):bool
    {
        $table = $this->getSource();
        $whereData = $this->_where($where);
        $_where = $whereData['where'];
        $_params = $whereData['params'];
        $sql = "DELETE FROM `{$table}` WHERE {$_where}";
        $result = $this->exec($sql, $_params);
        return $result;
    }

    /**
     * 更新记录
     * @param array $data
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    final public function updateData(array $where, array $data)
    {
        $table = $this->getSource();
        $filedData = $this->_update($data);
        $whereData = $this->_where($where);
        $_fieldValue = $filedData['fieldValue'];
        $_where = $whereData['where'];
        $params = array_merge($filedData['params'], $whereData['params']);
        $sql = "UPDATE `{$table}` SET {$_fieldValue} WHERE {$_where}";
        $result = $this->exec($sql, $params);
        return $result;
    }

    /**
     * 处理where条件
     * array(
     *     'id'     => 1                         // id=1
     *     'status' => ['in', [1,2]]             // status in (1,2)
     *     'name'   => ['like', '%PHP%']         // name like '%PHP%'
     *     'year'   => ['between', [2018,2019]]  // year between 2018 AND 2019
     *     'age'    => ['>', 10]                 // age > 10
     *     '_sql'   => ['_sql', "(status=1 or status=3)"]
     * )
     * 默认 and 连接
     * @param array $where
     * @return array
     */
    final private function _where(array $where):array
    {
        if(empty($where)){
            return ['where'=>'1=1', 'params'=>[]];
        }
        $condition = [];
        $bindParams = [];

        foreach ($where as $key => $value) {
            if(empty($key) || is_numeric($key)){
                continue;
            }

            if(is_array($value)){
                $operator = $value[0];
                if(is_string($operator)){
                    $operator = trim(strtolower($operator));
                }
                if(in_array($operator,['>', '>=', '<', '<=', 'like', '!=', '<>'])){
                    $condition[] = "{$key} {$operator} ?";
                    $bindParams[] = $value[1];
                }else if($operator === 'between' && is_array($value[1])){
                    $condition[] = "{$key} between ? AND ?";
                    $bindParams[] = $value[1][0];
                    $bindParams[] = $value[1][1];
                }else if($operator === 'in' && is_array($value[1])){
                    $placeholder = [];
                    foreach ($value[1] as $v) {
                        $placeholder[] = '?';
                        $bindParams[] = $v;
                    }
                    $placeholderStr = implode(',',$placeholder);
                    $condition[] = "{$key} IN ({$placeholderStr})";
                }else if($operator === '_sql'){
                    $bindParams[] = $value[1];
                }
            }else{
                $condition[] = "{$key}=?";
                $bindParams[] = $value;
            }
        }
        $conditionStr = '1=1';
        if(!empty($condition)){
            $conditionStr = implode(' AND ', $condition);
        }

        return ['where'=>$conditionStr, 'params'=>$bindParams];
    }

    /**
     * 处理需要查询的字段
     * @param array $fields
     * @return string
     * @throws \Exception
     */
    final private function _fields(array $fields):string
    {
        if (empty($fields)) {
            throw new \Exception('sql异常:查询字段不能为空!');
        }
        $fieldsStr = implode(',', $fields);
        return $fieldsStr;
    }

    /**
     * 处理更新数据
     * @param array $data
     * @return array
     */
    final private function _update(array $data):array
    {
        $bindParams = [];
        $fields = [];
        foreach ($data as $key => $val) {
            $fields[] = "{$key}=?";
            $bindParams[] = $val;
        }
        $fieldsStr = implode(',', $fields);
        return ['fieldValue'=>$fieldsStr, 'params'=>$bindParams];
    }
}