<?php
namespace core\traits;


use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Env;
use UnexpectedValueException;

trait JwtAuthModelTrait
{
    /**
     * @param string $type
     * @param array $params
     * @return array
     */
    public function getToken(string $type, array $params = []): array
    {
        $id = $this->{$this->getPk()};
        $host = app()->request->host();
        $time = time();

        $params += [
            'iss' => $host,
            'aud' => $host,
            'iat' => $time,
            'nbf' => $time,
            'exp' => strtotime('+ 3hour'),
        ];
        $params['jti'] = compact('id', 'type');
        $token = JWT::encode($params, env('APP_APP_KEY', 'default'), 'HS256');

        return compact('token', 'params');
    }

    /**
     * @param string $jwt
     * @return array
     *
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function parseToken(string $jwt): array
    {
        JWT::$leeway = 60;

        $headers = array('HS256');
        $data = JWT::decode($jwt, Env::get('app.app_key', 'default'), $headers);

        $model = new self();
        return [$model->where($model->getPk(), $data->jti->id)->find(), $data->jti->type];
    }
}
