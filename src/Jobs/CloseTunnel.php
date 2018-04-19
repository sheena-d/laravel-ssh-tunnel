<?php namespace STS\Tunneler\Jobs;

class CloseTunnel
{

    /**
     * The Command for checking if the tunnel is open
     * @var string
     */
    protected $ncCommand;

    /**
     * The command for creating the tunnel
     * @var string
     */
    protected $sshCommand;

    /**
     * Simple place to keep all output.
     * @var array
     */
    protected $output = [];

    public function __construct()
    {

        $this->ncCommand = sprintf('%s -vv -z %s %d  >> %s 2>&1',
            config('tunneler.nc_path'),
            config('tunneler.local_address'),
            config('tunneler.local_port'),
            config('tunneler.nohup_log')
        );

        $this->bashCommand = sprintf('timeout 1 %s -vv -c \'cat < /dev/null > /dev/tcp/%s/%d\' >> %s 2>&1',
            config('tunneler.bash_path'),
            config('tunneler.local_address'),
            config('tunneler.local_port'),
            config('tunneler.nohup_log')
        );

        $this->sshCommand = sprintf('%s %s -S %s -vv -O exit %s@%s',
            config('tunneler.ssh_path'),
            config('tunneler.ssh_verbosity'),
            config('tunneler.ctl_path'),
            config('tunneler.user'),
            config('tunneler.hostname')
        );
    }


    public function handle(): int
    {
        if ($this->verifyTunnel()) {
            return $this->closeTunnel();
        }
        else {
            return 2;
        }
    }

    /**
     * Closes the SSH Tunnel for us.
     */
    protected function closeTunnel()
    {
        $this->runCommand(sprintf('%s %s >> %s 2>&1 &',
            config('tunneler.nohup_path'),
            $this->sshCommand,
            config('tunneler.nohup_log')
        ));

        // Ensure we wait long enough for it to actually close.
        usleep(config('tunneler.wait'));

        if(!$this->verifyTunnel()) {
            return 1;
        }

        throw new \ErrorException("Tunnel could not be closed.");
    }

    /**
     * Verifies whether the tunnel is active or not.
     * @return bool
     */
    protected function verifyTunnel()
    {

        if (config('tunneler.verify_process') == 'bash') {
            return $this->runCommand($this->bashCommand);
        }

        return $this->runCommand($this->ncCommand);
    }

    /**
     * Runs a command and converts the exit code to a boolean
     * @param $command
     * @return bool
     */
    protected function runCommand($command)
    {
        $return_var = 1;
        exec($command, $this->output, $return_var);
        return (bool)($return_var === 0);
    }


}
