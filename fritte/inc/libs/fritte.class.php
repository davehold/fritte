<?php
    /**
    * Fritte Class
    * 
    * A class to generate an static HTML-Website from textfiles
    */

    require_once 'parsedown/parsedown.php';
    require_once 'parsedown/parsedownExtra.php';
    require_once 'parsedown/parsedownExtraPlugin.php';

    class Fritte
    {
        // Array that contains every directory to scan
        public $config = array();
        public $dirs = array();
        public $fileExtensions = array("md", "txt");
        public $outputFileExtension = "html";

        public function __construct()
        {
            $this->loadConfig();
            $this->setContentDirs();
        }

        // loads config file
        function loadConfig($pathToConfig = "/../../../config.ini")
        {
            $basedir = dirname(realpath(__FILE__));
            $this->config = parse_ini_file($basedir.$pathToConfig, TRUE);
        }

        // returns config file as array
        function getConfig()
        {
            return $this->config;
        }

        // sets content directories
        // if not set it uses the config file
        function setContentDirs($dirArr = "")
        {
            if($dirArr == "")
            {
                $this->dirs = $this->config['contentdirs'];
            } else {
                $this->dirs = $dirArr;
            }
        }

        // return file index files for specific folder
        function getFileIndex($folder)
        {
            // Dateiliste einlesen
            $filelist = file($this->config['paths']['rootdir'].$this->config['paths']['cache'].DIRECTORY_SEPARATOR.$folder.".txt"); // Datei lesen

            return $filelist;
        }

        // create url friendly string
        function urlfriendly($string)
        {
            $string = $string;
            $replace = array(" ", "ä",  "ü",  'ö', '&auml;', '&uuml;', '&ouml;', "Ä",  "Ü",  'Ö', '&Auml;', '&Uuml;', '&Ouml;', 'ß', "&szlig", "&#246;");
            $with = array("-", "ae", "ue", "oe", "ae", "ue", "oe", "Ae", "Ue", "Oe", "Ae", "Ue", "Oe", "sz", "sz", "");
            $string = str_replace($replace, $with, $string);

            return $string;
        }

        // get metadata from text header
        function getMetaData($text)
        {
            $metare    = '~^( ?[\-#=_\.])?[ ]{0,3}([A-Za-z0-9][A-Za-z0-9_-]*):\s*(.*?)$~';
            $metamore  = '~^( ?[\-#=_\.])?[ ]{4,}(.*?)$~';
            $meta      = array(); // Will contain the meta data

            $lines = explode(PHP_EOL, $text);

            foreach ( $lines as $id => $line ) 
            {
                if( preg_match($metare, $line, $match) ) 
                {
                    $key = strtolower( $match[2] );
                    $meta[$key] = $match[3];
                    unset($lines[$id]);
                } elseif( isset($key) && preg_match($metamore, $line, $match) ) {
                    $meta[$key] .= $match[2];
                    unset($lines[$id]);
                }
                else break;    
            }

            $result['metadata'] = $meta;
            $result['text']     = implode("\n", $lines);

            return $result;
        }

        // getMetaData from .meta file
        function getMetaDataFromFile($file)
        {
            // construct path to metadata
            $path_parts = pathinfo($file);
            $path_parts['extension'] = "meta";
            $metaDataPath = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].".".$path_parts['extension'];
            $filecontent = file_get_contents($metaDataPath);
            $filecontent = utf8_encode($filecontent);

            return json_decode($filecontent);
        }

        // returns the content of the content directories
        function getDirContent($dir)
        {
            $result = array();           
            $directory = new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS);
            $objects = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::SELF_FIRST);

            foreach($objects as $object)
            {
                if ($object->isDir()) 
                {
                    $result['folders'][] = $object->getPathname();
                } elseif ($object->isFile()) {
                    
                    if(in_array($object->getExtension(), $this->fileExtensions))
                    {
                        $result['files'][] = $object->getPathname();
                    }
                }
            }

            return $result;
        }

        // write folder contents into a txt-file
        function writeFileIndex($folder, $files)
        {
            $dir = $this->config['paths']['rootdir'].$this->config['paths']['cache']; // navigate to cache folder
            
            //create file
            $myfile = fopen($dir.DIRECTORY_SEPARATOR.$folder.".txt", "w") or die("Unable to open file!");
            $txt = '';

            foreach ($files['files'] as $file)
            {
                // filter ./content or .\content
                $str = ".".DIRECTORY_SEPARATOR.$this->config['paths']['content'];
                $txt .= str_replace($str , "", $file)."\n";
            }

            fwrite($myfile, $txt);
            fclose($myfile);

            return true;
        }

        // creates HTML files for every file in the array
        function createHTML($filelistArray)
        {
            $parsedown = new ParsedownExtraPlugin(); // create parsedown instance
            $parsedown->setBreaksEnabled(true);
            $parsedown->setMarkupEscaped(true);

            // custom link attributes
            $parsedown->links_attr = array();

            // custom external link attributes
            $parsedown->links_external_attr = array(
                'target' => '_blank'
            );
            
            $cachedir = $this->config['paths']['rootdir'].$this->config['paths']['cache'].DIRECTORY_SEPARATOR; // navigate to content folder

            foreach ($filelistArray as $file)
            {
                $html = "";
                $newFilename;
                $filecontent;

                // build path to original file
                // and get its content
                $path = trim(str_replace('\\', DIRECTORY_SEPARATOR , $this->config['paths']['rootdir'].$this->config['paths']['content'].$file));
                $filecontent = file_get_contents($path);

                // extract metadata
                // extracts metadata from filecontent
                $extracted = $this->getMetaData($filecontent);
                
                // copy text without metadata
                $filecontent = $extracted['text'];

                // save metadata as json
                $metadataJSON = json_encode($extracted['metadata']);
                        
                // remove file extension and make filename url-friendly, if needed
                $newFilename = str_replace($this->fileExtensions, '', $this->urlfriendly($file));
                $newFilename = trim($newFilename);

                // build path to the fresh and new html file
                $newFilePath = trim(str_replace('\\', DIRECTORY_SEPARATOR , $this->config['paths']['rootdir'].$this->config['paths']['content'].$newFilename));

                // try to open/write file to create/overwrite it. with html file extension
                $myfile = fopen($newFilePath."html", "w") or die("Unable to open file!");
                $html .= $parsedown->text($filecontent);
                fwrite($myfile, $html);
                fclose($myfile);

                // try to open/write metadata in a seperate file
                $metaFile = fopen($newFilePath."meta", "w") or die("Unable to open file!");
                fwrite($metaFile, $metadataJSON);
                fclose($metaFile);
            }

            return true;
        }
    }

?>