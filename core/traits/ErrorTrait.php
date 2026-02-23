<?php
namespace core\traits;

/**
 *
 * Class BaseError
 * @package core\basic
 */
trait ErrorTrait
{
    /**
     * 错误信息
     * @var mixed
     */
    protected mixed $error = '';

    /**
     * 设置错误信息
     * @param string|null $error
     * @return bool
     */
    protected function setError(?string $error = null): bool
    {
        $this->error = $error ?: '未知错误';
        return false;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError(): string
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }
}
