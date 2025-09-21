<?php

namespace addons\cron\library;

use Exception;
use think\App;
use think\cache\Driver;
use addons\cron\model\JobModel;
use addons\cron\model\JobLogModel;
use think\console\Output;

class Scheduler
{
    /** @var App */
    protected $app;

    /** @var Carbon */
    protected $startedAt;

    protected $tasks = [];

    /** @var Driver */
    protected $cache;

    public $output;

    public function __construct(App $app)
    {
        $this->app   = $app;
        $this->tasks = JobModel::where('status',0)->select()->toArray();
        $this->cache = $app->cache->store($app->config->get('cache.default', null));
    }

    public function run(int $jobId=0)
    {
        $this->startedAt = time();
        if ($jobId) {
            $job = JobModel::find($jobId)->toArray();
            if (!$job) {
                $this->output->write('任务不存在');
                return;
            }

            /** @var Task $task */
            if (str_contains($job['invokeTarget'],'@')) {
                list($class) = explode('@',$job['invokeTarget']);
            }else{
                $class = $job['invokeTarget'];
            }
            $task = $this->app->invokeClass($class, ['cache'=>$this->cache,'job'=>$job]);
            $task->output = method_exists($this->output,'fetch')?$this->output:new Output('buffer');
            $this->runTask($task);

            $this->app->event->trigger('TaskProcessed',$task);
            $task->jobLog['jobMessage'] = $task->output->fetch();
            $r = JobLogModel::create($task->jobLog);
            if (!method_exists($this->output,'fetch')) {
                $this->output->write($task->jobLog['jobMessage']);
            }
            return;
        }
        foreach ($this->tasks as $job) {

            /** @var Task $task */
            if (str_contains($job['invokeTarget'],'@')) {
                list($class) = explode('@',$job['invokeTarget']);
            }else{
                $class = $job['invokeTarget'];
            }
            $task = $this->app->invokeClass($class, ['cache'=>$this->cache,'job'=>$job]);
            $task->output = new Output('buffer');
            if ($task->isDue()) {

                if (!$task->filtersPass()) {
                    continue;
                }

                if ($task->onOneServer) {
                    $this->runSingleServerTask($task);
                } else {
                    $this->runTask($task);
                }

                $this->app->event->trigger('TaskProcessed',$task);
                $task->jobLog['jobMessage'] = $task->output->fetch();
                JobLogModel::create($task->jobLog);
                $this->output->write($task->jobLog['jobMessage']);
            }
        }
    }

    /**
     * @param $task Task
     * @return bool
     */
    protected function serverShouldRun($task)
    {
        $key = $task->mutexName() . $this->startedAt->format('Hi');
        if ($this->cache->has($key)) {
            return false;
        }
        $this->cache->set($key, true, 60);
        return true;
    }

    protected function runSingleServerTask($task)
    {
        if ($this->serverShouldRun($task)) {
            $this->runTask($task);
        } else {
            $this->app->event->trigger('TaskSkipped',$task);
        }
    }

    /**
     * @param $task Task
     */
    protected function runTask($task)
    {
        try {
            $task->run();
        } catch (Exception $e) {
            $task->exception = $e;
            $this->app->event->trigger('TaskFailed',$task);
            $task->jobLog['jobMessage'] = $task->output->fetch();
            $task->jobLog['status'] = 1;
            $task->jobLog['exception_info'] = $e;
            JobLogModel::create($task->jobLog);
            $this->output->write($task->jobLog['jobMessage']);
        }
    }
}