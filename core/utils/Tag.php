<?php
namespace core\utils;


use think\cache\TagSet;
use think\Container;

/**
 * Class Tag
 * @package core\utils
 * @mixin TagSet
 */
class Tag
{

    protected TagSet $tag;

    /**
     * @var string
     */
    protected string $tagStr;

    /**
     * Tag constructor.
     * @param TagSet $tag
     * @param string $tagStr
     */
    public function __construct(TagSet $tag, string $tagStr)
    {
        $this->tag = $tag;
        $this->tagStr = $tagStr;
    }

    /**
     * @param string $name
     * @param $value
     * @param null $expire
     * @return mixed
     */
    public function remember(string $name, $value, $expire = null)
    {
        //不开启数据缓存直接返回
        if (!app()->config->get('cache.is_data')) {

            if ($value instanceof \Closure) {
                $value = Container::getInstance()->invokeFunction($value);
            }
            return $value;
        }

        $name = $this->tagStr .':'. $name;
        return $this->tag->remember($name, $value, $expire);
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        return $this->tag->{$name}(...$arguments);
    }
}
