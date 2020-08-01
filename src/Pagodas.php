<?php

namespace Pagodas;


use DirectoryIterator;
use Exception;

class Pagodas
{
    private $templatesDir;
    private $cacheDir;
    private $templateFiles;
    private $templatesDirUpdateTime;
    private $templates;

    private $inheritance;

    public function __construct(string $templatesDir, string $cacheDir)
    {
        $this->templatesDir = $templatesDir;
        $this->cacheDir = $cacheDir;
        $this->templatesDirUpdateTime = filemtime($this->templatesDir . "/.");
    }

    public function render(string $template, array $templateData, array $templates = [])
    {
        $this->templates = $templates;
        $templeCacheFile = $this->cacheDir . "/{$this->templatesDirUpdateTime}-" . preg_replace("!\.html!", ".php", $template);

        // If template dir has not changed invoke cache
        if(apcu_fetch('pagodasDirTime') === $this->templatesDirUpdateTime) {
            try {
                if(include $templeCacheFile)
                    return "from cache\n";
                echo "file does not exist<br>";
            } catch (\Exception $e) {
                // file does not exist, let the script continue
            }
        } else {
            $this->clearCache();
            apcu_store('pagodasDirTime', $this->templatesDirUpdateTime);
            $time = apcu_fetch('pagodasDirTime');
        }
        echo "create file<br>";
        // else clear cache and rebuild f
        $progenitor = $this->getInheritance($this->templatesDir . "/" . $template);
        $temple = $this->buildTemple($progenitor);
        if(!file_put_contents($templeCacheFile, $temple))
            echo "cannot create file";
        chmod($templeCacheFile, 0777);
        require $templeCacheFile;
        return "rebuilt @ $time";
    }

    private function getInheritance(string $templateFile) : string
    {
        $content = file_get_contents($templateFile);

        if(preg_match('!{{extends (\w+).(\w+)}}!', $content, $matches) === 1) {
            // this is a child template. If not set
            $this->templates[$matches[2]] = $templateFile;
            return $this->getInheritance($this->templatesDir . "/" . $matches[1].'.html');
        } else {
            // this is the progenitor
            return $templateFile;
        }
    }

    private function buildTemple(string $templateFile)
    {
        echo "building $templateFile<br>";
        $templateContent = file_get_contents($templateFile);
        return preg_replace_callback_array(
            [
                // include child template (hereditary, provided or default) or remove parent identifier
                '#\{\{(\w+) ((?:\w+)\.(?:\w+))\}\}\s#' => function ($match) {
                    if($match[1] === 'extends') {
                        return "";
                    }
                    return $this->buildTemple($this->templates[$match[1]] ?? $this->templatesDir . "/" . $match[2]);
                },
                // replace variables
                '#\{\{\$(\w+)\}\}#' => function ($match){
                    return "<?php echo \$templateData['{$match[1]}']; ?>";
                }
            ],
            $templateContent
        );
    }

    private function clearCache()
    {
        $this->deleteDirContent($this->cacheDir);
        return true;
    }

    public function deleteDirContent($path){
        try{
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $fileInfo ) {
                if($fileInfo->isDot())
                    continue;
                if($fileInfo->isDir()){
                    if($this->deleteDirContent($fileInfo->getPathname()))
                        @rmdir($fileInfo->getPathname());
                }
                if($fileInfo->isFile()){
                    @unlink($fileInfo->getPathname());
                }
            }
        } catch ( Exception $e ){
            // write log
            return false;
        }
        return true;
    }

}