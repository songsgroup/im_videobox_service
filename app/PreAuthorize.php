<?php
declare (strict_types = 1);

namespace app;

use Attribute;
 
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class PreAuthorize
{
    /**
     * @param string $type   权限类别
     * @param string $value 权限标识
     */
    public function __construct(public string $type, public ?string $value='')
    {
    }
}