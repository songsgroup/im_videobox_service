<?php
declare (strict_types = 1);

namespace addons\cron\command;

use think\console\Command;
use think\console\Output;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\exception\Handle;
use addons\cron\library\Scheduler;

class Cron extends Command
{
    /** @var Carbon */
    protected $startedAt;

    protected function configure()
    {
        $this->startedAt = date('Y-m-d\TH:i:s');
        $this->setName('cron')
            ->addOption('jobId',null,Option::VALUE_OPTIONAL,'指定任务Id立即运行')
            ->setDescription('运行计划任务');
    }

    public function handle(Scheduler $scheduler)
    {
        $scheduler->output = $this->output;
        $jobId = intval($this->input->getOption('jobId'));
        if ($jobId) {
            $scheduler->run($jobId);
            return;
        }
        $this->listenForEvents();
        $scheduler->run();
    }

    /**
     * 注册事件
     */
    protected function listenForEvents()
    {
        $this->app->event->listen('TaskProcessed', function ($event) {
            $this->output->writeln("Task {$event->getName()} run at " . date('Y-m-d\TH:i:s'));
        });

        $this->app->event->listen('TaskSkipped', function ($event) {
            $this->output->writeln('<info>Skipping task (has already run on another server):</info> ' . $event->getName());
        });

        $this->app->event->listen('TaskFailed', function ($event) {
            $this->output->writeln("Task {$event->getName()} failed at " . date('Y-m-d\TH:i:s'));

            /** @var Handle $handle */
            $handle = $this->app->make(Handle::class);

            $handle->renderForConsole($this->output, $event->exception);

            $handle->report($event->exception);
        });
    }

}