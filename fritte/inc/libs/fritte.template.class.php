<?php
    /**
    * Fritte Template Class
    * 
    * A very simple template class for Fritte
    * 
    */

    class Template extends Fritte
    {   
        private $tagEnd             = "}";
        private $tagStart           = "{";
        private $placeholders       = array();
        private $placeholderPrefix  = "$";
        private $templateParts      = array();

        public function __construct()
        {
            Fritte::__construct();
        }

        function setPlaceholder($key, $value)
        {
            $this->placeholders[$key] = $value;
        }

        function setupTag($startTag, $endTag, $prefix)
        {
            $this->tagStart = $startTag;
            $this->tagEnd   = $endTag;
            $this->parameterPrefix = $prefix;
        }

        function addTemplatePart($file)
        {
            $this->templateParts[] = $file;
        }

        function buildTemplate($filelist)
        {
            $pathToCSS = $this->config['paths']['rooturl']."template".DIRECTORY_SEPARATOR.$this->config['settings']['template'].DIRECTORY_SEPARATOR."css".DIRECTORY_SEPARATOR;
            $pathToInc = $this->config['paths']['rooturl']."inc".DIRECTORY_SEPARATOR;

            foreach ($filelist as $file)
            {
                $txt        = "";
                $hfp        = "";
                $html       = "";
                $template   = "";

                $str = ".".DIRECTORY_SEPARATOR.$this->config['paths']['content'];
                $hfp .= str_replace($str , "", $file)."\n";
                $path = trim(str_replace('\\', DIRECTORY_SEPARATOR , $this->config['paths']['rootdir'].$this->config['paths']['content'].$hfp));
                $filecontent = file_get_contents($path);

                $this->setPlaceholder("text", $filecontent);
                $this->setPlaceholder("csspath", $pathToCSS);
                $this->setPlaceholder("incpath", $pathToInc);
                $this->setPlaceholder("rooturl", $this->config['paths']['rooturl']);
                $this->setPlaceholder("siteurl", $this->config['settings']['siteurl']);

                $metajson = $this->getMetaDataFromFile($path);
                
                foreach ($this->placeholders as $key => $value)
                {
                    if(isset($metajson->{$key}))
                    {
                        $this->setPlaceholder($key, $metajson->{$key});
                    } 
                }
                
                if(isset($metajson->{'template'}))
                {
                    $template = $metajson->{'template'};
                } else {
                    $template = 'singlepage.html';
                }

                // try to open/write file to create/overwrite it. with html file extension
                $myfile = fopen($path, "w") or die("Unable to open file!");

                // building template from different parts
                if (!empty($this->templateParts))
                {
                    foreach($this->templateParts as $part)
                    {
                        $html .= $this->output($part);
                    }
                } else {
                    // build template from one file
                    $html .= $this->output($template);
                }
                
                fwrite($myfile, $html);
                fclose($myfile);
            }
        }

        function output($file)
        {
            $file = $this->config['paths']['rootdir'].'template'.DIRECTORY_SEPARATOR.$this->config['settings']['template'].DIRECTORY_SEPARATOR.$file;
            $data;

            if (file_exists($file))
            {
                if (!$data = file_get_contents($file))
                {
                    throw new Exception('File not readable');
                    return false;
                } else {
                    foreach ($this->placeholders as $key => $value)
                    {
                        $replace = $this->tagStart.$this->placeholderPrefix.$key.$this->tagEnd;
                        $data = str_replace($replace, $value, $data);
                    }
                    
                    return $data;
                }
            } else {
                throw new Exception('File not found');
            }
        }
    }

?>