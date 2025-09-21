<?php

namespace addons\cron\library;

use Closure;
use Cron\CronExpression;
use think\App;
use think\Cache;

abstract class Task
{

    /** @var string|null 时区 */
    public $timezone = null;

    /** @var string 任务周期 */
    public $expression = '* * * * *';

    /** @var bool 任务是否可以重叠执行 */
    public $withoutOverlapping = false;

    /** @var int 最大执行时间(重叠执行检查用) */
    public $expiresAt = 1440;

    /** @var bool 分布式部署 是否仅在一台服务器上运行 */
    public $onOneServer = false;

    protected $filters = [];
    protected $rejects = [];

    /** @var Cache */
    protected $cache;

    /** @var App */
    protected $app;

    /** @var task */
    protected $job;
    public $jobLog;
    public $output;

    public function __construct(App $app, Cache $cache,array $job)
    {
        $this->app   = $app;
        $this->cache = $cache;
        $this->job = $job;
        $this->configure($job);
    }

    /**
     * 是否到期执行
     * @return bool
     */
    public function isDue()
    {
        $cronExpression = new CronExpression($this->expression);

        return $cronExpression->isDue('now', $this->timezone);
    }

    /**
     * 配置任务
     */
    protected function configure(array $job)
    {
    	$this->expression = implode(' ',array_slice(explode(' ',$job['cronExpression']),1));
    	$this->withoutOverlapping = !intval($job['concurrent']);
        $this->jobLog = [
            'jobName'=>$this->job['jobName'],
            'jobGroup'=>$this->job['jobGroup'],
            'invokeTarget'=>$this->job['invokeTarget'],
            'jobMessage'=>'',
            'status'=>0,
            'exception_info'=>'',
        ];
    }

    /**
     * 执行任务
     */
    protected function execute()
    {
    	if (str_contains($this->job['invokeTarget'],'@')) {
    		list($class,$method) = explode('@',$this->job['invokeTarget']);
    		if (str_contains($method,'(')) {
                preg_match('/(\w+)\((.*)\)$/',$method,$match);
                $method = $match[1];
                $vars = eval('return ['.$match[2].'];');
    		}
            $this->app->invoke([$this,$method],$vars);
    	}
    }

    final public function run()
    {
        if ($this->withoutOverlapping &&
            !$this->createMutex()) {
            return;
        }

        register_shutdown_function(function () {
            $this->removeMutex();
        });

        try {
            $this->execute();
        } finally {
            $this->removeMutex();
        }
    }

    /**
     *
     */
    public function getName()
    {
        return $this->job['jobName'];
    }

    /**
     * 过滤
     * @return bool
     */
    public function filtersPass()
    {
        foreach ($this->filters as $callback) {
            if (!call_user_func($callback)) {
                return false;
            }
        }

        foreach ($this->rejects as $callback) {
            if (call_user_func($callback)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 任务标识
     */
    public function mutexName()
    {
        return 'task-' . $this->job['jobId'];
    }

    protected function removeMutex()
    {
        return $this->cache->delete($this->mutexName());
    }

    protected function createMutex()
    {
        $name = $this->mutexName();

        return $this->cache->set($name, time(), $this->expiresAt);
    }

    protected function existsMutex()
    {
        if ($this->cache->has($this->mutexName())) {
            $mutex = $this->cache->get($this->mutexName());
            return $mutex + $this->expiresAt > time();
        }
        return false;
    }

    public function when(Closure $callback)
    {
        $this->filters[] = $callback;

        return $this;
    }

    public function skip(Closure $callback)
    {
        $this->rejects[] = $callback;

        return $this;
    }

    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->skip(function () {
            return $this->existsMutex();
        });
    }

    public function onOneServer()
    {
        $this->onOneServer = true;

        return $this;
    }
}