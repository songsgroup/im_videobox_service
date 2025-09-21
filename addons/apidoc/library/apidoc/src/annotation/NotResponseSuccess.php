<?php

namespace hg\apidoc\annotation;

use Attribute;
use hg\apidoc\utils\AbstractAnnotation;

/**
 * 不使用成功响应体返回数据
 * @package hg\apidoc\annotation
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class NotResponseSuccess extends AbstractAnnotation
{

    public function __construct(...$value)
    {
        parent::__construct(...$value);
    }
}
