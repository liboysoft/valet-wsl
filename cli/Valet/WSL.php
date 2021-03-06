<?php

namespace Valet;

class WSL
{
    public $wsl_cert_store = '/mnt/c/tools/valet/certs/';
    public $cli;
    public $files;
    public $sites;

    /**
     * Create a new WSL instance.
     *
     * @param  CommandLine  $cli
     * @param  Filesystem  $files
     */
    public function __construct(CommandLine $cli, Filesystem $files, Site $sites)
    {
        $this->cli = $cli;
        $this->files = $files;
        $this->sites = $sites;
    }

    /**
     * Copy any scripts needed by the windows host
     *
     * @return void
     */
    public function copyScripts()
    {
        $this->files->ensureDirExists($this->wsl_cert_store);

        $contents = $this->files->get(__DIR__.'/../stubs/install_certs.cmd');

        $this->files->put(
            $this->wsl_cert_store . 'install_certs.cmd',
            $contents
        );
    }

    /**
     * Publish the .crt file to the windows host
     *
     * @param string $url
     * @return void
     */
    public function publish($url)
    {
        $this->files->ensureDirExists($this->wsl_cert_store);

        $cert =  $url . '.crt';
        $crtPath = $this->sites->certificatesPath() . '/' . $cert;

        $contents = $this->files->get($crtPath);

        $this->files->put(
            $this->wsl_cert_store . $cert,
            $contents
        );
    }

    /**
     * remove old certs from win host and get publish new certs
     *
     * @return void
     */
    public function cleanAndRepublish()
    {
        $this->files->ensureDirExists($this->wsl_cert_store);

        $win_certs = collect($this->files->scanDir($this->wsl_cert_store))->filter(function ($value, $key) {
            return ends_with($value, '.crt');
        });

        $win_certs->each(function ($cert) {
            $this->files->unlink($this->wsl_cert_store . $cert);
        });

        $new_certs = collect($this->files->scanDir($this->sites->certificatesPath()))->filter(function ($value, $key) {
            return ends_with($value, '.crt');
        });

        $new_certs->each(function ($cert) {
            $contents = $this->files->get($this->sites->certificatesPath() . '/' . $cert);

            $this->files->put(
                $this->wsl_cert_store . $cert,
                $contents
            );
        });
    }
}
