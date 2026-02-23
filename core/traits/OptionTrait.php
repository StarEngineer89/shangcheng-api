<?php
namespace core\traits;

/**
 * 设置参数
 * Trait OptionTrait
 * @package core\traits
 */
trait OptionTrait
{

    protected array $item = [];

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function getItem(string $key, $default = null)
    {
        return $this->item[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setItem(string $key, $value): static
    {
        $this->item[$key] = $value;
        return $this;
    }

    /**
     * 重置
     */
    public function reset()
    {
        $this->item = [];
    }

}
