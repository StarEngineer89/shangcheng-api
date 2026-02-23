<?php
namespace core\basic;

use core\traits\ModelTrait;
use think\db\Query;
use think\Model;
use core\topthink\HasManyThrough;

/**
 * Class BaseModel
 * @package core\basic
 * @mixin ModelTrait
 * @mixin Query
 */
class BaseModel extends Model
{

    /**
     * 重写一对多关联
     * @param string $model
     * @param string $through
     * @param string $foreignKey
     * @param string $throughKey
     * @param string $localKey
     * @param string $throughPk
     * @return HasManyThrough
     * @author MrBruce
     */
    public function hasManyThrough(string $model, string $through, string $foreignKey = '', string $throughKey = '', string $localKey = '', string $throughPk = ''): HasManyThrough
    {
        // 记录当前关联信息
        $model = $this->parseModel($model);
        $through = $this->parseModel($through);
        $localKey = $localKey ?: $this->getPk();
        $foreignKey = $foreignKey ?: $this->getForeignKey($this->name);
        $throughKey = $throughKey ?: $this->getForeignKey((new $through())->getName());
        $throughPk = $throughPk ?: (new $through())->getPk();

        return new HasManyThrough($this, $model, $through, $foreignKey, $throughKey, $localKey, $throughPk);
    }

}
