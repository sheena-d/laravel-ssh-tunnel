<?php namespace STS\Tunneler\Console;

use Illuminate\Console\Command;
use STS\Tunneler\Jobs\CloseTunnel;
use STS\Tunneler\Jobs\CreateTunnel;

class TunnelerCommandClose extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tunneler:close';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes the maintained SSH Tunnel';

    public function handle(){
        try {
            $result = dispatch_now(new CloseTunnel());
        } catch (\ErrorException $e) {
            $this->error($e->getMessage());
        }

        if($result === 1) {
            $this->info('The Tunnel has been closed.');
            return 0;
        }

        if($result === 2) {
            $this->info('No tunnel was available to close.');
            return 0;
        }

        $this->warn("I have no idea how this happened. Let me know if you figure it out.");

        return 1;
    }
}