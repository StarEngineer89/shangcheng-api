<?php

namespace PHPSTORM_META {

    use think\Container;
    use function \app;

    override(
        \app(),
        map([
            'json' => \core\utils\Json::class
        ])
    );

    override(
        \think\Container::make(),
        map([
            '' => '@'
        ])
    );

}
