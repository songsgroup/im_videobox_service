<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 */
class AllowCrossDomain
{
    protected $cookieDomain;

    protected $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => 1800,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, Istoken, repeatsubmit',
    ];

    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array   $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin && ('' == $this->cookieDomain || str_contains($origin, $this->cookieDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }

        if ($request->isOptions()) {
            abort(Response::create()->header($header));
        }
        return $next($request)->header($header);
    }
}
