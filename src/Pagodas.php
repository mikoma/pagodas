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
    private $templatesDirUpdateTime;
    private $templates;

    /**
     * Pagodas constructor.
     * @param string $templatesDir directory where the templates are stored
     * @param string $fileCacheDir directory where the merged templates will be cached
     * @param CacheInterface $metaCache this will only store one single value: the time of template folder update
     */
    public function __construct(string $templatesDir, string $fileCacheDir, CacheInterface $metaCache)
    {
        $this->templatesDir = $templatesDir;
        $this->fileCacheDir = $fileCacheDir;
        $this->metaCache = $metaCache;
        $this->templatesDirUpdateTime = filemtime($this->templatesDir . "/.");
    }

    /**
     * The template file in templatesDir that is going to be rendered. Inheritance {{extends parent.section}} is auto-included.
     * Default values {{section default.html}} are also auto-included if not overwritten by the $templates parameters
     * @param string $template
     * template variables that will be applied
     * @param array $templateData
     * specify templates that overwrite defaults like ['section' => 'section.html']
     * @param array $templates
     * filename of cached template file
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function render(string $template, array $templateData, array $templates = [])
    {
        $this->templates = $templates;
        $templeCacheFile = $this->fileCacheDir . "/{$this->templatesDirUpdateTime}-" . preg_replace("!\.html!", ".php", $template);

        // If template dir has not changed invoke cache
        if($this->metaCache->get('pagodasDirTime') === $this->templatesDirUpdateTime) {
            try {
                if(include $templeCacheFile)
                    return $templeCacheFile;
//                echo "file does not exist<br>";
            } catch (Exception $e) {
                // file does not exist, let the script continue
            }
        } else {
            $this->clearCache();
            $this->metaCache->set('pagodasDirTime', $this->templatesDirUpdateTime);
        }
//        echo "create file<br>";
        // else clear cache and rebuild f
        $progenitor = $this->getInheritance($template);
        $temple = $this->buildTemple($progenitor);
        if(!file_put_contents($templeCacheFile, $temple))
            echo "cannot create file";
        chmod($templeCacheFile, 0777);
        require $templeCacheFile;
        return "$templeCacheFile";
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
                //apply indentation
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
                //remove empty lines
                '#\n\s*(\r?\n)#' => function ($match) {
                    return $match[1];
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

    private function deleteDirContent($path){
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
        } catch (Exception $e) {
            // write log
            return false;
        }
        return true;
    }
}