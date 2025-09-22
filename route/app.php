<?php

use think\facade\Route;

// Define routes explicitly based on controller annotations
// This ensures all routes are available regardless of annotation scanning issues

// Root route
Route::get('/', [\app\controller\Index::class, 'index']);

// Imext group routes
Route::group('imext', function () {
    // Imext Video routes
    Route::group('video', function () {
        Route::post('list', [\app\controller\imext\video\Video::class, 'list']);
        Route::post(':videoId', [\app\controller\imext\video\Video::class, 'getInfo'])->pattern(['videoId' => '\\d+']);
        Route::post('add', [\app\controller\imext\video\Video::class, 'add']);
        Route::post('update', [\app\controller\imext\video\Video::class, 'edit']);
        Route::delete(':videoId', [\app\controller\imext\video\Video::class, 'remove']);
        Route::post('uploadmedia', [\app\controller\imext\video\Video::class, 'uploadmedia']);
        //
        Route::post('play', [\app\controller\imext\video\Video::class, 'play']);
    });

    Route::group('redpacket', function () {
        // Explicit routes for Send controller
        Route::post('list', [\app\controller\imext\redpacket\Redpacket::class, 'list']);
        Route::post('send', [\app\controller\imext\redpacket\Redpacket::class, 'send']);
        Route::post('receive', [\app\controller\imext\redpacket\Redpacket::class, 'receive']);
        Route::post('refuse', [\app\controller\imext\redpacket\Redpacket::class, 'refuse']);

        // Route::group('send', function () {
        //     Route::get('list', [\app\controller\imext\redpacket\Send::class, 'list']);
        //     Route::get(':id', [\app\controller\imext\redpacket\Send::class, 'getInfo'])->pattern(['id'=>'\\d+']);
        //     Route::post('add', [\app\controller\imext\redpacket\Send::class, 'add']);
        //     Route::post('update', [\app\controller\imext\redpacket\Send::class, 'edit']);
        //     Route::delete(':id', [\app\controller\imext\redpacket\Send::class, 'remove']);
        // });
    });

    // Imext Users routes
    Route::group('users', function () {
        Route::post('add', [\app\controller\imext\users\Users::class, 'add']);
        Route::post('list', [\app\controller\imext\users\Users::class, 'list']);
        Route::post(':userId', [\app\controller\imext\users\Users::class, 'getInfo']); //->pattern(['userId'=>'\\d+']);
        Route::post('update', [\app\controller\imext\users\Users::class, 'edit']);
        Route::delete(':id', [\app\controller\imext\users\Users::class, 'remove']);
       
    });
    Route::group('sms', function () {       
        Route::post('secret_key', [\app\controller\imext\sms\Sms::class, 'secret_key']);
    });
    // Imext Money routes
    Route::group('money', function () {
        Route::group('payincallback', function () {
            Route::post('apply/:orderNo', [\app\controller\imext\money\PayInCallBack::class, 'apply']);
        });

        Route::group('buyvideo', function () {
            Route::post('buy', [\app\controller\imext\money\BuyVideo::class, 'buy']);
        });

        // Recharge routes
        Route::group('recharge', function () {
            Route::post('list', [\app\controller\imext\money\Recharge::class, 'list']);
            Route::post('statistics', [\app\controller\imext\money\Recharge::class, 'statistics']);
            Route::post(':id', [\app\controller\imext\money\Recharge::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\Recharge::class, 'add']);
            Route::post('update', [\app\controller\imext\money\Recharge::class, 'edit']);
            Route::post('audit', [\app\controller\imext\money\Recharge::class, 'audit']);
            Route::post('processCommission', [\app\controller\imext\money\Recharge::class, 'processCommission']);
            Route::post('export', [\app\controller\imext\money\Recharge::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\Recharge::class, 'remove']);
        });

        // Withdraw routes
        Route::group('withdraw', function () {
            Route::post('list', [\app\controller\imext\money\Withdraw::class, 'list']);
            Route::get('statistics', [\app\controller\imext\money\Withdraw::class, 'statistics']);
            Route::get('statusCounts', [\app\controller\imext\money\Withdraw::class, 'statusCounts']);
            Route::get('pendingCount', [\app\controller\imext\money\Withdraw::class, 'pendingCount']);
            Route::post(':id', [\app\controller\imext\money\Withdraw::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\Withdraw::class, 'add']);
            Route::post('update', [\app\controller\imext\money\Withdraw::class, 'edit']);
            Route::post('audit', [\app\controller\imext\money\Withdraw::class, 'audit']);
            Route::post('batchAudit', [\app\controller\imext\money\Withdraw::class, 'batchAudit']);
            Route::post('reject', [\app\controller\imext\money\Withdraw::class, 'reject']);
            Route::post('calculateFee', [\app\controller\imext\money\Withdraw::class, 'calculateFee']);
            Route::post('export', [\app\controller\imext\money\Withdraw::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\Withdraw::class, 'remove']);
        });

        // Payment Config routes
        Route::group('paymentconfig', function () {
            Route::post('list', [\app\controller\imext\money\PaymentConfig::class, 'list']);
            Route::get('enabled', [\app\controller\imext\money\PaymentConfig::class, 'enabled']);
            Route::get('statistics', [\app\controller\imext\money\PaymentConfig::class, 'statistics']);
            Route::get('code/:code', [\app\controller\imext\money\PaymentConfig::class, 'getByCode']);
            Route::post(':id', [\app\controller\imext\money\PaymentConfig::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\PaymentConfig::class, 'add']);
            Route::post('update', [\app\controller\imext\money\PaymentConfig::class, 'edit']);
            Route::post('copy', [\app\controller\imext\money\PaymentConfig::class, 'copy']);
            Route::post('checkCode', [\app\controller\imext\money\PaymentConfig::class, 'checkCode']);
            Route::post('updateOrdering', [\app\controller\imext\money\PaymentConfig::class, 'updateOrdering']);
            Route::post('batchUpdateStatus', [\app\controller\imext\money\PaymentConfig::class, 'batchUpdateStatus']);
            Route::post('toggleStatus', [\app\controller\imext\money\PaymentConfig::class, 'toggleStatus']);
            Route::post('batchDelete', [\app\controller\imext\money\PaymentConfig::class, 'batchDelete']);
            Route::post('export', [\app\controller\imext\money\PaymentConfig::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\PaymentConfig::class, 'remove']);
        });

        // Recorder routes
        Route::group('recorder', function () {
            Route::post('list', [\app\controller\imext\money\Recorder::class, 'list']);
            Route::get('statistics', [\app\controller\imext\money\Recorder::class, 'statistics']);
            Route::get('user/:userId', [\app\controller\imext\money\Recorder::class, 'getByUserId']);
            Route::get('type/:type', [\app\controller\imext\money\Recorder::class, 'getByType']);
            Route::get('data/:dataId', [\app\controller\imext\money\Recorder::class, 'getByDataId']);
            Route::get('userFlow/:userId', [\app\controller\imext\money\Recorder::class, 'getUserFlow']);
            Route::get(':id', [\app\controller\imext\money\Recorder::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\Recorder::class, 'add']);
            Route::post('batchAdd', [\app\controller\imext\money\Recorder::class, 'batchAdd']);
            Route::post('update', [\app\controller\imext\money\Recorder::class, 'edit']);
            Route::post('createRechargeRecord', [\app\controller\imext\money\Recorder::class, 'createRechargeRecord']);
            Route::post('createWithdrawRecord', [\app\controller\imext\money\Recorder::class, 'createWithdrawRecord']);
            Route::post('createConsumeRecord', [\app\controller\imext\money\Recorder::class, 'createConsumeRecord']);
            Route::post('batchDelete', [\app\controller\imext\money\Recorder::class, 'batchDelete']);
            Route::post('export', [\app\controller\imext\money\Recorder::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\Recorder::class, 'remove']);
        });

        // User Bank routes
        Route::group('userbank', function () {
            Route::post('list', [\app\controller\imext\money\UserBank::class, 'list']);
            Route::get('statistics', [\app\controller\imext\money\UserBank::class, 'statistics']);
            Route::get('pendingCount', [\app\controller\imext\money\UserBank::class, 'pendingCount']);
            Route::POST('user/:userId', [\app\controller\imext\money\UserBank::class, 'getByUserId']);
            Route::POST('order/:orderId', [\app\controller\imext\money\UserBank::class, 'getByOrderId']);
            Route::POST('card/:cardId', [\app\controller\imext\money\UserBank::class, 'getByCardId']);
            Route::post(':id', [\app\controller\imext\money\UserBank::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\UserBank::class, 'add']);
            Route::post('update', [\app\controller\imext\money\UserBank::class, 'edit']);
            Route::post('audit', [\app\controller\imext\money\UserBank::class, 'audit']);
            Route::post('batchAudit', [\app\controller\imext\money\UserBank::class, 'batchAudit']);
            Route::post('reject', [\app\controller\imext\money\UserBank::class, 'reject']);
            Route::post('batchUpdateStatus', [\app\controller\imext\money\UserBank::class, 'batchUpdateStatus']);
            Route::post('calculateFee', [\app\controller\imext\money\UserBank::class, 'calculateFee']);
            Route::post('generateOrderId', [\app\controller\imext\money\UserBank::class, 'generateOrderId']);
            Route::post('export', [\app\controller\imext\money\UserBank::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\UserBank::class, 'remove']);
        });

        //Bank routes
        Route::group('bank', function () {
            Route::post('list', [\app\controller\imext\money\Bank::class, 'list']);
            Route::post('add', [\app\controller\imext\money\Bank::class, 'add']);
        });

        // Digital Currency routes
        Route::group('digitalcurrency', function () {
            Route::post('list', [\app\controller\imext\money\DigitalCurrency::class, 'list']);
            Route::get('statistics', [\app\controller\imext\money\DigitalCurrency::class, 'statistics']);
            Route::get('pendingCount', [\app\controller\imext\money\DigitalCurrency::class, 'pendingCount']);
            Route::get('types', [\app\controller\imext\money\DigitalCurrency::class, 'getAllTypes']);
            Route::get('user/:userId', [\app\controller\imext\money\DigitalCurrency::class, 'getByUserId']);
            Route::get('type/:type', [\app\controller\imext\money\DigitalCurrency::class, 'getByType']);
            Route::get('address/:address', [\app\controller\imext\money\DigitalCurrency::class, 'getByAddress']);
            Route::get('userValidAddresses/:userId', [\app\controller\imext\money\DigitalCurrency::class, 'getUserValidAddresses']);
            Route::post(':id', [\app\controller\imext\money\DigitalCurrency::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\money\DigitalCurrency::class, 'add']);
            Route::post('batchAdd', [\app\controller\imext\money\DigitalCurrency::class, 'batchAdd']);
            Route::post('update', [\app\controller\imext\money\DigitalCurrency::class, 'edit']);
            Route::post('audit', [\app\controller\imext\money\DigitalCurrency::class, 'audit']);
            Route::post('batchAudit', [\app\controller\imext\money\DigitalCurrency::class, 'batchAudit']);
            Route::post('batchUpdateStatus', [\app\controller\imext\money\DigitalCurrency::class, 'batchUpdateStatus']);
            Route::post('checkAddress', [\app\controller\imext\money\DigitalCurrency::class, 'checkAddress']);
            Route::post('copy', [\app\controller\imext\money\DigitalCurrency::class, 'copy']);
            Route::post('export', [\app\controller\imext\money\DigitalCurrency::class, 'export']);
            Route::delete(':id', [\app\controller\imext\money\DigitalCurrency::class, 'remove']);
        });
    });

    // Imext ImMsg routes
    Route::group('immsg', function () {
        // Group Config routes
        Route::group('groupconfig', function () {
            Route::post('list', [\app\controller\imext\immsg\ImGroupConfig::class, 'list']);
            Route::get('statistics', [\app\controller\imext\immsg\ImGroupConfig::class, 'statistics']);
            Route::get('group/:groupId', [\app\controller\imext\immsg\ImGroupConfig::class, 'getByGroupId'])->pattern(['groupId' => '\\d+']);
            Route::get('user/:userId', [\app\controller\imext\immsg\ImGroupConfig::class, 'getByUserId'])->pattern(['userId' => '\\d+']);
            Route::get(':id', [\app\controller\imext\immsg\ImGroupConfig::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('add', [\app\controller\imext\immsg\ImGroupConfig::class, 'add']);
            Route::post('update', [\app\controller\imext\immsg\ImGroupConfig::class, 'edit']);
            Route::post('copy', [\app\controller\imext\immsg\ImGroupConfig::class, 'copy']);
            Route::post('checkGroupId', [\app\controller\imext\immsg\ImGroupConfig::class, 'checkGroupId']);
            Route::post('batchDelete', [\app\controller\imext\immsg\ImGroupConfig::class, 'batchDelete']);
            Route::post('export', [\app\controller\imext\immsg\ImGroupConfig::class, 'export']);
            Route::delete(':id', [\app\controller\imext\immsg\ImGroupConfig::class, 'remove']);
        });
        Route::group('autoimmsg', function () {
            Route::post('send', [\app\controller\imext\immsg\AutoImMsg::class, 'send']);
            Route::get('automsg', [\app\controller\imext\immsg\AutoImMsg::class, 'autoMsg']);
        });
    });
});


// Admin group routes

// Admin Index routes
Route::get('/', [\app\controller\admin\Index::class, 'index']);
Route::get('captchaImage', [\app\controller\admin\Index::class, 'getCode']);
Route::post('login', [\app\controller\admin\Index::class, 'login']);
Route::any('logout', [\app\controller\admin\Index::class, 'logout']);
Route::get('getInfo', [\app\controller\admin\Index::class, 'getInfo']);
Route::get('getRouters', [\app\controller\admin\Index::class, 'getRouters']);
Route::post('register', [\app\controller\admin\Index::class, 'register']);

// Admin Common routes
Route::group('common', function () {
    Route::get('download', [\app\controller\admin\Common::class, 'fileDownload']);
    Route::post('upload', [\app\controller\admin\Common::class, 'uploadFile']);
    Route::post('uploads', [\app\controller\admin\Common::class, 'uploadFiles']);
    Route::get('download/resource', [\app\controller\admin\Common::class, 'resourceDownload']);
});

// Admin Druid routes
Route::group('druid', function () {
    Route::any('login', [\app\controller\admin\druid\Index::class, 'login']);
    Route::any('logout', [\app\controller\admin\druid\Index::class, 'logout']);
});

// Admin Tool Addons routes
Route::group('tool/addons', function () {
    Route::post('', [\app\controller\admin\tool\addons\Index::class, 'add']);
    Route::delete('<ids>', [\app\controller\admin\tool\addons\Index::class, 'remove']);
    Route::put('', [\app\controller\admin\tool\addons\Index::class, 'edit']);
    Route::put('changeStatus', [\app\controller\admin\tool\addons\Index::class, 'changeStatus']);
    Route::get('config/:name', [\app\controller\admin\tool\addons\Index::class, 'config']);
    Route::put('config/:name', [\app\controller\admin\tool\addons\Index::class, 'config']);
    Route::get('export', [\app\controller\admin\tool\addons\Index::class, 'export']);
    Route::get('list', [\app\controller\admin\tool\addons\Index::class, 'list']);
    Route::get(':name', [\app\controller\admin\tool\addons\Index::class, 'getInfo']);
    Route::post('import', [\app\controller\admin\tool\addons\Index::class, 'import']);
});

// Admin System routes
Route::group('system', function () {
    // User routes
    Route::group('user', function () {
        Route::get('profile', [\app\controller\admin\system\User::class, 'profile']);
        Route::get('deptTree', [\app\controller\admin\system\User::class, 'deptTree']);
        Route::get('list', [\app\controller\admin\system\User::class, 'list']);
        Route::post('export', [\app\controller\admin\system\User::class, 'export']);
        Route::get('<id>', [\app\controller\admin\system\User::class, 'getInfo'])->pattern(['id' => '\\d+']);
        Route::get('', [\app\controller\admin\system\User::class, 'getInfo']);
        Route::post('', [\app\controller\admin\system\User::class, 'add']);
        Route::put('', [\app\controller\admin\system\User::class, 'edit']);
        Route::delete(':userId', [\app\controller\admin\system\User::class, 'remove']);
        Route::put('resetPwd', [\app\controller\admin\system\User::class, 'resetPwd']);
        Route::put('changeStatus', [\app\controller\admin\system\User::class, 'changeStatus']);
        Route::post('importData', [\app\controller\admin\system\User::class, 'importData']);
        Route::post('importTemplate', [\app\controller\admin\system\User::class, 'importTemplate']);

        // User Profile routes
        Route::group('profile', function () {
            Route::get('', [\app\controller\admin\system\user\Profile::class, 'profile']);
            Route::put('', [\app\controller\admin\system\user\Profile::class, 'updateProfile']);
            Route::put('updatePwd', [\app\controller\admin\system\user\Profile::class, 'updatePwd']);
            Route::post('avatar', [\app\controller\admin\system\user\Profile::class, 'avatar']);
        });

        // User AuthRole routes
        Route::group('authRole', function () {
            Route::get('<id>', [\app\controller\admin\system\user\AuthRole::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::put('', [\app\controller\admin\system\user\AuthRole::class, 'edit']);
        });
    });

    // Role routes
    Route::group('role', function () {
        Route::get('list', [\app\controller\admin\system\Role::class, 'list']);
        Route::post('export', [\app\controller\admin\system\Role::class, 'export']);
        Route::get('<id>', [\app\controller\admin\system\Role::class, 'getInfo'])->pattern(['id' => '\\d+']);
        Route::post('', [\app\controller\admin\system\Role::class, 'add']);
        Route::put('', [\app\controller\admin\system\Role::class, 'edit']);
        Route::delete(':roleIds', [\app\controller\admin\system\Role::class, 'remove']);
        Route::put('changeStatus', [\app\controller\admin\system\Role::class, 'changeStatus']);
        Route::get('optionselect', [\app\controller\admin\system\Role::class, 'optionselect']);

        // Role AuthUser routes
        Route::group('authUser', function () {
            Route::get('allocatedList', [\app\controller\admin\system\role\AuthUser::class, 'allocatedList']);
            Route::get('unallocatedList', [\app\controller\admin\system\role\AuthUser::class, 'unallocatedList']);
            Route::put('cancel', [\app\controller\admin\system\role\AuthUser::class, 'cancel']);
            Route::put('cancelAll', [\app\controller\admin\system\role\AuthUser::class, 'cancelAll']);
            Route::put('selectAll', [\app\controller\admin\system\role\AuthUser::class, 'selectAll']);
        });
    });

    // Dept routes
    Route::group('dept', function () {
        Route::get('list', [\app\controller\admin\system\Dept::class, 'list']);
        Route::get('list/exclude/:deptId', [\app\controller\admin\system\Dept::class, 'excludeChild'])->pattern(['deptId' => '\\d+']);
        Route::get(':deptId', [\app\controller\admin\system\Dept::class, 'getInfo'])->pattern(['deptId' => '\\d+']);
        Route::post('', [\app\controller\admin\system\Dept::class, 'add']);
        Route::put('', [\app\controller\admin\system\Dept::class, 'edit']);
        Route::delete(':deptId', [\app\controller\admin\system\Dept::class, 'remove']);
    });

    // Menu routes
    Route::group('menu', function () {
        Route::get('list', [\app\controller\admin\system\Menu::class, 'list']);
        Route::get('<id>', [\app\controller\admin\system\Menu::class, 'getInfo'])->pattern(['id' => '\\d+']);
        Route::get('treeselect', [\app\controller\admin\system\Menu::class, 'treeselect']);
        Route::get('roleMenuTreeselect/:roleId', [\app\controller\admin\system\Menu::class, 'roleMenuTreeselect']);
        Route::post('', [\app\controller\admin\system\Menu::class, 'add']);
        Route::put('', [\app\controller\admin\system\Menu::class, 'edit']);
        Route::delete('<ids>', [\app\controller\admin\system\Menu::class, 'remove']);
    });

    // Config routes
    Route::group('config', function () {
        Route::get('list', [\app\controller\admin\system\Config::class, 'list']);
        Route::post('export', [\app\controller\admin\system\Config::class, 'export']);
        Route::get(':configId', [\app\controller\admin\system\Config::class, 'getInfo'])->pattern(['configId' => '\\d+']);
        Route::get('configKey/:configKey', [\app\controller\admin\system\Config::class, 'getConfigKey']);
        Route::post('', [\app\controller\admin\system\Config::class, 'add']);
        Route::put('', [\app\controller\admin\system\Config::class, 'edit']);
        Route::delete(':configIds', [\app\controller\admin\system\Config::class, 'remove']);
    });

    // Notice routes
    Route::group('notice', function () {
        Route::get('list', [\app\controller\admin\system\Notice::class, 'list']);
        Route::get(':noticeId', [\app\controller\admin\system\Notice::class, 'getInfo'])->pattern(['noticeId' => '\\d+']);
        Route::post('', [\app\controller\admin\system\Notice::class, 'add']);
        Route::put('', [\app\controller\admin\system\Notice::class, 'edit']);
        Route::delete(':noticeId', [\app\controller\admin\system\Notice::class, 'remove']);
    });

    // Post routes
    Route::group('post', function () {
        Route::get('list', [\app\controller\admin\system\Post::class, 'list']);
        Route::get(':postId', [\app\controller\admin\system\Post::class, 'getInfo'])->pattern(['postId' => '\\d+']);
        Route::post('', [\app\controller\admin\system\Post::class, 'add']);
        Route::put('', [\app\controller\admin\system\Post::class, 'edit']);
        Route::delete(':postId', [\app\controller\admin\system\Post::class, 'remove']);
    });

    // Dict routes
    Route::group('dict', function () {
        // Dict Data routes
        Route::group('data', function () {
            Route::get('list', [\app\controller\admin\system\dict\Data::class, 'list']);
            Route::post('export', [\app\controller\admin\system\dict\Data::class, 'export']);
            Route::get('<id>', [\app\controller\admin\system\dict\Data::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::get('type/:dictType', [\app\controller\admin\system\dict\Data::class, 'dictType']);
            Route::post('', [\app\controller\admin\system\dict\Data::class, 'add']);
            Route::put('', [\app\controller\admin\system\dict\Data::class, 'edit']);
            Route::delete(':dictCodes', [\app\controller\admin\system\dict\Data::class, 'remove']);
        });

        // Dict Type routes
        Route::group('type', function () {
            Route::get('list', [\app\controller\admin\system\dict\Type::class, 'list']);
            Route::post('export', [\app\controller\admin\system\dict\Type::class, 'export']);
            Route::get('<id>', [\app\controller\admin\system\dict\Type::class, 'getInfo'])->pattern(['id' => '\\d+']);
            Route::post('', [\app\controller\admin\system\dict\Type::class, 'add']);
            Route::put('', [\app\controller\admin\system\dict\Type::class, 'edit']);
            Route::delete(':dictIds', [\app\controller\admin\system\dict\Type::class, 'remove']);
            Route::delete('refreshCache', [\app\controller\admin\system\dict\Type::class, 'refreshCache']);
        });
    });
});

// Admin Monitor routes
Route::group('monitor', function () {
    // Logininfor routes
    Route::group('logininfor', function () {
        Route::get('list', [\app\controller\admin\monitor\Logininfor::class, 'list']);
        Route::post('export', [\app\controller\admin\monitor\Logininfor::class, 'export']);
        Route::delete(':infoIds', [\app\controller\admin\monitor\Logininfor::class, 'remove']);
        Route::delete('clean', [\app\controller\admin\monitor\Logininfor::class, 'clean']);
        Route::get('unlock/:userName', [\app\controller\admin\monitor\Logininfor::class, 'unlock']);
    });

    // Operlog routes
    Route::group('operlog', function () {
        Route::get('list', [\app\controller\admin\monitor\Operlog::class, 'list']);
        Route::get('export', [\app\controller\admin\monitor\Operlog::class, 'export']);
        Route::delete('clean', [\app\controller\admin\monitor\Operlog::class, 'clean']);
        Route::delete(':operIds', [\app\controller\admin\monitor\Operlog::class, 'remove']);
    });

    // Online routes
    Route::group('online', function () {
        Route::get('list', [\app\controller\admin\monitor\Online::class, 'list']);
        Route::delete(':tokenId', [\app\controller\admin\monitor\Online::class, 'forceLogout']);
    });

    // Cache routes
    Route::group('cache', function () {
        Route::get('', [\app\controller\admin\monitor\Cache::class, 'getInfo']);
        Route::get('getNames', [\app\controller\admin\monitor\Cache::class, 'getNames']);
        Route::get('getKeys/:cacheName', [\app\controller\admin\monitor\Cache::class, 'getKeys']);
        Route::get('getValue/:cacheName/:cacheKey', [\app\controller\admin\monitor\Cache::class, 'getValue']);
        Route::delete('clearCacheName/:cacheName', [\app\controller\admin\monitor\Cache::class, 'clearCacheName']);
        Route::delete('clearCacheName/:cacheKey', [\app\controller\admin\monitor\Cache::class, 'clearCacheKey']);
        Route::delete('clearCacheAll', [\app\controller\admin\monitor\Cache::class, 'clearCacheAll']);
    });

    // Server routes
    Route::group('server', function () {
        Route::get('', [\app\controller\admin\monitor\Server::class, 'getInfo']);
    });
});
