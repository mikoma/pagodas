<?php

namespace Pagodas;


use DirectoryIterator;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Pagodas
{
    private $templatesDir;
    private $fileCacheDir;
    private $metaCache;
    private $templateFiles;
    private $templatesDirUpdateTime;
    private $templates;

    private $inheritance;

    public function __construct(string $templatesDir, string $fileCacheDir, CacheInterface $metaCache)
    {
        $this->templatesDir = $templatesDir;
        $this->fileCacheDir = $fileCacheDir;
        $this->metaCache = $metaCache;
        $this->templatesDirUpdateTime = filemtime($this->templatesDir . "/.");
    }

    public function render(string $template, array $templateData, array $templates = [])
    {
        $this->templates = $templates;
        $templeCacheFile = $this->fileCacheDir . "/{$this->templatesDirUpdateTime}-" . preg_replace("!\.html!", ".php", $template);

        // If template dir has not changed invoke cache
        if($this->metaCache->get('pagodasDirTime') === $this->templatesDirUpdateTime) {
            try {
                if(include $templeCacheFile)
                    return "from cache\n";
                echo "file does not exist<br>";
            } catch (\Exception $e) {
                // file does not exist, let the script continue
            }
        } else {
            $this->clearCache();
            $this->metaCache->set('pagodasDirTime', $this->templatesDirUpdateTime);
        }
        echo "create file<br>";
        // else clear cache and rebuild f
        $progenitor = $this->getInheritance($template);
        $temple = $this->buildTemple($progenitor);
        if(!file_put_contents($templeCacheFile, $temple))
            echo "cannot create file";
        chmod($templeCacheFile, 0777);
        require $templeCacheFile;
        return "rebuilt @ {$this->templatesDirUpdateTime}";
    }

    private function getInheritance(string $templateFile) : string
    {
        $content = file_get_contents($this->templatesDir . "/" . $templateFile);

        if(preg_match('!{{extends (\w+).(\w+)}}!', $content, $matches) === 1) {
            // this is a child template. If not set
            $this->templates[$matches[2]] = $templateFile;
            return $this->getInheritance($matches[1].'.html');
        } else {
            // this is the progenitor
            return $templateFile;
        }
    }

    private function buildTemple(string $templateFile, string $indentation = "")
    {
        echo "building $templateFile<br>";
        $templateContent = file_get_contents($this->templatesDir . "/" . $templateFile);
        return preg_replace_callback_array(
            [
                //aply indentation
                '#^(.*)$#m' => function ($match) use ($indentation) {
                    return $indentation . $match[1];
                },
                // include child template (hereditary, provided or default) or remove parent identifier
                '#^(\s*)\{\{(\w+) ((?:\w+)\.(?:\w+))\}\}\s*$#m' => function ($match) {
                    if($match[2] === 'extends') {
                        return "";
                    }
                    return $this->buildTemple($this->templates[$match[2]] ?? $match[3], $match[1]);
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
        $this->deleteDirContent($this->fileCacheDir);
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