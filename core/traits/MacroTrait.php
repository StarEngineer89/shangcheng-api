<?php
namespace core\traits;

use app\Request;

/**
 * Trait MacroTrait
 * @package core\traits
 * @property Request $request
 */
trait MacroTrait
{

    /**
     * 获取request内的值
     * @param string $name
     * @param null $default
     * @return null
     */
    public function getMacro(string $name, $default = null)
    {
        return $this->request->hasMacro($name) ? $this->request->{$name}() : $default;
    }
}
